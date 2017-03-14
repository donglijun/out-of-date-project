<?php
use Aws\S3\S3Client;

class StreamingController extends CliController
{
    protected $authActions = array();

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    public function reset_online_dataAction()
    {
        $this->getStreamingDb();
        $this->getRedisStreaming();

        echo "Clear online channel.\n";
        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
        $redisStreamingChannelOnlineChannelModel->clear();

        echo "Clear online client by channel.\n";
        $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
        $redisStreamingChannelOnlineClientByChannelModel->clear();

        echo "Clear online client by server.\n";
        $redisStreamingChannelOnlineClientByServerModel = new Redis_Streaming_Channel_Online_ClientByServerModel($this->redisStreaming);
        foreach ($redisStreamingChannelOnlineClientByServerModel->enumKeys() as $key) {
            $this->redisStreaming->del($key);
        }

        echo "Clear paused channel. \n";
        $redisStreamingChannelPausedModel = new Redis_Streaming_Channel_PausedModel($this->redisStreaming);
        foreach ($redisStreamingChannelPausedModel->enumKeys() as $key) {
            $this->redisStreaming->del($key);
        }

        echo "Clear online client.\n";
        $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
        foreach ($redisStreamingChannelOnlineClientModel->enumKeys() as $key) {
            $this->redisStreaming->del($key);
        }

        return false;
    }

    public function reopen_highlightAction()
    {
        $request = $this->getRequest();

        $ids = $request->get('ids');

        $ids = Misc::parseIds($ids);

        $gearmanClient = Daemon::getGearmanClient();

        foreach ($ids as $highlightID) {
            $gearmanClient->doBackground('streaming-highlight', $highlightID);

            echo "Send to job {$highlightID}\n";
        }

        return false;
    }

    public function sync_gift_accountAction()
    {
        $request = $this->getRequest();

        $this->getPassportDb();
        $this->getStreamingDb();

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);

        $userRange = $userAccountModel->getRange('id');

        $range = range($userRange['min'], $userRange['max']);
        foreach (array_chunk($range, 100) as $chunk) {
            $values = array();

            foreach ($chunk as $item) {
                $values[] = sprintf("('%s')", $item);
            }

            $values = implode(',', $values);

            $sql = 'REPLACE INTO `gift_account` (`id`) VALUES %s';
            $sql = sprintf($sql, $values);

            $this->streamingDb->exec($sql);
        }

        return false;
    }

    public function sync_point_accountAction()
    {
        $request = $this->getRequest();

        $this->getPassportDb();
        $this->getStreamingDb();

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

        $userRange = $userAccountModel->getRange('id');

        $range = range($userRange['min'], $userRange['max']);
        foreach (array_chunk($range, 100) as $chunk) {
            $values = array();

            foreach ($chunk as $item) {
                $values[] = sprintf("('%s')", $item);
            }

            $values = implode(',', $values);

            $sql = 'REPLACE INTO `point_account` (`id`) VALUES %s';
            $sql = sprintf($sql, $values);

            $this->streamingDb->exec($sql);
        }

        return false;
    }

    public function sync_gold_accountAction()
    {
        $request = $this->getRequest();

        $this->getPassportDb();
        $this->getStreamingDb();

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);

        $userRange = $userAccountModel->getRange('id');

        $range = range($userRange['min'], $userRange['max']);
        foreach (array_chunk($range, 100) as $chunk) {
            $values = array();

            foreach ($chunk as $item) {
                $values[] = sprintf("('%s')", $item);
            }

            $values = implode(',', $values);

            $sql = 'REPLACE INTO `gold_account` (`id`) VALUES %s';
            $sql = sprintf($sql, $values);

            $this->streamingDb->exec($sql);
        }

        return false;
    }

    public function build_gift_rankingAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $redisGiftRankingDailyModel = new Redis_Gift_Ranking_DailyModel($this->redisStreaming);
        $redisGiftRankingMonthlyModel = new Redis_Gift_Ranking_MonthlyModel($this->redisStreaming);

        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);

        $redisGiftRankingMonthlyModel->clear();

        $start = 1;
        $end = 30000;
        $limit = 1000;
        while ($rows = $giftChannelLogModel->getRowsByStep('id', $start, $end, $limit)) {
            foreach ($rows as $row) {
                $redisGiftRankingMonthlyModel->incr($row['channel'], $row['number'], $row['dealt_on']);
            }

            $start = $row['id'] + 1;
        }

        return false;
    }

    public function zoom_points_0615Action()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getRedisStreaming();

        // Update database
//        echo "Update database.\n";
//        try {
//            $this->streamingDb->beginTransaction();
//
//            $sql = 'update `point_log` set `number`=`number`*2';
//            $this->streamingDb->exec($sql);
//            $sql = 'update `red` set `points`=`points`*2, `consumed_points`=`consumed_points`*2';
//            $this->streamingDb->exec($sql);
//            $sql = 'update `red_log` set `points`=`points`*2';
//            $this->streamingDb->exec($sql);
//            $sql = 'update `point_account` set `number`=`number`*2';
//            $this->streamingDb->exec($sql);
//
//            $this->streamingDb->commit();
//        } catch (Exception $e) {
//            $this->streamingDb->rollBack();
//
//            var_dump($e->getMessage());
//        }

        // Update redis
        echo "Update redis.\n";
//        $keys = $this->redisStreaming->keys('red.consumed:*');
//        foreach ($keys as $key) {
//            $data = $this->redisStreaming->hGetAll($key);
//
//            $key2 = $key . ':backup';
//            $this->redisStreaming->renameNx($key, $key2);
//
//            foreach ($data as $k => $v) {
//                $this->redisStreaming->hSet($key, $k, $v * 2);
//            }
//        }
        $ids = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
        $redLogModel = new MySQL_Red_LogModel($this->streamingDb);
        foreach ($ids as $id) {
            $key = 'red.consumed:' . $id;
            $this->redisStreaming->del($key);
            $members = $redLogModel->members($id);

            $members = array_reverse($members);

            foreach ($members as $member) {
                $this->redisStreaming->hSet($key, $member['user'], $member['points']);
            }
        }

        return false;
    }

    public function upload_broadcastAction()
    {
        $request = $this->getRequest();

        if ($broadcast = $request->get('broadcast')) {
            $yarStreamingBroadcastModel = new Yar_Streaming_BroadcastModel();

            $yarStreamingBroadcastModel->upload($broadcast);
        }

        return false;
    }

    public function build_goods_accountAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $redisGoodsChannelTotalModel = new Redis_Goods_Channel_TotalModel($this->redisStreaming);

        $goodsLogModel = new MySQL_Goods_LogModel($this->streamingDb);

        $sql = "select `receiver`,`goods`,sum(`number`) as `total` from `goods_log` group by `receiver`,`goods`";
        $stmt = $this->streamingDb->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $redisGoodsChannelTotalModel->incrBy($row['receiver'], $row['goods'], $row['total']);
        }

        return false;
    }

    // 2015-11-06
    public function settle_remained_earn_moneyAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);
        $createdOn = time();
        $date = '201510';
        $defaultRow = array(
            'live_length' => 0,
            'live_salary' => 0,
            'live_exclusive_bonus' => 0,
            'goods_golds' => 0,
            'goods_money' => 0,
        );

        $sql = "SELECT `id` AS `user`,`remained_earn_num` AS `goods_golds`,`remained_earn_num` * 0.5 AS `goods_money` FROM `gold_account` WHERE `remained_earn_num`>0";
        $stmt = $this->streamingDb->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $val) {
            $val = array_merge($defaultRow, $val);

            $channelInfo = $streamingChannelModel->getRow($val['user'], array('paypal'));

            $data = array(
                'user' => $val['user'],
                'dt' => $date,
                'paypal' => $channelInfo['paypal'],
                'live_length' => $val['live_length'],
                'live_salary' => $val['live_salary'],
                'live_exclusive_bonus' => $val['live_exclusive_bonus'],
                'goods_golds' => $val['goods_golds'],
                'goods_money' => ceil($val['goods_money']),
                'total_money' => $val['live_salary'] + $val['live_exclusive_bonus'] + ceil($val['goods_money']),
                'created_on' => $createdOn,
            );

//            var_dump($data);

            $streamingWithdrawOrderModel->batchInsert($data);
        }

        $streamingWithdrawOrderModel->completeBatchInsert();

        return false;
    }

    public function rebuild_user_growth_levelAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $this->getStreamingDb();

        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);
        $giftGrowthSchemeModel = new MySQL_Gift_GrowthSchemeModel($this->streamingDb);
        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);

        $userGifts = $giftChannelLogModel->sumByUser(0, $timestamp);
        $levelPoints = $giftGrowthSchemeModel->getLevelPointsMap();

        printf("==== Find %d user records %s ====\n", count($userGifts), date('Y-m-d H:i:s'));

        foreach ($userGifts as $row) {
            $user = $row['user'];
            $userLevel = 1;
            $userPoints = $row['sum'];

            foreach ($levelPoints as $level => $points) {
                if ($userPoints >= $points) {
                    $userPoints -= $points;
                    $userLevel++;
                } else {
                    break;
                }
            }

            $giftAccountModel->update($user, array(
                'growth_level' => $userLevel,
                'growth_points' => $userPoints,
            ));
        }

        printf("==== Completed %s ====\n", count($userGifts), date('Y-m-d H:i:s'));

        return false;
    }
}