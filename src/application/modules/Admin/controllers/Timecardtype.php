<?php
class TimecardtypeController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
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

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('timecardtype/edit.phtml'));
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

        $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);
        $result = $cardTypeModel->search('*', null, null, $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/timecardtype/list?' . http_build_query($filter);

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
                'title' => $request->get('title', ''),
                'price' => $request->get('price', 0),
                'game'  => $request->get('game', 0),
            );

            $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);
            $cardTypeModel->insert($data);

            $this->redirect('/admin/timecardtype/list');

            return false;
        }

        $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);
        $this->_view->assign(array(
            'games' => $streamingGameModel->getGameMap(),
        ));

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
            $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);

            if ($request->isPost()) {
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if ($price = $request->get('price')) {
                    $data['price'] = $price;
                }

                if ($game = $request->get('game')) {
                    $data['game'] = $game;
                }

                if ($data) {
                    $affectedCount = $cardTypeModel->update($id, $data);
                }

                $this->redirect('/admin/timecardtype/list');

                return false;
            } else {
                $data = $cardTypeModel->getRow($id, $cardTypeModel->getFields());

                $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);
                $this->_view->assign(array(
                    'id'    => $id,
                    'games' => $streamingGameModel->getGameMap(),
                ));
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
        $this->getStreamingDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);
            $affectedCount = $cardTypeModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/timecardtype/list');

        return false;
    }
}