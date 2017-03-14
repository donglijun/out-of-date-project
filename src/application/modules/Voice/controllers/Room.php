<?php
use Aws\S3\S3Client;

class RoomController extends ApiController
{
    const DEFAULT_ROOM_ICON_SIZE = 100;

    protected $authActions = array(
        'create',
        'edit',
        'enter',
        'ban',
        'unban',
        'blacklist',
        'grantmanager',
        'revokemanager',
        'setoptions',
        'getoptions',
    );

    protected $voiceDb;

    protected $accountDb;

    protected $redisVoice;

    protected $s3;

    protected function getVoiceDb()
    {
        if (empty($this->voiceDb)) {
            $this->voiceDb = Daemon::getDb('voice-db', 'voice-db');
        }

        return $this->voiceDb;
    }

    protected function getAccountDb()
    {
        if (empty($this->accountDb)) {
            $this->accountDb = Daemon::getDb('account-db', 'account-db');
        }

        return $this->accountDb;
    }

    protected function getRedisVoice()
    {
        if (empty($this->redisVoice)) {
            $this->redisVoice = Daemon::getRedis('redis-voice', 'redis-voice');
        }

        return $this->redisVoice;
    }

    protected function getS3()
    {
        if (empty($this->s3)) {
            $config = Yaf_Registry::get('config')->toArray();

            $this->s3 = S3Client::factory(array(
                'key'       => $config['aws']['s3']['key'],
                'secret'    => $config['aws']['s3']['secret'],
                'region'    => $config['aws']['s3']['region'],
            ));
        }

        return $this->s3;
    }

    protected function getUserDetail($users)
    {
        $result = array();

        $this->getAccountDb();

        if (is_array($users) && $users) {
            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);
            $result = $mkjogoUserModel->getRows($users, array('user_id', 'username', 'nickname'));
        }

        return $result;
    }

    public function createAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $mkuser = Yaf_Registry::get('mkuser');
        $userid = $mkuser['userid'];

        if ($request->isPost()) {
            $this->getVoiceDb();
            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);

            $data = array(
                'id'            => $request->get('id'),
                'title'         => $request->get('title'),
                'creator'       => $userid,
                'created_on'    => $request->getServer('REQUEST_TIME'),
                'owner'         => $userid,
                'options'       => json_encode($voiceRoomModel->getDefaultOptions()),
            );
            $result['data'] = $voiceRoomModel->insert($data);
            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

    public function editAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $this->getVoiceDb();
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);
            $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);

            if ($room = $request->get('room')) {
                if ($voiceRoomModel->isOwner($room, $userid) || $voiceManagerModel->isMember($room, $userid)) {
                    $data = array();

                    if ($title = $request->get('title')) {
                        $data['title'] = $title;
                    }

                    if ($_FILES) {
                        $matches = array();

                        $s3 = $this->getS3();

                        foreach ($_FILES as $key => $val) {
                            if ((exif_imagetype($val['tmp_name']) !== false) && preg_match('|^icon_file_(\d+)$|', $key, $matches)) {
                                $size = (int) $matches[1];
                                //@todo validate size

                                $fext = strtolower(pathinfo($val['name'], PATHINFO_EXTENSION));
                                //@todo validate extension
                                $fname = sprintf('%d-%d.%s', $room, $size, $fext);

                                $return = $s3->putObject(array(
                                    'Bucket'        => AWS_S3_Bucket_VoiceRoomIconModel::BUCKET,
                                    'Key'           => $fname,
                                    'SourceFile'    => $val['tmp_name'],
                                    'ContentType'   => $val['type'],
                                    'Metadata'      => array(
                                        'size'  => $size,
                                    ),
                                    'ACL'           => 'public-read',
                                ));

                                $result['data'][$key] = $s3->getObjectUrl(AWS_S3_Bucket_VoiceRoomIconModel::BUCKET, $fname, null, array(
                                    'Scheme'    => 'http',
                                ));

                                $data['icon'] = $fext;
                            }
                        }
                    }

                    if ($data) {
                        $voiceRoomModel->update($room, $data);
                    }

                    if ($options = $request->get('options')) {
                        $voiceRoomModel->updateOptions($room, $options);
                    }

                    $result['code'] = 200;
                } else {
                    $result['code'] = 403;
                }
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function listAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $voiceRoomModel = new MySQL_Voice_RoomModel($this->getVoiceDb());
        $result = $voiceRoomModel->search('id,title,max_online,current_online,icon');

//        $config = Yaf_Registry::get('config')->toArray();
        $s3 = $this->getS3();
        $redisVoiceRoomOnlineModel = new Redis_Voice_Room_OnlineModel($this->getRedisVoice());

        foreach ($result['data'] as $key => $val) {
            if (isset($val['icon']) && $val['icon']) {
//                $roomIcon = new Mkjogo_Voice_Room_Icon($val['id'], $config['upload']['room-icon']);
//                $result['data'][$key]['icon'] = $roomIcon->url(array(static::DEFAULT_ROOM_ICON_SIZE, $val['id'] . '.' . $val['icon']));
                $fname = sprintf('%d-%d.%s', $val['id'], static::DEFAULT_ROOM_ICON_SIZE, $val['icon']);
//                $result['data'][$key]['icon'] = sprintf('//%s.s3-us-west-1.amazonaws.com/%s', AWS_S3_Bucket_VoiceRoomIconModel::BUCKET, $fname);
                $result['data'][$key]['icon'] = $s3->getObjectUrl(AWS_S3_Bucket_VoiceRoomIconModel::BUCKET, $fname, null, array(
                    'Scheme'    => 'http',
                ));
            }

            $result['data'][$key]['current_online'] = $redisVoiceRoomOnlineModel->getTotal($val['id']);
        }

        $result['code'] = 200;

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
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];
            $this->getVoiceDb();

            $voiceBlacklistModel = new MySQL_Voice_BlacklistModel($this->voiceDb);

            if (!$voiceBlacklistModel->isMember($room, $userid)) {
                $result['code'] = 200;

                // Get options
                $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);
                $row = $voiceRoomModel->getRow($room);

                $result['data'] = array(
                    'id'        => $row['id'],
                    'title'     => $row['title'],
                    'owner'     => $row['owner'],
                    'options'   => json_decode($row['options'], true),
                );

                if (isset($row['icon']) && $row['icon']) {
//                    $config = Yaf_Registry::get('config')->toArray();
//                    $roomIcon = new Mkjogo_Voice_Room_Icon($row['id'], $config['upload']['room-icon']);
//                    $result['data']['icon'] = $roomIcon->url(array(static::DEFAULT_ROOM_ICON_SIZE, $row['id'] . '.' . $row['icon']));
                    $fname = sprintf('%d-%d.%s', $row['id'], static::DEFAULT_ROOM_ICON_SIZE, $row['icon']);
                    $result['data']['icon'] = sprintf('//%s.s3-us-west-1.amazonaws.com/%s', AWS_S3_Bucket_VoiceRoomIconModel::BUCKET, $fname);
                }

                $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);

                if ($voiceRoomModel->isOwner($room, $userid) || $voiceManagerModel->isMember($room, $userid)) {
                    // Get blacklist
                    $result['data']['blacklist'] = $this->getUserDetail($voiceBlacklistModel->users($room));

                    // Get managers
                    $result['data']['managers'] = $this->getUserDetail($voiceManagerModel->users($room));
                }

                // Save history
                $redisVoiceRoomHistoryModel = new Redis_Voice_Room_HistoryModel($this->getRedisVoice());
                $redisVoiceRoomHistoryModel->update($userid, $room, $row['title']);
            } else {
                $result['code'] = 403;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function exitAction()
    {
        ;
        // opt_current_online--
    }

    public function authenticateAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if (($room = $request->get('room')) && ($password = $request->get('password'))) {
            $voiceRoomModel = new MySQL_Voice_RoomModel($this->getVoiceDb());
            $result['data'] = $voiceRoomModel->authenticate($room, $password);

            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

    public function followAction()
    {
        ;
    }

    public function banAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if (($room = $request->get('room')) && ($user = $request->get('user'))) {
            $this->getVoiceDb();
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);
            $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);

            if (($voiceRoomModel->isOwner($room, $userid) || $voiceManagerModel->isMember($room, $userid))
                && ($userid != $user) && !$voiceRoomModel->isOwner($room, $user) && !$voiceManagerModel->isMember($room, $user)) {
                try {
                    $voiceBlacklistModel = new MySQL_Voice_BlacklistModel($this->voiceDb);
                    $voiceBlacklistModel->insert(array(
                        'room'          => $room,
                        'user'          => $user,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                        'created_by'    => $userid,
                    ));

                    $result['code'] = 200;
                } catch (Exception $e) {
                    $result['code'] = 400;
                }

            } else {
                $result['code'] = 403;
            }
        }

        $this->callback($result);

        return false;
    }

    public function unbanAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if (($room = $request->get('room')) && ($users = Misc::parseIds($request->get('users')))) {
            $this->getVoiceDb();
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);
            $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);

            if ($voiceRoomModel->isOwner($room, $userid) || $voiceManagerModel->isMember($room, $userid)) {
                $voiceBlacklistModel = new MySQL_Voice_BlacklistModel($this->voiceDb);
                $result['count'] = $voiceBlacklistModel->remove($room, $users);

                $result['code'] = 200;
            } else {
                $result['code'] = 403;
            }
        }

        $this->callback($result);

        return false;
    }

    public function blacklistAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($room = $request->get('room')) {
            $this->getVoiceDb();
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);
            $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);

            if ($voiceRoomModel->isOwner($room, $userid) || $voiceManagerModel->isMember($room, $userid)) {
                $voiceBlacklistModel = new MySQL_Voice_BlacklistModel($this->getVoiceDb());
                $result['data'] = $this->getUserDetail($voiceBlacklistModel->users($room));

                $result['code'] = 200;
            } else {
                $result['code'] = 403;
            }
        }

        $this->callback($result);

        return false;
    }

    public function grantmanagerAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if (($room = $request->get('room')) && ($user = $request->get('user'))) {
            $this->getVoiceDb();
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);

            if ($voiceRoomModel->isOwner($room, $userid)) {
                $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);

                try {
                    $voiceManagerModel->insert(array(
                        'room'          => $room,
                        'user'          => $user,
                        'granted_on'    => $request->getServer('REQUEST_TIME'),
                        'granted_by'    => $userid,
                    ));

                    $result['code'] = 200;
                } catch (Exception $e) {
                    $result['code'] = 400;
                }
            } else {
                $result['code'] = 403;
            }
        }

        $this->callback($result);

        return false;
    }

    public function revokemanagerAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if (($room = $request->get('room')) && ($user = $request->get('user'))) {
            $this->getVoiceDb();
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);

            if ($voiceRoomModel->isOwner($room, $userid)) {
                $voiceManagerModel = new MySQL_Voice_ManagerModel($this->voiceDb);
                $voiceManagerModel->remove($room, $user);

                $result['code'] = 200;
            } else {
                $result['code'] = 403;
            }
        }

        $this->callback($result);

        return false;
    }

    public function setoptionsAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($room = $request->get('room')) {
            $voiceRoomModel = new MySQL_Voice_RoomModel($this->getVoiceDb());

            if ($voiceRoomModel->updateOptions($room, $request->get('options'))) {
                $result['code'] = 200;
            } else {
                $result['code'] = 400;
            }
        }

        $this->callback($result);

        return false;
    }

    public function getoptionsAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($room = $request->get('room')) {
            $voiceRoomModel = new MySQL_Voice_RoomModel($this->getVoiceDb());
            if ($row = $voiceRoomModel->getRow($room, array('options'))) {
                $result['data'] = json_decode($row['options'], true);

                $result['code'] = 200;
            }
        }

        $this->callback($result);

        return false;
    }

    public function historyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $userid = $request->getPost('userid', 0);

            $redisVoiceRoomHistoryModel = new Redis_Voice_Room_HistoryModel($this->getRedisVoice());
            $result['data'] = $redisVoiceRoomHistoryModel->getHistory($userid);

            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }
}