<?php
class LogController extends AdminController
{
    protected $authActions = array(
        'list'  => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $mkjogoDb;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    public function listAction()
    {
        $result = $where = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $db = $this->getMkjogoDb();

        $filter['page'] = '0page0';
        if ($loggedOnFrom = $request->get('from', date('Y-m-d'))) {
            $where[] = '`logged_on`>=' . strtotime($loggedOnFrom);
            $filter['from'] = $loggedOnFrom;
        }

        if ($loggedOnTo = $request->get('to', date('Y-m-d', strtotime('+1 day')))) {
            $where[] = '`logged_on`<' . strtotime($loggedOnTo);
            $filter['to'] = $loggedOnTo;
        }

        if ($user = $request->get('user')) {
            $where[] = '`user`=' . $db->quote($user);
            $filter['user'] = $user;
        }

        $where = $where ? implode(' AND ', $where) : '';

        $adminLogModel = new MySQL_AdminLogModel($this->getMkjogoDb());
        $result = $adminLogModel->search('*', $where, 'id DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/log/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}