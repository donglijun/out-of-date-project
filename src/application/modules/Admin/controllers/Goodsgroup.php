<?php
class GoodsgroupController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
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

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('goodsgroup/edit.phtml'));
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

        $goodsGroupModel = new MySQL_Goods_GroupModel($this->streamingDb);
        $result = $goodsGroupModel->search('*', null, null, $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/goodsgroup/list?' . http_build_query($filter);

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
                'number'      => $request->get('number'),
                'title'       => $request->get('title', ''),
                'description' => $request->get('description', 0),
                'created_on'  => time(),
            );

            $goodsGroupModel = new MySQL_Goods_GroupModel($this->streamingDb);
            $goodsGroupModel->insert($data);

            $this->redirect('/admin/goodsgroup/list');

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
            $goodsGroupModel = new MySQL_Goods_GroupModel($this->streamingDb);

            if ($request->isPost()) {
                if ($number = $request->get('number')) {
                    $data['number'] = $number;
                }

                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if ($description = $request->get('description')) {
                    $data['description'] = $description;
                }

                if ($data) {
                    $affectedCount = $goodsGroupModel->update($id, $data);
                }

                $this->redirect('/admin/goodsgroup/list');

                return false;
            } else {
                $data = $goodsGroupModel->getRow($id);

                $this->_view->assign(array(
                    'id'    => $id,
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
            $goodsGroupModel = new MySQL_Goods_GroupModel($this->streamingDb);
            $affectedCount = $goodsGroupModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/goodsgroup/list');

        return false;
    }
}