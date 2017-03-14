<?php
class StreamingwatchingtaskController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingwatchingtask/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);
        $result = $streamingWatchingTaskModel->search('*', null, '`level` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingwatchingtask/list?' . http_build_query($filter);

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

        $this->getStreamingDb();

        if ($request->isPost()) {
            $data = array(
                'level' => $request->get('level', 0),
                'created_on' => $request->getServer('REQUEST_TIME'),
            );

            if (!is_null($gifts = $request->get('gifts'))) {
                $data['gifts'] = $gifts;
            }

            if (!is_null($points = $request->get('points'))) {
                $data['points'] = $points;
            }

            if (!is_null($timer = $request->get('timer'))) {
                $data['timer'] = $timer;
            }

            try {
                $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);
                $streamingWatchingTaskModel->insert($data);

                $this->redirect('/admin/streamingwatchingtask/list');
            } catch (Exception $e) {
                echo $e->getMessage();
            }

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
            $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);

            if ($request->isPost()) {
                $data = array(
                    'level'  => $request->get('level', 0),
                );

                if (!is_null($gifts = $request->get('gifts'))) {
                    $data['gifts'] = $gifts;
                }

                if (!is_null($points = $request->get('points'))) {
                    $data['points'] = $points;
                }

                if (!is_null($timer = $request->get('timer'))) {
                    $data['timer'] = $timer;
                }

                try {
                    $affectedCount = $streamingWatchingTaskModel->update($id, $data);

                    $this->redirect('/admin/streamingwatchingtask/list');
                } catch (Exception $e) {
                    echo $e->getMessage();
                }

                return false;
            } else {
                $data = $streamingWatchingTaskModel->getRow($id, $streamingWatchingTaskModel->getFields());
                $this->_view->assign('id', $id);
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }
}