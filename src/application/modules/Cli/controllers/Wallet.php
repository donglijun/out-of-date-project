<?php
class WalletController extends CliController
{
    protected $streamingDb;

    protected $redisStreaming;

    protected $redisChat;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    protected function checkStreamingDb()
    {
        if (!$this->streamingDb) {
            $this->getStreamingDb();
        } else {
            try {
                $this->streamingDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('streaming-db');
                $this->streamingDb = null;

                $this->getStreamingDb();
            }
        }
    }

    public function retire_redAction()
    {
        $this->getStreamingDb();
        $this->getRedisStreaming();

        $redisRedQueueModel = new Redis_Red_QueueModel($this->redisStreaming);
        if ($reds = $redisRedQueueModel->retire()) {
            $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);
            $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
            $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
            $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);

            foreach ($reds as $red) {
                try {
                    $this->streamingDb->beginTransaction();

                    $redisRedRedModel->retire($red);

                    if ($redInfo = $redRedModel->getRow($red, array('user', 'points'))) {
                        $consumedPoints = $redisRedRedModel->consumedPoints($red);
                        $consumedNumber = $redisRedRedModel->consumedNumber($red);

                        if (($returnedPoints = $redInfo['points'] - $consumedPoints) > 0) {
                            $pointAccountModel->incr($redInfo['user'], $returnedPoints);

                            $pointLogModel->insert(array(
                                'user'     => $redInfo['user'],
                                'number'   => $returnedPoints,
                                'type'     => MySQL_Point_LogModel::LOG_TYPE_RETURN_RED,
                                'dealt_on' => time(),
                            ));

                            $redRedModel->update($red, array(
//                                'consumed_points' => $consumedPoints,
//                                'consumed_number' => $consumedNumber,
                                'returned_points' => $returnedPoints,
                                'ending_on'       => time(),
                            ));
                        }
                    }

                    $redisRedQueueModel->rem($red);

                    $this->streamingDb->commit();
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    Misc::log($e->getMessage(), Zend_Log::ERR);
                }
            }
        }

        return false;
    }

    public function check_exchange_cardAction()
    {
        $hours = $this->getRequest()->get('hours', 0.05);

        $this->getStreamingDb();

        $cardRequestModel = new MySQL_Card_RequestModel($this->streamingDb);

        foreach ($cardRequestModel->getNotProcessed($hours) as $row) {
            $gearmanClient = Daemon::getGearmanClient();
            $gearmanClient->doBackground('exchange-card', (string) $row['id']);

            if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                Misc::log(sprintf("gearman job (exchange-card) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
            }
        }

        return false;
    }

    public function publish_system_redAction()
    {
        $this->getStreamingDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $redScheduleModel = new MySQL_Red_ScheduleModel($this->streamingDb);
        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);

        if ($schedule = $redScheduleModel->getRightOne()) {
            $timestamp = time();
            $userid = $schedule['created_by'];
            $points = $schedule['points'];
            $number = $schedule['number'];

            if ($points <= $pointAccountModel->number($userid)) {
                try {
                    $this->streamingDb->beginTransaction();

                    $hash = uniqid();

                    $redID = $redRedModel->insert(array(
                        'user'           => $userid,
                        'name'           => $schedule['created_name'],
                        'points'         => $points,
                        'number'         => $schedule['number'],
                        'memo'           => $schedule['memo'],
                        'target_channel' => $schedule['target_channel'],
                        'target_client'  => $schedule['target_client'],
                        'hash'           => $hash,
                        'created_on'     => $timestamp,
                    ));

                    $pointAccountModel->incr($userid, $points * -1);

                    $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                    $pointLogModel->insert(array(
                        'user'     => $userid,
                        'number'   => $points * -1,
                        'type'     => MySQL_Point_LogModel::LOG_TYPE_CONSUME_RED,
                        'dealt_on' => $timestamp,
                    ));

                    // Generate reds
                    $reds = Mkjogo_Red_Generator::lucky($points, $number);

                    // Push to list
                    $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);
                    $redisRedRedModel->push($redID, $reds);

                    // Push to queue
                    $redisRedQueueModel = new Redis_Red_QueueModel($this->redisStreaming);
                    $redisRedQueueModel->add($redID);

                    // Broadcast to channel
                    $data = array(
                        'from'      => array(
                            'id'    => 0,
                            'name'  => '',
                        ),
                        'id'             => $redID,
                        'number'         => $number,
                        'memo'           => $schedule['memo'],
                        'target_channel' => $schedule['target_channel'],
                        'target_client'  => $schedule['target_client'],
                        'hash'           => $hash,
                        'expires'        => strtotime('+24 hour', $timestamp),
                        'timestamp'      => $timestamp,
                    );

                    $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
                    $channels = $redisStreamingChannelOnlineChannelModel->getList();

                    $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                    $redisStreamingChatChannelModel->publishRed($channels, $data);

                    // Update schedule status
                    $redScheduleModel->update($schedule['id'], array(
                        'publish_status' => MySQL_Red_ScheduleModel::PUBLISH_STATUS_SUCCESSFUL,
                    ));

                    $this->streamingDb->commit();
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    Misc::log($e->getMessage(), Zend_Log::ERR);

                    $redScheduleModel->update($schedule['id'], array(
                        'publish_status' => MySQL_Red_ScheduleModel::PUBLISH_STATUS_FAILED,
                    ));
                }
            } else {
                $redScheduleModel->update($schedule['id'], array(
                    'publish_status' => MySQL_Red_ScheduleModel::PUBLISH_STATUS_FAILED,
                ));
            }
        }

        return false;
    }

//    public function calc_salary_weeklyAction()
//    {
//        $request = $this->getRequest();
//        $config = Yaf_Registry::get('config')->toArray();
//
//        $result = array();
//
//        $timestamp  = $request->get('at', 0) ?: time();
//        $year       = date('Y', $timestamp);
//        $month      = date('m', $timestamp);
//        $day        = date('d', $timestamp);
//        $weekday    = date('N', $timestamp);
//
//        $today  = mktime(0, 0, 0, $month, $day, $year);
//        $to     = strtotime(sprintf('%d day', 1 - $weekday), $today);
//        $from   = strtotime('-7 day', $to);
//
//        $date   = date('oW', $from);
//
//        try {
//            $this->getStreamingDb();
//
//            printf("==== Calculate live history data at %s ====\n", date('Y-m-d H:i:s'));
//
//            $sql = 'SELECT `channel`, SUM(`length`) AS `live_length`, SUM(`length` * `hourly_pay`) AS `live_salary`, SUM(`length` * `hourly_pay` * `exclusive_bonus`) AS `live_exclusive_bonus` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `length`>=:starting_length GROUP BY `channel`';
//            $stmt = $this->streamingDb->prepare($sql);
//            $stmt->execute(array(
//                ':from' => $from,
//                ':to' => $to,
//                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
//            ));
//
//            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
//                $result[$row['channel']] = array(
//                    'live_length' => $row['live_length'],
//                    'live_salary' => $row['live_salary'] / 3600,
//                    'live_exclusive_bonus' => $row['live_exclusive_bonus'] / 3600,
//                );
//            }
//
//            $this->checkStreamingDb();
//
//            printf("==== Calculate goods history data at %s ====\n", date('Y-m-d H:i:s'));
//
//            $sql = 'SELECT `receiver` AS `channel`, SUM(`golds`) AS `goods_golds`, SUM(`golds` * `withdraw_rate`) AS `goods_money` FROM `goods_log` WHERE `created_on`>=:from AND `created_on`<:to GROUP BY `receiver`';
//            $stmt = $this->streamingDb->prepare($sql);
//            $stmt->execute(array(
//                ':from' => $from,
//                ':to' => $to,
//            ));
//
//            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
//                $result[$row['channel']]['goods_golds'] = $row['goods_golds'];
//                $result[$row['channel']]['goods_money'] = MySQL_Gold_AccountModel::numToMoney($row['goods_money']);
//            }
//
//            $this->checkStreamingDb();
//
//            printf("==== Build withdraw order at %s ====\n", date('Y-m-d H:i:s'));
//
//            $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);
//            $createdOn = time();
//            $defaultRow = array(
//                'live_length' => 0,
//                'live_salary' => 0,
//                'live_exclusive_bonus' => 0,
//                'goods_golds' => 0,
//                'goods_money' => 0,
//            );
//
//            foreach ($result as $key => $val) {
//                $val = array_merge($defaultRow, $val);
//
//                $streamingWithdrawOrderModel->batchInsert(array(
//                    'user' => $key,
//                    'dt' => $date,
//                    'live_length' => $val['live_length'],
//                    'live_salary' => $val['live_salary'],
//                    'live_exclusive_bonus' => $val['live_exclusive_bonus'],
//                    'goods_golds' => $val['goods_golds'],
//                    'goods_money' => $val['goods_money'],
//                    'total_money' => $val['live_salary'] + $val['live_exclusive_bonus'] + $val['goods_money'],
//                    'created_on' => $createdOn,
//                ));
//            }
//
//            $streamingWithdrawOrderModel->completeBatchInsert();
//
//            printf("==== Complete at %s ====\n", date('Y-m-d H:i:s'));
//
//            printf("Calculate withdraw data weekly: %d\n", $date);
//        } catch (Exception $e) {
//            var_dump($e->getMessage());
//        }
//
//        return false;
//    }

    public function calc_salary_monthlyAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = $paypals = array();

        $timestamp  = $request->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);

        $to     = mktime(0, 0, 0, $month, 1, $year);
        $from   = strtotime('-1 month', $to);

        $date   = date('Ym', $from);

        try {
            $this->getStreamingDb();

            printf("==== Calculate live history data at %s ====\n", date('Y-m-d H:i:s'));

            $sql = 'SELECT `channel`, SUM(`length`) AS `live_length`, SUM(`length` * `hourly_pay`) AS `live_salary`, SUM(`length` * `hourly_pay` * `exclusive_bonus`) AS `live_exclusive_bonus` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `length`>=:starting_length GROUP BY `channel`';
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $from,
                ':to' => $to,
                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
            ));

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[$row['channel']] = array(
                    'live_length' => (int) $row['live_length'],
                    'live_salary' => ceil($row['live_salary'] / 3600),
                    'live_exclusive_bonus' => ceil($row['live_exclusive_bonus'] / 3600),
                );
            }

            $this->checkStreamingDb();

            printf("==== Calculate goods history data at %s ====\n", date('Y-m-d H:i:s'));

            $sql = 'SELECT `receiver` AS `channel`, SUM(`golds`) AS `goods_golds`, SUM(`golds` * `withdraw_rate`) AS `goods_money` FROM `goods_log` WHERE `created_on`>=:from AND `created_on`<:to GROUP BY `receiver`';
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $from,
                ':to' => $to,
            ));

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[$row['channel']]['goods_golds'] = (int) $row['goods_golds'];
                $result[$row['channel']]['goods_money'] = ceil($row['goods_money']);
            }

            $this->checkStreamingDb();

            // Find paypals
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            foreach ($streamingChannelModel->getRows(array_keys($result, array('id', 'paypal'))) as $row) {
                $paypals[$row['id']] = $row['paypal'];
            }

            printf("==== Build withdraw order at %s ====\n", date('Y-m-d H:i:s'));

            $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);
            $createdOn = time();
            $defaultRow = array(
                'live_length' => 0,
                'live_salary' => 0,
                'live_exclusive_bonus' => 0,
                'goods_golds' => 0,
                'goods_money' => 0,
            );

            foreach ($result as $key => $val) {
                $val = array_merge($defaultRow, $val);

                if ($totalMoney = $val['live_salary'] + $val['live_exclusive_bonus'] + $val['goods_money']) {
                    $streamingWithdrawOrderModel->batchInsert(array(
                        'user' => $key,
                        'dt' => $date,
                        'paypal' => isset($paypals[$key]) ? $paypals[$key] : '',
                        'live_length' => $val['live_length'],
                        'live_salary' => $val['live_salary'],
                        'live_exclusive_bonus' => $val['live_exclusive_bonus'],
                        'goods_golds' => $val['goods_golds'],
                        'goods_money' => $val['goods_money'],
                        'total_money' => $totalMoney,
                        'created_on' => $createdOn,
                    ));
                }
            }

            $streamingWithdrawOrderModel->completeBatchInsert();

            printf("==== Complete at %s ====\n", date('Y-m-d H:i:s'));

            printf("Calculate withdraw data monthly: %d\n", $date);
        } catch (Exception $e) {
            Misc::log($e->getMessage(), Zend_Log::ERR);
        }

        return false;
    }
}