<?php
class UserController extends CliController
{
    protected $authActions = array(
        'retention' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'ipcountry' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $accountDb;

    protected $mkjogoDb;

    protected $redis;

    protected function getAccountDb()
    {
        if (empty($this->accountDb)) {
            $this->accountDb = Daemon::getDb('account-db', 'account-db');
        }

        return $this->accountDb;
    }

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getRedis()
    {
        if (empty($this->redis)) {
            $this->redis = Daemon::getRedis();
        }

        return $this->redis;
    }

    public function retentionAction()
    {
        $request = $this->getRequest();

        $idoffset = 100000;

        $from   = strtotime($request->get('from', ''));
        $to     = strtotime($request->get('to', ''));

        $start  = mktime(0, 0, 0, 6, 1, 2013);
        $end    = $from;

        $param_arr = array(
            'OR',
            'tmp:retention',
        );

        for ($i = $from; $i < $to; $i += 86400) {
            $param_arr[] = Redis_DauModel::key($i);
        }

        call_user_func_array(array($this->getRedis(), 'bitOp'), $param_arr);

        $sql = "SELECT MIN(`user_id`) AS `min`, MAX(`user_id`) AS `max`, COUNT(`user_id`) AS `count` FROM `user` WHERE `user_regdate`>=:start AND `user_regdate`<:stop";
        $stmt = $this->getAccountDb()->prepare($sql);

        while ($start < $end) {
            $stop = strtotime('+1 month', $start);

            $stmt->execute(array(
                ':start' => $start,
                ':stop'  => $stop,
            ));

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            printf("%s - %s: %d, %d, %d, %d\n", date('Y-m-d', $start), date('Y-m-d', $stop), $row['min'], $row['max'], $row['count'], $this->redis->bitCount('tmp:retention', floor(($row['min'] - $idoffset) / 8), floor(($row['max'] - $idoffset) / 8)));

            $start = $stop;
        }

        return false;
    }

    public function ipcountryAction()
    {
        set_time_limit(0);

        $request = $this->getRequest();

        $key = 'ipcountry:' . date('Ymd');
        $start = 100000;
        $endtime = mktime(0, 0, 0, 9, 22, 2014);
        $map = $ips = array();

        $sql = "SELECT `user_id`, `user_ip` FROM `user` WHERE `user_id`>=:start AND `user_regdate`<:endtime ORDER BY `user_id` ASC LIMIT 10000";
        $stmt = $this->getAccountDb()->prepare($sql);
        $redis = $this->getRedis();

        try {
            while (true) {
                $stmt->execute(array(
                    ':start'    => $start,
                    ':endtime'  => $endtime,
                ));

                foreach (($rowset = $stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
                    if ($row['user_ip']) {
//                        $ips[] = $row['user_ip'];
                        $ret = Mon17_IP::find($row['user_ip']);
                        $redis->hIncrBy($key, $ret[0], 1);
                    }
                }

                if (!$rowset) {
                    break;
                }

                $start = $row['user_id'] + 1;
            }
        } catch (Exception $e) {
            Debug::dump($e);
        }

//        foreach ($ips as $ip) {
//            $ret = Mon17_IP::find($ip);
//
//            if ($ret) {
//                $map[$ret[0]] += 1;
//            }
//        }
//
//        unset($ips);
        $map = $redis->hGetAll($key);

        arsort($map, SORT_NUMERIC);

        foreach ($map as $key => $val) {
            printf("%s \t %s\n", $key, $val);
        }

        return false;
    }

    public function retention30Action()
    {
        $request = $this->getRequest();
        $this->getMkjogoDb();
        $this->getAccountDb();
        $this->getRedis();
        $ouModel = new Redis_OuModel();

        $rs = $regIncDaily = $auDailyByLang = $au30ByLang = $regRangeDaily = array();
        $regIncDailyByLang = $regInc30ByLang = $retention2ByLang = $retention30ByLang = array();
        $regTotal = $regInc30 = $regRange30 = $regRange60to30 = 0;
        $idoffset = 100000;
        $langs = array('it_it', 'es_es', 'zh_tw', 'zh_cn', 'ko_kr', 'en_us', 'ru_ru', 'tr_tr', 'pt_br', 'ko');
        sort($langs);
        $days = array();

        $from   = mktime(0, 0, 0, 8, 23, 2014);
        $to     = strtotime('+30 day', $from);
        $from2  = strtotime('-30 day', $from);

        $fromDate = date('Ymd', $from);
        $toDate   = date('Ymd', $to);

        $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);

        // Total registration
        $sql = "SELECT COUNT(*) FROM `user` WHERE `user_regdate`<:endtime";
        $stmt = $this->accountDb->prepare($sql);
        $stmt->execute(array(
            ':endtime'  => $to,
        ));
        $regTotal = $stmt->fetchColumn();

        // Registration increment in 30 days
        $regInc30 = $mkjogoUserModel->getRegistrationCount($from, $to);

        // Registration id range in 30 days
        $where = sprintf('`user_regdate`>=%d AND `user_regdate`<%d', $from, $to);
        $regRange30 = $mkjogoUserModel->getRange('user_id', $where);

        // Registration id range from 60 days to 30 days
        $where = sprintf('`user_regdate`>=%d AND `user_regdate`<%d', $from2, $from);
        $regRange60to30 = $mkjogoUserModel->getRange('user_id', $where);

        for ($i = $from; $i < $to; $i += 86400) {
            $day = date('Ymd', $i);
            $days[] = $day;

            // Registration id range daily
            $where = sprintf('`user_regdate`>=%d AND `user_regdate`<%d', $i, $i + 86400);
            $regRangeDaily[$day] = $mkjogoUserModel->getRange('user_id', $where);
        }

        // Registration increment daily
        $reportRegistrationDailyModel = new MySQL_ReportRegistrationDailyModel($this->mkjogoDb);
        $rs = $reportRegistrationDailyModel->getRowsByRange('date', $fromDate, $toDate);
        foreach ($rs as $row) {
            $regIncDaily[$row['date']] = $row['increment'];
        }

        // AU daily by lang
        foreach ($langs as $lang) {
            foreach ($days as $day) {
                $key = 'dau:' . $lang . ':' . $day;

                // AU daily by lang
                $auDailyByLang[$lang][$day] = $this->redis->bitCount($key);

                // Registration increment daily by lang
                $range = $regRangeDaily[$day];
                $regIncDailyByLang[$lang][$day] = $this->redis->bitCount($key, floor(($range['min'] - $idoffset) / 8), floor(($range['max'] - $idoffset) / 8));
            }
        }

        // AU 30 days
        foreach ($langs as $lang) {
            $key = 'au30:' . $lang;
            $param_arr = array(
                'OR',
                $key,
            );
            foreach ($days as $day) {
                $param_arr[] = 'dau:' . $lang . ':' . $day;
            }
            call_user_func_array(array($this->redis, 'bitOp'), $param_arr);
            $au30ByLang[$lang] = $this->redis->bitCount($key);

            // Registration increment in 30 days by lang
            $regInc30ByLang[$lang] = $this->redis->bitCount($key, floor(($regRange30['min'] - $idoffset) / 8), floor(($regRange30['max'] - $idoffset) / 8));

            // Retention from 60 to 30 days
            $retention30ByLang[$lang] = $this->redis->bitCount($key, floor(($regRange60to30['min'] - $idoffset) / 8), floor(($regRange60to30['max'] - $idoffset) / 8));
        }

        // Retention day by day in 30 days
        foreach ($langs as $lang) {
            for ($i = $from; $i < $to; $i += 86400) {
                $day = date('Ymd', $i);
                $day2 = date('Ymd', $i + 86400);

                $key = 'dau:' . $lang . ':' . $day2;

                $range = $regRangeDaily[$day];
                $retention2ByLang[$lang][$day] = $this->redis->bitCount($key, floor(($range['min'] - $idoffset) / 8), floor(($range['max'] - $idoffset) / 8));
            }
        }

        // Debug
//        var_dump($auDailyByLang);
//        var_dump($au30ByLang);
//        var_dump($regIncDailyByLang);
//        var_dump($regInc30ByLang);
//        var_dump($retention2ByLang);
//        var_dump($retention30ByLang);
//        exit();

        // Output
        echo "<html><head><meta charset=\"utf-8\"></head><body>";

        // a
        print("<p>日活跃用户</p>\n");
        print("<table border=1>\n<tr><th>&nbsp;</th>");
        foreach ($days as $day) {
            printf("<th>%s</th>", $day);
        }
        print("</tr>\n");
        foreach ($auDailyByLang as $lang => $val) {
            print("<tr>\n");
            printf("<th>%s</th>\n", $lang);
            foreach ($val as $day => $num) {
                printf("<td>%s</td>\n", $num);
            }
            printf("</tr>\n");
        }
        printf("</table>\n");

        print("<p>30天活跃用户</p>\n");
        print("<table border=1>\n");
        foreach ($au30ByLang as $lang => $val) {
            printf("<tr><th>%s</th><td>%s</td></tr>\n", $lang, $val);
        }
        printf("</table>\n");

        // b
        print("<p>日新增用户 (* 仅包含当日注册且当日登录过客户端的用户 *)</p>\n");
        print("<table border=1>\n<tr><th>&nbsp;</th>");
        foreach ($days as $day) {
            printf("<th>%s</th>", $day);
        }
        print("</tr>\n");
        foreach ($regIncDailyByLang as $lang => $val) {
            print("<tr>\n");
            printf("<th>%s</th>\n", $lang);
            foreach ($val as $day => $num) {
                printf("<td>%s</td>\n", $num);
            }
            printf("</tr>\n");
        }
        printf("</table>\n");

        print("<p>30天新增用户 (* 仅包含近30天注册且登录过客户端的用户 *)</p>\n");
        print("<table border=1>\n");
        foreach ($regInc30ByLang as $lang => $val) {
            printf("<tr><th>%s</th><td>%s</td></tr>\n", $lang, $val);
        }
        printf("</table>\n");

        // c
        print("<p>次日留存用户</p>\n");
        print("<table border=1>\n<tr><th>&nbsp;</th>");
        foreach ($days as $day) {
            printf("<th>%s</th>", $day);
        }
        print("</tr>\n");
        foreach ($retention2ByLang as $lang => $val) {
            print("<tr>\n");
            printf("<th>%s</th>\n", $lang);
            foreach ($val as $day => $num) {
                printf("<td>%s</td>\n", $num);
            }
            printf("</tr>\n");
        }
        printf("</table>\n");

        print("<p>前月留存用户</p>\n");
        print("<table border=1>\n");
        foreach ($retention30ByLang as $lang => $val) {
            printf("<tr><th>%s</th><td>%s</td></tr>\n", $lang, $val);
        }
        printf("</table>\n");

        // d
        printf("<p>用户总数: %d</p>\n", $regTotal);

        echo "</body></html>\n";

        return false;
    }

    public function dau_by_langAction()
    {
        $request = $this->getRequest();

        $result = $langs = array();

        $redis = $this->getRedis();

        $from = mktime(0, 0, 0, 9, 1, 2015);
        $to = mktime(0, 0, 0, 10, 9, 2015);

        $current = $from;

        while ($current < $to) {
            $pattern = 'dau:*:' . date('Ymd', $current);
            $keys = $redis->keys($pattern);

            foreach ($keys as $key) {
                list($prefix, $lang, $date,) = explode(':', $key);

                $result[$date][$lang] = $redis->bitCount($key);

                if ($lang) {
                    $langs[$lang] = 1;
                }
            }

            $current = strtotime('+1 day', $current);
        }

        # Html
        echo "<html><head><meta charset=\"utf-8\"></head><body>\n";

        ## Table
        echo "<table border=\"1\">\n";

        ### Head
        echo "<tr>\n";
        echo "<th>Date</th>\n";
        foreach ($langs as $lang => $val) {
            echo "<th>{$lang}</th>\n";
        }
        echo "<tr>\n";

        ### Body
        foreach ($result as $date => $row) {
            echo "<tr>\n";
            echo "<th>{$date}</th>\n";

            foreach ($langs as $lang => $val) {
                printf("<td>%s</td>\n", isset($row[$lang]) ? $row[$lang] : 0);
            }

            echo "</tr>\n";
        }

        echo "</table>\n";

        echo "</body></html>\n";

        return false;
    }
}
