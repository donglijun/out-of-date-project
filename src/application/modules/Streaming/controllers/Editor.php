<?php
class EditorController extends ApiController
{
    protected $authActions = array(
        'add',
        'remove',
        'getusersbychannel',
        'getchannelsbyuser',
        'check',
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisChat;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    public function addAction()
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
            $this->getPassportDb();
            $this->getStreamingDb();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);

//            if (($user = $request->get('editor_id')) && ($userInfo = $mkjogoUserModel->getRow($user, array('username')))) {
            if (($user = $request->get('editor_id')) && ($userInfo = $userAccountModel->getRow($user, array('name')))) {
                try {
                    $data = array(
                        'channel'       => $userid,
                        'user'          => $user,
                        'name'          => $userInfo['name'],
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );

                    $streamingEditorModel->insert($data);

                    // Publish message
                    $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->getRedisChat());
                    $redisStreamingChatChannelModel->operateGrant($userid, array(
                        'editor'        => $user,
                        'editor_name'   => $userInfo['name'],
                    ));

                    $result['code'] = 200;
                } catch (Exception $e) {
                    $result['code'] = 400;
                }
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function removeAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($user = $request->get('editor_id')) {
            if ($user != $userid) {
                $this->getStreamingDb();

                $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);

                $streamingEditorModel->remove($userid, $user);

                $result['code'] = 200;
            } else {
                $result['code'] = 403;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function getusersbychannelAction()
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
            $this->getStreamingDb();

            $where = '`channel`=' . $userid;

            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $searchResult = $streamingEditorModel->search('user,name', $where);

            $result['data'] = $searchResult['data'];
            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

    public function getchannelsbyuserAction()
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
            $this->getStreamingDb();

            $channels = $data = array();

            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);

            if ($channels = $streamingEditorModel->channels($userid)) {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                $data = $streamingChannelModel->getRows($channels, array('title', 'owner_name'));
            }

            $result['data'] = $data;
            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

    public function checkAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost() && ($channel = $request->get('channel'))) {
            $this->getStreamingDb();
            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);

            $result['data'] = ($channel == $userid) || $streamingEditorModel->isMember($channel, $userid);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}