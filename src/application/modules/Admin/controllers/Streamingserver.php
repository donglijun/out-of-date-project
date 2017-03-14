<?php
class StreamingserverController extends AdminController
{
    protected $authActions = array(
        'list'   => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'create' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $streamingDb;

    protected $redisStreaming;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingserver/edit.phtml'));
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
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $where = $where ? implode(' AND ', $where) : '';

        $streamingServerModel = new MySQL_Streaming_ServerModel($this->streamingDb);
        $result = $streamingServerModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingserver/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();


        if ($request->isPost()) {
            $this->getStreamingDb();

            $data = array(
                'name'          => $request->get('name', ''),
                'ip'            => $request->get('ip', ''),
                'port'          => (int) $request->get('port', 0),
                'weight'        => min((int) $request->get('weight', 0), 10),
                'created_on'    => $request->getServer('REQUEST_TIME'),
            );

            $streamingServerModel = new MySQL_Streaming_ServerModel($this->getStreamingDb());
            $streamingServerModel->insert($data);

            $this->redirect('/admin/streamingserver/list');

            return false;
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        $id = $request->get('id', 0);
        if ($id) {
            $streamingServerModel = new MySQL_Streaming_ServerModel($this->getStreamingDb());

            if ($request->isPost()) {
                $data = array(
                    'name'          => $request->get('name', ''),
                    'ip'            => $request->get('ip', ''),
                    'port'          => (int) $request->get('port', 0),
                    'weight'        => min((int) $request->get('weight', 0), 10),
                );

                $affectedCount = $streamingServerModel->update($id, $data);

                $this->redirect('/admin/streamingserver/list');

                return false;
            } else {
                $data = $streamingServerModel->getRow($id);
                $this->_view->assign('id', $id);
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function updatecacheAction()
    {
        $request = $this->getRequest();

        $streamingServerModel = new MySQL_Streaming_ServerModel($this->getStreamingDb());
        $redisStreamingServerWeightingModel = new Redis_Streaming_Server_WeightingModel($this->getRedisStreaming());

        if ($weightings = $streamingServerModel->getWeightings()) {
            $data = array();

            foreach ($weightings as $row) {
                for ($i = 0; $i < $row['weight']; $i++) {
                    $data[] = $row['ip'];
                }
            }

            shuffle($data);

            $redisStreamingServerWeightingModel->update($data);

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingserver/list');

        return false;
    }
}