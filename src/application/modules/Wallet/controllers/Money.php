<?php
class MoneyController extends ApiController
{
    protected $authActions = array(
        'balance',
        'withdraw_history',
        'daily_bill',
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    public function balanceAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $timestamp  = $request->getServer('REQUEST_TIME');
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);

        $from   = mktime(0, 0, 0, $month, 1, $year);

        $data = array();
        $defaultData = array(
            'live_length' => 0,
            'live_salary' => 0,
            'live_exclusive_bonus' => 0,
            'goods_golds' => 0,
            'goods_money' => 0,
            'class'       => 0,
            'is_signed'   => 0,
            'is_exclusive' => 0,
        );

        $this->getStreamingDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if ($channelInfo = $streamingChannelModel->getRow($userid)) {
            // Live history
            $sql = 'SELECT SUM(`length`) AS `live_length`, SUM(`length` * `hourly_pay`) AS `live_salary`, SUM(`length` * `hourly_pay` * `exclusive_bonus`) AS `live_exclusive_bonus` FROM `live_length_log` WHERE `from`>=:from AND `channel`=:channel AND `length`>=:starting_length';
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $from,
                ':channel' => $userid,
                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
            ));

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data['live_length'] = (int) $row['live_length'];
                $data['live_salary'] = ceil($row['live_salary'] / 3600);
                $data['live_exclusive_bonus'] = ceil($row['live_exclusive_bonus'] / 3600);
            }

            // Goods history
            $sql = 'SELECT SUM(`golds`) AS `goods_golds`, SUM(`golds` * `withdraw_rate`) AS `goods_money` FROM `goods_log` WHERE `created_on`>=:from AND `receiver`=:receiver';
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $from,
                ':receiver' => $userid,
            ));

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data['goods_golds'] = (int) $row['goods_golds'];
                $data['goods_money'] = ceil($row['goods_money']);
            }

            $data['class'] = $channelInfo['class'];
            $data['is_signed'] = $channelInfo['is_signed'];
            $data['is_exclusive'] = $channelInfo['is_exclusive'];

            $result['data'] = array_merge($defaultData, $data);
            $result['code'] = 200;

        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function withdraw_historyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $where = array();

        $where[] = '`user`=' . (int) $userid;

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);
        $result = $streamingWithdrawOrderModel->search('*', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $key => $val) {
            $result['data'][$key]['live_salary'] = (int) $val['live_salary'];
            $result['data'][$key]['live_exclusive_bonus'] = (int) $val['live_exclusive_bonus'];
            $result['data'][$key]['goods_money'] = (int) $val['goods_money'];
            $result['data'][$key]['total_money'] = (int) $val['total_money'];
        }

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function daily_billAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $timestamp = $request->getServer('REQUEST_TIME');
        $year      = date('Y', $timestamp);
        $month     = date('m', $timestamp);
        $day       = date('d', $timestamp);

        $today  = mktime(0, 0, 0, $month, $day, $year);
        $from   = strtotime('-30 day', $today);

        $data = $tempData = array();
        $defaultRow = array(
            'live_length' => 0,
            'live_salary' => 0,
            'live_exclusive_bonus' => 0,
            'goods_golds' => 0,
            'goods_money' => 0,
        );
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();
//        $timezoneVal = $tzDiff ?: '+2:00';
        $timezoneVal = date_default_timezone_get();

        try {
            $this->getStreamingDb();

            // Live history
            $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`from`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`length`) AS `live_length`, SUM(`length` * `hourly_pay`) AS `live_salary`, SUM(`length` * `hourly_pay` * `exclusive_bonus`) AS `live_exclusive_bonus` FROM `live_length_log` WHERE `from`>=:from AND `channel`=:channel AND `length`>=:starting_length GROUP BY `dt`";
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $from,
                ':channel' => $userid,
                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
            ));

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tempData[$row['dt']]['live_length'] = (int) $row['live_length'];
                $tempData[$row['dt']]['live_salary'] = ceil($row['live_salary'] / 3600);
                $tempData[$row['dt']]['live_exclusive_bonus'] = ceil($row['live_exclusive_bonus'] / 3600);
            }

            // Goods history
            $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`created_on`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`golds`) AS `goods_golds`, SUM(`golds` * `withdraw_rate`) AS `goods_money` FROM `goods_log` WHERE `created_on`>=:from AND `receiver`=:receiver GROUP BY `dt`";
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $from,
                ':receiver' => $userid,
            ));

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tempData[$row['dt']]['goods_golds'] = (int) $row['goods_golds'];
                $tempData[$row['dt']]['goods_money'] = ceil($row['goods_money']);
            }

            krsort($tempData);

            foreach ($tempData as $key => $val) {
                $val = array_merge($defaultRow, $val);
                $val['dt'] = $key;

                $data[] = $val;
            }

            $result['data'] = $data;
            $result['code'] = 200;
        } catch (Exception $e) {
            Misc::log($e->getMessage(), Zend_Log::ERR);
        }

        $this->callback($result);

        return false;
    }
}