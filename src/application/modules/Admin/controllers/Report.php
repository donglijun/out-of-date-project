<?php
class ReportController extends AdminController
{
    protected $authActions = array(
        'regdaily'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'regweekly'     => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'regmonthly'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'dau'           => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'mau'           => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'daunow'        => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'summary'       => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'ounow'         => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $accountDb;

    protected $mkjogoDb;

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

    public function regdailyAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');

        $dateFrom = date('Ymd', strtotime($from));
        $dateTo = date('Ymd', strtotime($to));

        $reportRegistrationDailyModel = new MySQL_ReportRegistrationDailyModel($this->getMkjogoDb());
        $data = $reportRegistrationDailyModel->between($dateFrom, $dateTo);

        $this->_view->assign(array(
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
        ));
    }

    public function regweeklyAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');

        $dateFrom = date('YW', strtotime($from));
        $dateTo = date('YW', strtotime($to));

        $reportRegistrationWeeklyModel = new MySQL_ReportRegistrationWeeklyModel($this->getMkjogoDb());
        $data = $reportRegistrationWeeklyModel->between($dateFrom, $dateTo);

        $this->_view->assign(array(
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
        ));
    }

    public function regmonthlyAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');

        $dateFrom = date('Ym', strtotime($from));
        $dateTo = date('Ym', strtotime($to));

        $reportRegistrationMonthlyModel = new MySQL_ReportRegistrationMonthlyModel($this->getMkjogoDb());
        $data = $reportRegistrationMonthlyModel->between($dateFrom, $dateTo);

        $this->_view->assign(array(
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
        ));
    }

    public function dauAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');

        $dateFrom = date('Ymd', strtotime($from));
        $dateTo = date('Ymd', strtotime($to));

        $reportActiveUsersDailyModel = new MySQL_ReportActiveUsersDailyModel($this->getMkjogoDb());
        $data = $reportActiveUsersDailyModel->between($dateFrom, $dateTo);

        $this->_view->assign(array(
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
        ));
    }

    public function mauAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');

        $dateFrom = date('Ym', strtotime($from));
        $dateTo = date('Ym', strtotime($to));

        $reportActiveUsersMonthlyModel = new MySQL_ReportActiveUsersMonthlyModel($this->getMkjogoDb());
        $data = $reportActiveUsersMonthlyModel->between($dateFrom, $dateTo);

        $this->_view->assign(array(
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
        ));
    }

    public function ouAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');
        $lang = $request->get('lang', '');

        $dateFrom   = MySQL_ReportOnlineUsersModel::key(strtotime($from));
        $dateTo     =  MySQL_ReportOnlineUsersModel::key(strtotime($to));

        $reportOnlineUserModel = new MySQL_ReportOnlineUsersModel($this->getMkjogoDb());
        $data = $reportOnlineUserModel->betweenOnHourTime($dateFrom, $dateTo, $lang);

        $this->_view->assign(array(
            'langs' => $reportOnlineUserModel->getLangMap(),
            'from'  => $from,
            'to'    => $to,
            'lang'  => $lang,
            'data'  => $data,
        ));
    }

    public function daunowAction()
    {
        header('Content-Type: application/json; charset=utf-8');

        $result = array(
            'code'  => 200,
        );

        $dauModel = new Redis_DauModel();
        $result['data'] = $dauModel->count();

        echo json_encode($result);

        return false;
    }

    public function summaryAction()
    {
        $config = Yaf_Registry::get('config');
        $rowsetDr = $rowsetWr = $rowsetMr = $rowsetDau = $rowsetMau = $rowsetHau = $rowsetOu = $data = array();

        $rowsetDr = MySQL_ReportRegistrationDailyModel::getModel($this->getMkjogoDb())->getAll();
        foreach ($rowsetDr as $row) {
            $datetime = mktime(0, 0, 0, substr($row['date'], -4, 2), substr($row['date'], -2, 2), substr($row['date'], 0, 4));
            $data[] = array(
                $datetime * 1000,
                $row['increment'],
            );
        }
        $dr = json_encode($data, JSON_NUMERIC_CHECK);

        $data = array();
        $rowsetWr = MySQL_ReportRegistrationWeeklyModel::getModel($this->getMkjogoDb())->getAll();
        foreach ($rowsetWr as $row) {
            $ptime = strptime($row['date'] . '1', '%Y%W%u');
            $datetime = mktime(0, 0, 0, $ptime['tm_mon'] + 1, $ptime['tm_mday'], $ptime['tm_year'] + 1900);
            $data[] = array(
                $datetime * 1000,
                $row['increment'],
            );
        }
        $wr = json_encode($data, JSON_NUMERIC_CHECK);

        $data = array();
        $rowsetMr = MySQL_ReportRegistrationMonthlyModel::getModel($this->getMkjogoDb())->getAll();
        foreach ($rowsetMr as $row) {
            $datetime = mktime(0, 0, 0, substr($row['date'], -2, 2), 1, substr($row['date'], 0, 4));
            $data[] = array(
                $datetime * 1000,
                $row['increment'],
            );
        }
        $mr = json_encode($data, JSON_NUMERIC_CHECK);

        $data = array();
        $rowsetDau = MySQL_ReportActiveUsersDailyModel::getModel($this->getMkjogoDb())->getAll();
        foreach ($rowsetDau as $row) {
            $datetime = mktime(0, 0, 0, substr($row['date'], -4, 2), substr($row['date'], -2, 2), substr($row['date'], 0, 4));
            $data[] = array(
                $datetime * 1000,
                $row['total'],
            );
        }
        $dau = json_encode($data, JSON_NUMERIC_CHECK);

        $data = array();
        $rowsetMau = MySQL_ReportActiveUsersMonthlyModel::getModel($this->getMkjogoDb())->getAll();
        foreach ($rowsetMau as $row) {
            $datetime = mktime(0, 0, 0, substr($row['date'], -2, 2), 1, substr($row['date'], 0, 4));
            $data[] = array(
                $datetime * 1000,
                $row['total'],
            );
        }
        $mau = json_encode($data, JSON_NUMERIC_CHECK);

        $data = array();
        $rowsetHau = MySQL_ReportActiveUsersHourlyModel::getModel($this->getMkjogoDb())->getAll();
        foreach ($rowsetHau as $row) {
            $datetime = mktime(substr($row['date'], -2, 2) + 1, 0, 0, substr($row['date'], -6, 2), substr($row['date'], -4, 2), substr($row['date'], 0, 4));
            $data[] = array(
                $datetime * 1000,
                $row['increment'],
            );
        }
        $hau = json_encode($data, JSON_NUMERIC_CHECK);

        $data = $ou = array();
        $dateFrom = MySQL_ReportOnlineUsersModel::key(strtotime('-2 week'));
        $dateTo   = MySQL_ReportOnlineUsersModel::key();
        $rowsetOu = MySQL_ReportOnlineUsersModel::getModel($this->getMkjogoDb())->between($dateFrom, $dateTo);
        foreach ($rowsetOu as $row) {
            $datetime = mktime(substr($row['date'], -4, 2), substr($row['date'], -2, 2) * 5, 0, substr($row['date'], 4, 2), substr($row['date'], 6, 2), substr($row['date'], 0, 4));
            $data[$row['lang']][] = array(
                $datetime * 1000,
                $row['total'],
            );
        }
        ksort($data);
        foreach ($data as $key => $val) {
            $ou[$key] = json_encode($val, JSON_NUMERIC_CHECK);
        }

        $this->getView()->assign(array(
            'dr'    => $dr,
            'wr'    => $wr,
            'mr'    => $mr,
            'dau'   => $dau,
            'mau'   => $mau,
            'hau'   => $hau,
            'ou'    => $ou,
            'domain'    => isset($config->cookie->domain) ? $config->cookie->domain : 'mkjogo.com',
        ));
    }

    public function ounowAction()
    {
        header('Content-Type: application/json; charset=utf-8');

        $result = array(
            'code'  => 200,
            'data'  => array(),
        );
        $data = array();
        $datetime = 0;
        $timestamp = strtotime('-5 minute');

        $ouModel = new Redis_OuModel();

        foreach ($ouModel->count($timestamp) as $key => $val) {
            list($prefix, $lang, $dt,) = explode(':', $key);

            $data['langs'][$lang] = $val;

            if (!$datetime) {
                $datetime = mktime(substr($dt, -4, 2), substr($dt, -2, 2) * 5, 0, substr($dt, 4, 2), substr($dt, 6, 2), substr($dt, 0, 4));
                $data['dt'] = $datetime * 1000;
            }
        }

        $result['data'] = $data;

        echo json_encode($result);

        return false;
    }
}