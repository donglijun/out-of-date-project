<?php
class PointController extends AdminController
{
    protected $authActions = array(
        'list'     => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'log'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'recharge' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'balance'  => MySQL_AdminAccountModel::GROUP_DIRECTOR,
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

        $sort = '`id` ASC';
        $filter['page'] = '0page0';
        if ($user = $request->get('user')) {
            $where[] = "`id`=" . (int) $user;
            $filter['user'] = $user;
        }
        if ($request->get('order_by_number_desc')) {
            $filter['order_by_number_desc'] = 1;
            $sort = '`number` DESC';
        }
        $where = $where ? implode(' AND ', $where) : '';

        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
        $result = $pointAccountModel->search('*', $where, $sort, $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/point/list?' . http_build_query($filter);

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

        $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
        $result = $pointLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/point/log?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['typeMap'] = $pointLogModel->getTypeMap();

        $this->getView()->assign($result);
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
            $points = $request->get('points', 0);

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            if ($userAccountModel->exists($user)) {
                try {
                    $this->streamingDb->beginTransaction();

                    // Recharge
                    $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
                    $pointAccountModel->incr($user, $points);

                    // Point log
                    $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                    $pointLogModel->insert(array(
                        'user'     => $user,
                        'number'   => $points,
                        'type'     => MySQL_Point_LogModel::LOG_TYPE_RECHARGE,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    // Admin log
                    $logContent = array(
                        'user'   => $user,
                        'points' => $points,
                    );
                    Misc::adminLog(MySQL_AdminLogModel::OP_RECHARGE_POINT, $logContent);

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

    public function balanceAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        $this->getStreamingDb();
        $userid = $this->session->admin['user'];

        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
        if ($row = $pointAccountModel->getRow($userid)) {
            $result['data'] = $row['number'];

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }
}