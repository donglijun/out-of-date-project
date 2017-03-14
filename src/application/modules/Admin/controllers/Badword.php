<?php
class BadwordController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'update'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'delete'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $mkjogoDb;

    protected $redisChat;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    protected function updateCache()
    {
        $this->getMkjogoDb();
        $this->getRedisChat();

        $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
        if ($words = $mkjogoBadwordModel->getAllWords()) {
            $words = implode('|', array_map(function ($val) {
                return preg_quote($val, '#');
            }, $words));
        } else {
            $words = '';
        }

        $redisStreamingBadWordModel = new Redis_Streaming_BadWordModel($this->redisChat);
        return $redisStreamingBadWordModel->set($words);
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('badword/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getMkjogoDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        if ($keyword = $request->get('keyword')) {
            $where[] = sprintf("`content` LIKE %s", $this->mkjogoDb->quote('%' . $keyword . '%'));
            $filter['keyword'] = $keyword;
        }

        $where = $where ? implode(' AND ', $where) : '';

        $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
        $result = $mkjogoBadwordModel->search('*', $where, '`content` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/badword/list?' . http_build_query($filter);

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

        $this->getMkjogoDb();

        if ($request->isPost()) {
            $data = array(
                'content'   => $request->get('content', ''),
            );

            $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
            try {
                $mkjogoBadwordModel->insert($data);

                $this->updateCache();
            } catch (Exception $e) {
                ;
            }

            $this->redirect('/admin/badword/list');

            return false;
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getMkjogoDb();

        $id = $request->get('id', 0);
        if ($id) {
            $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);

            if ($request->isPost()) {
                $data = array(
                    'content'   => $request->get('content', ''),
                );

                $affectedCount = $mkjogoBadwordModel->update($id, $data);

                $this->updateCache();

                $this->redirect('/admin/badword/list');

                return false;
            } else {
                $data = $mkjogoBadwordModel->getRow($id);
                $this->_view->assign('id', $id);
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getMkjogoDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
            $affectedCount = $mkjogoBadwordModel->delete($ids);

            $this->updateCache();
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/badword/list');

        return false;
    }

    public function quickaddAction()
    {
        $request = $this->getRequest();
        $this->getMkjogoDb();

        $result = array(
            'code'  => 500,
        );

        if ($content = $request->get('content')) {
            $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
            try {
                $mkjogoBadwordModel->insert(array(
                    'content'   => $content,
                ));

                $this->updateCache();
            } catch (Exception $e) {
                ;
            }

            $result['code'] = 200;
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($result);

        return false;
    }
}