<?php
class StreamingreportController extends AdminController
{
    protected $authActions = array(
        'length' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'incoming_by_class' => MySQL_AdminAccountModel::GROUP_ADMIN,
        'incoming_by_channel' => MySQL_AdminAccountModel::GROUP_ADMIN,
        'daily_length' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'daily_top_n' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'daily_watching_length' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

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

    public function lengthAction()
    {
        $config = Yaf_Registry::get('config')->toArray();
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();

        $this->_view->assign(array(
            'domain' => isset($config['cookie']['domain']) ? $config['cookie']['domain'] : 'nikksy.com',
            'timezone' => $tzOffset ?: 2,
        ));
    }

    public function daily_lengthAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = array(
            'code'  => 500,
        );

        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');
        $parameters = $data = array();
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();
//        $timezoneVal = $tzDiff ?: '+2:00';
        $timezoneVal = date_default_timezone_get();

        $this->getStreamingDb();

        if ($channel = $request->get('channel')) {
            $filter['channel'] = $channel;
        }
        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        // Set MySQL timezone
//        $sql = sprintf("set time_zone '%s'", $config['date']['mysql-timezone'] ?: '+2:00');
//        $this->streamingDb->exec($sql);

        if ($channel) {
            $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`from`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`length`) AS `live_length` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `channel`=:channel AND `length`>=:starting_length GROUP BY `dt` ORDER BY `dt` ASC";
            $parameters = array(
                ':from' => $dateFrom,
                ':to' => $dateTo,
                ':channel' => $channel,
                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
            );
        } else {
            $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`from`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`length`) AS `live_length` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `length`>=:starting_length GROUP BY `dt` ORDER BY `dt` ASC";
            $parameters = array(
                ':from' => $dateFrom,
                ':to' => $dateTo,
                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
            );
        }

        $stmt = $this->streamingDb->prepare($sql);
        $stmt->execute($parameters);

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $key => $val) {
            $data[$key]['timestamp'] = strtotime($val['dt']) * 1000;
            $data[$key]['live_length'] = ceil($val['live_length'] / 3600);
        }

//        // test data
//        $data = array(
//            array(
//                'dt' => '2015-10-29',
//                'timestamp' => 1446048000 * 1000,
//                'live_length' => 200,
//            ),
//            array(
//                'dt' => '2015-10-30',
//                'timestamp' => 1446134400 * 1000,
//                'live_length' => 1000,
//            ),
//        );

        $result['data'] = $data;
        $result['filter'] = $filter;
        $result['code'] = 200;

        Yaf_Registry::get('layout')->disableLayout();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);

        return false;
    }

    public function daily_top_nAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = array(
            'code'  => 500,
        );

        $limit = (int) $request->get('limit');
        $limit = $limit ?: 10;
        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');

        $this->getStreamingDb();

        $filter['limit'] = $limit;
        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        $dateTo = strtotime('+1 day', $dateFrom);

        $sql = "SELECT `channel`, SUM(`length`) AS `live_length` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `length`>=:starting_length GROUP BY `channel` ORDER BY `live_length` DESC LIMIT {$limit}";
        $stmt = $this->streamingDb->prepare($sql);
        $stmt->execute(array(
            ':from' => $dateFrom,
            ':to' => $dateTo,
            ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
        ));

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $key => $val) {
            $data[$key]['live_length'] = ceil($val['live_length'] / 3600);
        }

//        // test data
//        $data = array(
//            array(
//                'channel' => 'little2225',
//                'live_length' => 1000,
//            ),
//            array(
//                'channel' => 'big',
//                'live_length' => 200,
//            ),
//        );

        $result['data'] = $data;
        $result['filter'] = $filter;
        $result['code'] = 200;

        Yaf_Registry::get('layout')->disableLayout();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);

        return false;
    }

    public function incoming_by_classAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');
        $channels = $filter = $data = array();
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();
//        $timezoneVal = $tzDiff ?: '+2:00';
        $timezoneVal = date_default_timezone_get();
        $defaultRow = array(
            'live_length' => 0,
            'live_incoming' => 0,
            'goods_incoming' => 0,
        );

        $this->getStreamingDb();
        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);

        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        if (($class = $request->get('class')) && $streamingChannelClassModel->exists($class)) {
            $filter['class'] = $class;

            $sql = "SELECT `id` FROM `channel` WHERE `class`=:class";
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':class' => $class,
            ));

            if ($channels = $stmt->fetchAll(PDO::FETCH_COLUMN)) {
                $channels = implode(',', $channels);

                $sql = "SELECT `channel`, SUM(`length`) AS `live_length`, SUM(`length` * `hourly_pay` * (1 + `exclusive_bonus`)) AS `live_incoming` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `channel` IN ({$channels}) AND `length`>=:starting_length GROUP BY `channel` ORDER BY `channel` ASC";
                $stmt = $this->streamingDb->prepare($sql);
                $stmt->execute(array(
                    ':from' => $dateFrom,
                    ':to' => $dateTo,
                    ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
                ));

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $data[$row['channel']] = array(
                        'live_length' => ceil($row['live_length'] / 3600),
                        'live_incoming' => ceil($row['live_incoming'] / 3600),
                    );
                }

                $sql = "SELECT `receiver` AS `channel`, SUM(`golds` * `withdraw_rate`) AS `goods_incoming` FROM `goods_log` WHERE `created_on`>=:from AND `created_on`<:to AND `receiver` IN ({$channels}) GROUP BY `receiver` ORDER BY `receiver` ASC";
                $stmt = $this->streamingDb->prepare($sql);
                $stmt->execute(array(
                    ':from' => $dateFrom,
                    ':to' => $dateTo,
                ));

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $data[$row['channel']]['goods_incoming'] = ceil($row['goods_incoming']);
                }

                foreach ($data as $key => $val) {
                    $val = array_merge($defaultRow, $val);
                    $val['channel'] = $key;

                    $data[$key] = $val;
                }
            }
        }

        $result['data'] = $data;
        $result['filter'] = $filter;
        $result['classes'] = $streamingChannelClassModel->getAll();
        $result['code'] = 200;

        $this->_view->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->_view->render('streamingreport/incoming-by-class.phtml'));

        return false;
    }

    public function incoming_by_channelAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');
        $filter = $data = $tempData = array();
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();
//        $timezoneVal = $tzDiff ?: '+2:00';
        $timezoneVal = date_default_timezone_get();
        $defaultRow = array(
            'live_length' => 0,
            'live_incoming' => 0,
            'goods_incoming' => 0,
        );

        $this->getStreamingDb();

        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        if ($channel = $request->get('channel')) {
            $filter['channel'] = $channel;

            // Live history
            $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`from`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`length`) AS `live_length`, SUM(`length` * `hourly_pay` * (1 + `exclusive_bonus`)) AS `live_incoming` FROM `live_length_log` WHERE `from`>=:from AND `from`<:to AND `channel`=:channel AND `length`>=:starting_length GROUP BY `dt`";
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $dateFrom,
                ':to' => $dateTo,
                ':channel' => $channel,
                ':starting_length' => (int)$config['streaming']['salary']['starting-length'],
            ));

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tempData[$row['dt']]['live_length'] = ceil($row['live_length'] / 3600);
                $tempData[$row['dt']]['live_incoming'] = ceil($row['live_incoming'] / 3600);
            }

            // Goods history
            $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`created_on`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`golds` * `withdraw_rate`) AS `goods_incoming` FROM `goods_log` WHERE `created_on`>=:from AND `created_on`<:to AND `receiver`=:receiver GROUP BY `dt`";
            $stmt = $this->streamingDb->prepare($sql);
            $stmt->execute(array(
                ':from' => $dateFrom,
                ':to' => $dateTo,
                ':receiver' => $channel,
            ));

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tempData[$row['dt']]['goods_incoming'] = ceil($row['goods_incoming']);
            }

            krsort($tempData);

            foreach ($tempData as $key => $val) {
                $val = array_merge($defaultRow, $val);
                $val['dt'] = $key;

                $data[] = $val;
            }
        }

        $result['data'] = $data;
        $result['filter'] = $filter;
        $result['code'] = 200;

        $this->_view->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->_view->render('streamingreport/incoming-by-channel.phtml'));

        return false;
    }

    public function daily_watching_lengthAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = array(
            'code'  => 500,
        );
        $data = $filter = array();

        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');

        $this->getStreamingDb();

        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = date('Ymd', strtotime($from));
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = date('Ymd', strtotime('+1 day', $dateTo));
        }

        $sql = "SELECT `dt`,`views`,`length` AS `watching_length` FROM `watching_length_summary` WHERE `dt`>=:from AND `dt`<=:to ORDER BY `dt` ASC";

        $stmt = $this->streamingDb->prepare($sql);
        $stmt->execute(array(
            ':from' => $dateFrom,
            ':to' => $dateTo,
        ));

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $key => $val) {
            $dateParts = date_parse_from_format('Ymd', $val['dt']);
            $data[$key]['dt'] = $dateParts['year'] . '-' . $dateParts['month'] . '-' . $dateParts['day'];
            $data[$key]['timestamp'] = strtotime($val['dt']) * 1000;
            $data[$key]['watching_length'] = ceil($val['watching_length'] / 3600);
        }

        $result['data'] = $data;
        $result['filter'] = $filter;
        $result['code'] = 200;

        Yaf_Registry::get('layout')->disableLayout();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);

        return false;
    }
}