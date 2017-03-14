<?php
class ReportedController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'resolve'   => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $videoDb;

    protected $mkjogoDb;

    protected $passportDb;

    protected $accountDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getAccountDb()
    {
        if (empty($this->accountDb)) {
            $this->accountDb = Daemon::getDb('account-db', 'account-db');
        }

        return $this->accountDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getVideoDb();
        $this->getMkjogoDb();
        $this->getPassportDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $status = $request->get('status');
        if (is_numeric($status)) {
            $where[] = '`status`=' . (int) $status;
            $filter['status'] = $status;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $mkjogoReportedModel = new MySQL_Mkjogo_ReportedModel($this->mkjogoDb);
        $result = $mkjogoReportedModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/reported/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function resolveAction()
    {
        $request = $this->getRequest();
        $this->getMkjogoDb();

        $mkjogoReportedModel = new MySQL_Mkjogo_ReportedModel($this->mkjogoDb);

        if (($id = $request->get('id')) && ($reportInfo = $mkjogoReportedModel->getRow($id, array('target', 'module', 'type')))) {
            $affectedCount = $mkjogoReportedModel->resolve($reportInfo['target'], $reportInfo['module'], $reportInfo['type']);
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
                'data'  => array(
                    'affected'  => $affectedCount,
                ),
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/reported/list');

        return false;
    }
}