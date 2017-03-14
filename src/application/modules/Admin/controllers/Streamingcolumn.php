<?php
class StreamingcolumnController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
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

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingcolumn/edit.phtml'));
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

        $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);
        $result = $streamingColumnModel->search('*', null, null, $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingcolumn/list?' . http_build_query($filter);

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
                'name'          => $request->get('name', ''),
            );

            if ($description = $request->get('description')) {
                $data['description'] = $description;
            }

            $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);
            $streamingColumnModel->insert($data);

            $this->redirect('/admin/streamingcolumn/list');

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
            $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);

            if ($request->isPost()) {
                $data = array(
                    'name'  => $request->get('name', ''),
                );

                if ($description = $request->get('description')) {
                    $data['description'] = $description;
                }

                $affectedCount = $streamingColumnModel->update($id, $data);

                $this->redirect('/admin/streamingcolumn/list');

                return false;
            } else {
                $data = $streamingColumnModel->getRow($id, $streamingColumnModel->getFields());
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
        $this->getStreamingDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);
            $affectedCount = $streamingColumnModel->delete($ids);

            $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
            foreach ($ids as $id) {
                $streamingColumnItemModel->deleteByColumn($id);
            }
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingcolumn/list');

        return false;
    }
}