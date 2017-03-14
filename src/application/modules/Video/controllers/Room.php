<?php
use Aws\S3\S3Client;

class RoomController extends ApiController
{
    protected $authActions = array(
        'create',
        'resetkey',
    );

    protected $videoDb;

    protected $s3;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    public function createAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost()) {
            $this->getVideoDb();
            $videoRoomModel = new MySQL_Video_RoomModel($this->videoDb);

            $data = array(
                'id'            => $userid,
                'title'         => $request->get('title'),
                'bio'           => $request->get('bio'),
                'created_on'    => $request->getServer('REQUEST_TIME'),
            );
            if ($room = $videoRoomModel->insert($data)) {
                $result['data'] = array(
                    'room'          => $room,
                    'stream_key'    => $videoRoomModel->resetStreamKey($room),
                );
                $result['code'] = 200;
            }
        }

        $this->callback($result);

        return false;
    }

    public function enterAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($room = $request->get('room')) {
//            $mkuser = Yaf_Registry::get('mkuser');
//            $userid = $mkuser['userid'];
            $currentUser = Yaf_Registry::get('user');
            $userid = $currentUser['id'];

            $this->getVideoDb();

            $videoRoomModel = new MySQL_Video_RoomModel($this->videoDb);
            if ($row = $videoRoomModel->getRow($room)) {
                $result['data'] = array(
                    'id'            => $row['id'],
                    'title'         => $row['title'],
                    'bio'           => $row['bio'],
                    'stream_key'    => MySQL_Video_RoomModel::makeStreamKey($room, $row['stream_key']),
                );
                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function resetkeyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getVideoDb();
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $videoRoomModel = new MySQL_Video_RoomModel($this->videoDb);

        if ($streamKey = $videoRoomModel->resetStreamKey($userid)) {
            $result['code'] = 200;
            $result['data'] = $streamKey;
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }
}