<?php
class VideotagController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $videoDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('videotag/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getVideoDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $group = $request->get('group');
        if ($group) {
            $where[] = '`group`=' . (int) $group;
            $filter['group'] = $group;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $videoTagModel = new MySQL_Video_TagModel($this->videoDb);
        $result = $videoTagModel->search('*', $where, '`name` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/videotag/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $videoTagGroupModel = new MySQL_Video_TagGroupModel($this->videoDb);
        $result['groups'] = $videoTagGroupModel->getAllRows();

        $this->getView()->assign($result);
    }

    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getVideoDb();

        if ($request->isPost()) {
            $data = array(
                'name'  => $request->get('name', ''),
            );

            if ($group = $request->get('group')) {
                $data['group'] = $group;
            }

            if ($description = $request->get('description')) {
                $data['description'] = $description;
            }

            $videoTagModel = new MySQL_Video_TagModel($this->videoDb);
            $videoTagModel->insert($data);

            $this->redirect('/admin/videotag/list');

            return false;
        }

        $videoTagGroupModel = new MySQL_Video_TagGroupModel($this->videoDb);
        $this->getView()->assign(array(
            'groups'    => $videoTagGroupModel->getAllRows(),
        ));

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getVideoDb();

        $id = $request->get('id', 0);
        if ($id) {
            $videoTagModel = new MySQL_Video_TagModel($this->videoDb);

            if ($request->isPost()) {
                $data = array(
                    'name'  => $request->get('name', ''),
                );

                if ($group = $request->get('group')) {
                    $data['group'] = $group;
                }

                if ($description = $request->get('description')) {
                    $data['description'] = $description;
                }

                $affectedCount = $videoTagModel->update($id, $data);

                $this->redirect('/admin/videotag/list');

                return false;
            } else {
                $data = $videoTagModel->getRow($id, $videoTagModel->getFields());
                $videoTagGroupModel = new MySQL_Video_TagGroupModel($this->videoDb);

                $this->_view->assign(array(
                    'id'        => $id,
                    'groups'    => $videoTagGroupModel->getAllRows(),
                ));
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

}