<?php
class PassportController extends CliController
{
    protected $mkjogoDb;

    protected $passportDb;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
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

    protected function checkPassportDb()
    {
        if (!$this->passportDb) {
            $this->getPassportDb();
        } else {
            try {
                $this->passportDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('passport-db');
                $this->passportDb = null;

                $this->getPassportDb();
            }
        }
    }


    public function reg_dailyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        $to     = mktime(0, 0, 0, $month, $day, $year);
        $from   = strtotime('-1 day', $to);
        $from2  = strtotime('-1 day', $from);

        $date   = date('Ymd', $from);
        $date2  = date('Ymd', $from2);

        $reportRegistrationDailyModel = new MySQL_ReportRegistrationDailyModel($this->getMkjogoDb());
        $data2 = $reportRegistrationDailyModel->getRow($date2, array('increment'));

        $userProfileModel = new MySQL_User_ProfileModel($this->getPassportDb());
        $data = array(
            'date'          => $date,
            'increment'     => $userProfileModel->getRegistrationCount($from, $to),
            'total'         => $userProfileModel->getRegistrationCount(null, $to),
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

    public function reg_weeklyAction()
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
        $to     = strtotime(sprintf('%d day', 1 - $weekday), $today);
        $from   = strtotime('-7 day', $to);
        $from2  = strtotime('-7 day', $from);

        $date   = date('oW', $from);
        $date2  = date('oW', $from2);

        $reportRegistrationWeeklyModel = new MySQL_ReportRegistrationWeeklyModel($this->getMkjogoDb());
        $data2 = $reportRegistrationWeeklyModel->getRow($date2, array('increment'));

        $userProfileModel = new MySQL_User_ProfileModel($this->getPassportDb());
        $data = array(
            'date'          => $date,
            'increment'     => $userProfileModel->getRegistrationCount($from, $to),
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

    public function reg_monthlyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);

        $to     = mktime(0, 0, 0, $month, 1, $year);
        $from   = strtotime('-1 month', $to);
        $from2  = strtotime('-1 month', $from);

        $date   = date('Ym', $from);
        $date2  = date('Ym', $from2);

        $reportRegistrationMonthlyModel = new MySQL_ReportRegistrationMonthlyModel($this->getMkjogoDb());
        $data2 = $reportRegistrationMonthlyModel->getRow($date2, array('increment'));

        $userProfileModel = new MySQL_User_ProfileModel($this->getPassportDb());
        $data = array(
            'date'          => $date,
            'increment'     => $userProfileModel->getRegistrationCount($from, $to),
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

}