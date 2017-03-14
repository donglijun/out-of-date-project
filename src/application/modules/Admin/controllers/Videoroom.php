<?php
use Aws\S3\S3Client;

class VideoroomController extends AdminController
{
    protected $authActions = array(
        'list'   => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'create' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $videoDb;

    protected $redisVideo;

    protected $s3;

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

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('videoroom/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

//        $this->getRedisVideo();
        $this->getVideoDb();
//        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $mkjogoVideoLink = new Mkjogo_Video_Link($this->videoDb, $this->redisVideo, $this->s3);

        $filter['page'] = '0page0';
        if ($room = $request->get('room')) {
            $where[] = "`id`=" . (int) $room;
            $filter['room'] = $room;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $videoRoomModel = new MySQL_Video_RoomModel($this->videoDb);
        $result = $videoRoomModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/videoroom/list?' . http_build_query($filter);

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

        $this->getVideoDb();

        if ($request->isPost() && ($id = $request->get('id', ''))) {
            $data = array(
                'id'  => $id,
            );

            if ($title = $request->get('title')) {
                $data['title'] = $title;
            }

            if ($bio = $request->get('bio')) {
                $data['bio'] = $bio;
            }

            $data['created_on'] = $request->getServer('REQUEST_TIME');

            $videoRoomModel = new MySQL_Video_RoomModel($this->videoDb);
            $videoRoomModel->insert($data);
            $videoRoomModel->resetStreamKey($id);

            $this->redirect('/admin/videoroom/list');

            return false;
        }

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
            $videoRoomModel = new MySQL_Video_RoomModel($this->videoDb);

            if ($request->isPost()) {
                if ($title = $request->get('title', '')) {
                    $data['title'] = $title;
                }

                if ($bio = $request->get('bio')) {
                    $data['bio'] = $bio;
                }

                if ($data) {
                    $affectedCount = $videoRoomModel->update($id, $data);
                }

                $this->redirect('/admin/videoroom/list');

                return false;
            } else {
                $data = $videoRoomModel->getRow($id);

                $this->getview()->assign(array(
                    'id'        => $id,
                ));
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

}