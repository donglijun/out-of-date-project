<?php
class GoldController extends AdminController
{
    protected $authActions = array(
        'list'                    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'log'                     => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'recharge_orders'         => MySQL_AdminAccountModel::GROUP_ADMIN,
        'view_recharge_order'     => MySQL_AdminAccountModel::GROUP_ADMIN,
        'balance'                 => MySQL_AdminAccountModel::GROUP_ADMIN,
        'recharge'                => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
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

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($user = $request->get('user')) {
            $where[] = "`id`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
        $result = $goldAccountModel->search('*', $where, '`id` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gold/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function logAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);
        $result = $goldLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gold/log?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['typeMap'] = $goldLogModel->getTypeMap();

        $this->getView()->assign($result);
    }

    public function recharge_ordersAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        switch ($status = $request->get('status', 'is_processed')) {
            case 'is_processed':
                $where[] = "`is_processed`=1";
                $filter['status'] = $status;
                break;
            case 'is_bad':
                $where[] = "`is_bad`=1";
                $filter['status'] = $status;
                break;
            default:
                $where[] = "`is_processed`=0";
                $filter['status'] = $status;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $goldRechargeOrderModel = new MySQL_Gold_RechargeOrderModel($this->streamingDb);
        $result = $goldRechargeOrderModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gold/recharge_orders?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gold/recharge-orders.phtml'));

        return false;
    }

    public function view_recharge_orderAction()
    {
        $data = array();
        $request = $this->getRequest();

        $id = $request->get('id', 0);

        if ($id) {
            $this->getStreamingDb();

            $goldRechargeOrderModel = new MySQL_Gold_RechargeOrderModel($this->streamingDb);
            $data = $goldRechargeOrderModel->getRow($id);
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gold/view-recharge-order.phtml'));

        return false;
    }

    public function balanceAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        $this->getStreamingDb();
        $userid = $this->session->admin['user'];

        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
        if ($row = $goldAccountModel->getRow($userid)) {
            $result['data'] = $row['recharge_num'];

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function rechargeAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        $this->getStreamingDb();
        $this->getPassportDb();
        $config = Yaf_Registry::get('config')->toArray();

        if ($request->isPost()) {
            $user = $request->get('user', 0);
            $golds = $request->get('golds', 0);

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            if ($userAccountModel->exists($user)) {
                try {
                    $this->streamingDb->beginTransaction();

                    // Recharge
                    $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
                    $goldAccountModel->recharge($user, $golds);

                    // Gold log
                    $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);
                    $goldLogModel->insert(array(
                        'user'     => $user,
                        'number'   => $golds,
                        'type'     => MySQL_Gold_LogModel::LOG_TYPE_RECHARGE,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    // Admin log
                    $logContent = array(
                        'user'  => $user,
                        'golds' => $golds,
                    );
                    Misc::adminLog(MySQL_AdminLogModel::OP_RECHARGE_GOLD, $logContent);

                    $this->streamingDb->commit();

                    $result['code'] = 200;
                    $result['message'] = 'ok';
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    Misc::log($e->getMessage(), Zend_Log::ERR);
                }

            } else {
                $result['code'] = 404;
                $result['message'] = 'Use not exists';
            }
        } else {
            $result['code'] = 404;
        }

        echo json_encode($result);

        return false;
    }
}