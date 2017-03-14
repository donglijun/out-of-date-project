<?php
class ReportController extends CliController
{
    protected $accountDb;

    protected $mkjogoDb;

    protected $sphinxDb;

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

    protected function checkMkjogoDb()
    {
        if (!$this->mkjogoDb) {
            $this->getMkjogoDb();
        } else {
            try {
                $this->mkjogoDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('mkjogo-db');
                $this->mkjogoDb = null;

                $this->getMkjogoDb();
            }
        }
    }

    protected function getSphinxDb()
    {
        if (empty($this->sphinxDb)) {
            $this->sphinxDb = Daemon::getSphinxQL('sphinxql-lol-plain', 'sphinxql-lol-plain');
        }

        return $this->sphinxDb;
    }

    public function regdailyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        $to     = mktime(0, 0, 0, $month, $day, $year);
//        $from   = mktime(0, 0, 0, $month, $day - 1, $year);
        $from   = strtotime('-1 day', $to);
//        $from2  = mktime(0, 0, 0, $month, $day - 2, $year);
        $from2  = strtotime('-1 day', $from);

        $date   = date('Ymd', $from);
        $date2  = date('Ymd', $from2);

        $reportRegistrationDailyModel = new MySQL_ReportRegistrationDailyModel($this->getMkjogoDb());
        $data2 = $reportRegistrationDailyModel->getRow($date2, array('increment'));

        $mkjogoUserModel    = new MySQL_MkjogoUserModel($this->getAccountDb());
        $data = array(
            'date'          => $date,
            'increment'     => $mkjogoUserModel->getRegistrationCount($from, $to),
            'total'         => $mkjogoUserModel->getRegistrationCount(null, $to),
            'updated_on'    => time(),
        );
        if (isset($data2['increment']) && $data2['increment']) {
            $data['growth_rate']    = ($data['increment'] - $data2['increment']) / abs($data2['increment']);
        }

        $this->checkMkjogoDb();
        $reportRegistrationDailyModel = new MySQL_ReportRegistrationDailyModel($this->mkjogoDb);

        $reportRegistrationDailyModel->insert($data);

        printf("Report registration daily: %d\n", $date);

        return false;
    }

    public function regweeklyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);
        /**
         * Week number of year, weeks starting from Monday
         */
        $week       = date('W', $timestamp);
        /**
         * Numeric representation of the day of the week, 1(for Monday) through 7(for Sunday)
         */
        $weekday    = date('N', $timestamp);

        $today  = mktime(0, 0, 0, $month, $day, $year);
//        $to     = mktime(0, 0, 0, $month, $day - $weekday + 1, $year);
        $to     = strtotime(sprintf('%d day', 1 - $weekday), $today);
//        $from   = mktime(0, 0, 0, $month, $day - $weekday - 6, $year);
        $from   = strtotime('-7 day', $to);
//        $from2  = mktime(0, 0, 0, $month, $day - $weekday - 13, $year);
        $from2  = strtotime('-7 day', $from);

        $date   = date('oW', $from);
        $date2  = date('oW', $from2);

        $reportRegistrationWeeklyModel = new MySQL_ReportRegistrationWeeklyModel($this->getMkjogoDb());
        $data2 = $reportRegistrationWeeklyModel->getRow($date2, array('increment'));

        $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());
        $data = array(
            'date'          => $date,
            'increment'     => $mkjogoUserModel->getRegistrationCount($from, $to),
            'updated_on'    => time(),
        );
        if (isset($data2['increment']) && $data2['increment']) {
            $data['growth_rate']    = ($data['increment'] - $data2['increment']) / abs($data2['increment']);
        }

        $this->checkMkjogoDb();
        $reportRegistrationWeeklyModel = new MySQL_ReportRegistrationWeeklyModel($this->mkjogoDb);

        $reportRegistrationWeeklyModel->insert($data);

        printf("Report registration weekly: %d\n", $date);

        return false;
    }

    public function regmonthlyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);

        $to     = mktime(0, 0, 0, $month, 1, $year);
//        $from   = mktime(0, 0, 0, $month - 1, 1, $year);
        $from   = strtotime('-1 month', $to);
//        $from2  = mktime(0, 0, 0, $month - 2, 1, $year);
        $from2  = strtotime('-1 month', $from);

        $date   = date('Ym', $from);
        $date2  = date('Ym', $from2);

        $reportRegistrationMonthlyModel = new MySQL_ReportRegistrationMonthlyModel($this->getMkjogoDb());
        $data2 = $reportRegistrationMonthlyModel->getRow($date2, array('increment'));

        $mkjogoUserModel    = new MySQL_MkjogoUserModel($this->getAccountDb());
        $data = array(
            'date'          => $date,
            'increment'     => $mkjogoUserModel->getRegistrationCount($from, $to),
            'updated_on'    => time(),
        );
        if (isset($data2['increment']) && $data2['increment']) {
            $data['growth_rate']    = ($data['increment'] - $data2['increment']) / ($data2['increment']);
        }

        $this->checkMkjogoDb();
        $reportRegistrationMonthlyModel = new MySQL_ReportRegistrationMonthlyModel($this->mkjogoDb);

        $reportRegistrationMonthlyModel->insert($data);

        printf("Report registration monthly: %d\n", $date);

        return false;
    }

    public function dauAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();

        $from   = strtotime('-1 day', $timestamp);
        $from2  = strtotime('-1 day', $from);

        $date   = date('Ymd', $from);
        $date2  = date('Ymd', $from2);

        $reportActiveUsersDailyModel = new MySQL_ReportActiveUsersDailyModel($this->getMkjogoDb());
        $data2 = $reportActiveUsersDailyModel->getRow($date2, array('total', 'increment'));

        $dauModel = new Redis_DauModel();
        $data = array(
            'date'          => $date,
            'total'         => $dauModel->count($from),
            'updated_on'    => time(),
        );
        $data['increment'] = isset($data2['total']) ? $data['total'] - $data2['total'] : $data['total'];
        if (isset($data2['increment']) && $data2['increment']) {
            $data['growth_rate']    = ($data['increment'] - $data2['increment']) / abs($data2['increment']);
        }

        $reportActiveUsersDailyModel->insert($data);

        printf("Report active users daily: %d\n", $date);

        return false;
    }

    public function cleardauAction()
    {
        $day = $this->getRequest()->get('day', 180);
        $day = $day > 0 ? $day * -1 : $day;

        $from = 0;
        $to   = strtotime(sprintf('%d day', $day));

        $dauModel = new Redis_DauModel();
        $dauModel->clear($from, $to);

        printf("Clear daily-active-users report monthly (%d days before): %s\n", $day, date('Y-m-d', $from));

        return false;
    }

    public function mauAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        $to     = mktime(0, 0, 0, $month, $day, $year);
        $from   = strtotime('-1 month', $to);
        $from2  = strtotime('-1 month', $from);

        $date   = date('Ym', $from);
        $date2  = date('Ym', $from2);

        $reportActiveUsersMonthlyModel = new MySQL_ReportActiveUsersMonthlyModel($this->getMkjogoDb());
        $data2 = $reportActiveUsersMonthlyModel->getRow($date2, array('total', 'increment'));

        $mauModel = new Redis_MauModel();
        $data = array(
            'date'          => $date,
            'total'         => $mauModel->count($from),
            'updated_on'    => time(),
        );
        $data['increment'] = isset($data2['total']) ? $data['total'] - $data2['total'] : $data['total'];
        if (isset($data2['increment']) && $data2['increment']) {
            $data['growth_rate']    = ($data['increment'] - $data2['increment']) / abs($data2['increment']);
        }

        $reportActiveUsersMonthlyModel->insert($data);

        printf("Report active users monthly: %d\n", $date);

        return false;
    }

    public function clearmauAction()
    {
        $month = $this->getRequest()->get('month', 6);
        $month = $month > 0 ? $month * -1 : $month;

        $from = 0;
        $to   = strtotime(sprintf('%d month', $month));

        $mauModel = new Redis_MauModel();
        $mauModel->clear($from, $to);

        printf("Clear monthly-active-users report monthly (%d months before): %s\n", $month, date('Y-m-d', $from));

        return false;
    }

    public function hauAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();

        $from   = strtotime('-1 hour', $timestamp);

        $date   = date('YmdH', $from);

        $dauModel = new Redis_DauModel();
        $reportActiveUsersHourlyModel = new MySQL_ReportActiveUsersHourlyModel($this->getMkjogoDb());

        if (date('G') == 1) {
            $data = array(
                'date'          => $date,
                'total'         => $dauModel->count(),
                'updated_on'    => time(),
            );

            $data['increment']  = $data['total'];
        } else {
            $from2  = strtotime('-1 hour', $from);
            $date2  = date('YmdH', $from2);

            $data2 = $reportActiveUsersHourlyModel->getRow($date2, array('total', 'increment'));

            $data = array(
                'date'          => $date,
                'total'         => $dauModel->count($from),
                'updated_on'    => time(),
            );

            $data['increment'] = isset($data2['total']) ? $data['total'] - $data2['total'] : $data['total'];

            if (isset($data2['increment']) && $data2['increment']) {
                $data['growth_rate']    = ($data['increment'] - $data2['increment']) / abs($data2['increment']);
            }
        }

        $reportActiveUsersHourlyModel->insert($data);

        printf("Report active users hourly: %d\n", $date);

        return false;
    }

    public function ouAction()
    {
        $data = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();

        $from   = strtotime('-5 minute', $timestamp);

        $date   = date('YmdHi', $from);

        $ouModel = new Redis_OuModel();

        $counts = $ouModel->count($from);

        $reportOnlineUserModel = new MySQL_ReportOnlineUsersModel($this->getMkjogoDb());

        foreach ($counts as $key => $val) {
            list($prefix, $lang, $dt,) = explode(':', $key);

            $data = array(
                'date'          => $dt,
                'lang'          => $lang,
                'total'         => $val,
                'updated_on'    => $this->getRequest()->getServer('REQUEST_TIME'),
            );

            $reportOnlineUserModel->insert($data);
        }

        printf("Report online users: %d\n", $date);

        return false;
    }

    public function lol_champion_weeklyAction()
    {
        $data = array();

        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $timestamp  = $request->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        /**
         * Numeric representation of the day of the week, 1(for Monday) through 7(for Sunday)
         */
        $weekday    = date('N', $timestamp);

        $today  = mktime(0, 0, 0, $month, $day, $year);
        $to     = strtotime(sprintf('%d day', 1 - $weekday), $today);
        $from   = strtotime('-7 day', $to);

        $date   = date('oW', $from);

        $microFrom  = $from * 1000;
        $microTo    = $to * 1000;

        try {
            $this->getMkjogoDb();

            $sphinxqlLOLMatchModelClass = sprintf('SphinxQL_LOL_Match_%sModel', $platform);
            $sphinxqlLOLMatchModel = new $sphinxqlLOLMatchModelClass($this->getSphinxDb());

            $sphinxqlLOLChampionPickBanModelClass = sprintf('SphinxQL_LOL_Champion_PickBan_%sModel', $platform);
            $sphinxqlLOLChampionPickBanModel = new $sphinxqlLOLChampionPickBanModelClass($this->getSphinxDb());

            $rankedTotalByMode = $summary = array();

            $opts = array(
                'max_matches'   => 1000,
            );

            // Total ranked
            $params = array(
                'select'    => '`mode`, COUNT(*) AS `group_count`',
                'filter'    => '`map`>0',
                'range'     => "`created_on`>={$from} AND `created_on`<{$to}",
                'groupby'   => '`mode`',
                'offset'    => 0,
                'limit'     => 1000,
            );

            $result = $sphinxqlLOLChampionPickBanModel->search($params, $opts);

            foreach ($result['matches'] as $row) {
                $rankedTotalByMode[$row['mode']] = $row['group_count'];
            }

            if (empty($rankedTotalByMode)) {
                printf("No ranked data in *%s* weekly: %d\n", $platform, $date);

                return false;
            }

            // Win rate by champions
            $params = array(
                'select'    => '`champion`,`mode`, COUNT(*) AS `total`, SUM(`win`) AS `wins`',
                'range'     => "`created_on`>={$from} AND `created_on`<{$to}",
                'groupby'   => '`champion`,`mode`',
                'offset'    => 0,
                'limit'     => 1000,
            );

            $result = $sphinxqlLOLMatchModel->search($params, $opts);

            foreach ($result['matches'] as $row) {
                $summary[$row['champion']][$row['mode']] = array(
                    'total' => $row['total'],
                    'win'   => $row['wins'],
                    'lose'  => $row['total'] - $row['wins'],
                    'win_rate'  => $row['wins'] / $row['total'],
                );
            }

            // Pick rate by champions
            foreach ($rankedTotalByMode as $key => $val) {
                $params = array(
                    'select'    => 'GROUPBY() AS `champion`, COUNT(*) AS `group_count`',
                    'filter'    => "`map`>0 AND `mode`={$key}",
                    'range'     => "`created_on`>={$from} AND `created_on`<{$to}",
                    'groupby'   => '`pick`',
                    'sort'      => '`group_count` DESC',
                    'offset'    => 0,
                    'limit'     => 1000,
                );

                $result = $sphinxqlLOLChampionPickBanModel->search($params, $opts);

                foreach ($result['matches'] as $row) {
                    $summary[$row['champion']][$key]['ranked_total']        = $val;
                    $summary[$row['champion']][$key]['ranked_pick']         = $row['group_count'];
                    $summary[$row['champion']][$key]['ranked_pick_rate']    = $row['group_count'] / $val;
                }
            }

            // Ban rate by champions
            foreach ($rankedTotalByMode as $key => $val) {
                $params = array(
                    'select'    => 'GROUPBY() AS `champion`, COUNT(*) AS `group_count`',
                    'filter'    => "`map`>0 AND `mode`={$key}",
                    'range'     => "`created_on`>={$from} AND `created_on`<{$to}",
                    'groupby'   => '`ban`',
                    'sort'      => '`group_count` DESC',
                    'offset'    => 0,
                    'limit'     => 1000,
                );

                $result = $sphinxqlLOLChampionPickBanModel->search($params, $opts);

                foreach ($result['matches'] as $row) {
                    $summary[$row['champion']][$key]['ranked_total']    = $val;
                    $summary[$row['champion']][$key]['ranked_ban']      = $row['group_count'];
                    $summary[$row['champion']][$key]['ranked_ban_rate'] = $row['group_count'] / $val;
                }
            }

            $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
            $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->mkjogoDb);

            foreach ($summary as $champion => $row1) {
                foreach ($row1 as $mode => $row2) {
                    $row2['date']       = $date;
                    $row2['champion']   = $champion;
                    $row2['mode']       = $mode;
                    $row2['updated_on'] = $request->getServer('REQUEST_TIME');

                    $reportLOLChampionWeeklyModel->replace($row2);
                }
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }

        printf("Report LOL champion in *%s* weekly: %d\n", $platform, $date);

        return false;
    }

    public function lol_champion_monthlyAction()
    {
        $data = array();

        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $timestamp  = $request->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);

        $to     = mktime(0, 0, 0, $month, 1, $year);
        $from   = strtotime('-1 month', $to);

        $date   = date('Ym', $from);

        $microFrom  = $from * 1000;
        $microTo    = $to * 1000;

        $this->getMkjogoDb();

        $sphinxqlLOLMatchModelClass = sprintf('SphinxQL_LOL_Match_%sModel', $platform);
        $sphinxqlLOLMatchModel = new $sphinxqlLOLMatchModelClass($this->getSphinxDb());

        $sphinxqlLOLChampionPickBanModelClass = sprintf('SphinxQL_LOL_Champion_PickBan_%sModel', $platform);
        $sphinxqlLOLChampionPickBanModel = new $sphinxqlLOLChampionPickBanModelClass($this->getSphinxDb());

        $rankedTotalByMode = $summary = array();

        $opts = array(
            'max_matches'   => 1000,
        );

        // Total ranked
        $params = array(
            'select'    => '`mode`, COUNT(*) AS `group_count`',
            'filter'    => '`map`>0',
            'range'     => "`start`>={$microFrom} AND `start`<{$microTo}",
            'groupby'   => '`mode`',
            'offset'    => 0,
            'limit'     => 1000,
        );

        $result = $sphinxqlLOLChampionPickBanModel->search($params, $opts);

        foreach ($result['matches'] as $row) {
            $rankedTotalByMode[$row['mode']] = $row['group_count'];
        }

        if (empty($rankedTotalByMode)) {
            printf("No ranked data in *%s* monthly: %d\n", $platform, $date);

            return false;
        }

        // Win rate by champions
        $params = array(
            'select'    => '`champion`,`mode`, COUNT(*) AS `total`, SUM(`win`) AS `wins`',
            'range'     => "`start`>={$microFrom} AND `start`<{$microTo}",
            'groupby'   => '`champion`,`mode`',
            'offset'    => 0,
            'limit'     => 1000,
        );

        $result = $sphinxqlLOLMatchModel->search($params, $opts);

        foreach ($result['matches'] as $row) {
            $summary[$row['champion']][$row['mode']] = array(
                'total' => $row['total'],
                'win'   => $row['wins'],
                'lose'  => $row['total'] - $row['wins'],
                'win_rate'  => $row['wins'] / $row['total'],
            );
        }

        // pick rate by champions
        foreach ($rankedTotalByMode as $key => $val) {
            $params = array(
                'select'    => 'GROUPBY() AS `champion`, COUNT(*) AS `group_count`',
                'filter'    => "`map`>0 AND `mode`={$key}",
                'range'     => "`start`>={$microFrom} AND `start`<{$microTo}",
                'groupby'   => '`pick`',
                'sort'      => '`group_count` DESC',
                'offset'    => 0,
                'limit'     => 1000,
            );

            $result = $sphinxqlLOLChampionPickBanModel->search($params, $opts);

            foreach ($result['matches'] as $row) {
                $summary[$row['champion']][$key]['ranked_total']        = $val;
                $summary[$row['champion']][$key]['ranked_pick']         = $row['group_count'];
                $summary[$row['champion']][$key]['ranked_pick_rate']    = $row['group_count'] / $val;
            }
        }

        // Ban rate by champions
        foreach ($rankedTotalByMode as $key => $val) {
            $params = array(
                'select'    => 'GROUPBY() AS `champion`, COUNT(*) AS `group_count`',
                'filter'    => "`map`>0 AND `mode`={$key}",
                'range'     => "`start`>={$microFrom} AND `start`<{$microTo}",
                'groupby'   => '`ban`',
                'sort'      => '`group_count` DESC',
                'offset'    => 0,
                'limit'     => 1000,
            );

            $result = $sphinxqlLOLChampionPickBanModel->search($params, $opts);

            foreach ($result['matches'] as $row) {
                $summary[$row['champion']][$key]['ranked_total']    = $val;
                $summary[$row['champion']][$key]['ranked_ban']      = $row['group_count'];
                $summary[$row['champion']][$key]['ranked_ban_rate'] = $row['group_count'] / $val;
            }
        }

        $reportLOLChampionMonthlyModelClass = sprintf('MySQL_Report_LOL_Champion_Monthly_%sModel', $platform);
        $reportLOLChampionMonthlyModel = new $reportLOLChampionMonthlyModelClass($this->mkjogoDb);

        foreach ($summary as $champion => $row1) {
            foreach ($row1 as $mode => $row2) {
                $row2['date']       = $date;
                $row2['champion']   = $champion;
                $row2['mode']       = $mode;
                $row2['updated_on'] = $request->getServer('REQUEST_TIME');

                $reportLOLChampionMonthlyModel->replace($row2);
            }
        }

        printf("Report LOL champion in *%s* monthly: %d\n", $platform, $date);

        return false;
    }
}