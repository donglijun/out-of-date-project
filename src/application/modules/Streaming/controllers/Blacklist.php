<?php
class BlacklistController extends ApiController
{
    protected $authActions = array(
        'add',
        'remove',
        'getusersbychannel',
        'getchannelsbyuser',
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

        // Check parameters
        if (($channel = $request->get('channel')) && ($user = $request->get('user'))) {
//            $this->getAccountDb();
            $this->getPassportDb();
            $this->getStreamingDb();

//            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $streamingBlacklistModel = new MySQL_Streaming_BlacklistModel($this->streamingDb);

            // Check privileges
            if (($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid)) {
                // Check user exists
//                if ($userInfo = $mkjogoUserModel->getRow($user, array('username'))) {
                if ($userInfo = $userAccountModel->getRow($user, array('name'))) {
                    // Cannot ban editor and owner
                    if (($user != $channel) && !$streamingEditorModel->isMember($channel, $user) && !$streamingSupervisorModel->isMember($user)) {
                        try {
                            $streamingBlacklistModel->insert(array(
                                'channel'       => $channel,
                                'user'          => $user,
                                'name'          => $userInfo['name'],
                                'created_on'    => $request->getServer('REQUEST_TIME'),
                                'created_by'    => $userid,
                            ));

                            // Publish message
                            $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->getRedisChat());
                            $redisStreamingChatChannelModel->operateBan($channel, array(
                                'user'          => $user,
                                'user_name'     => $userInfo['name'],
                                'editor'        => $userid,
                                'editor_name'   => $currentUser['name'],
                            ));

                            $result['code'] = 200;
                        } catch (Exception $e) {
                            $result['code'] = 400;
                        }
                    } else {
                        $result['code'] = 403;
                    }
                } else {
                    $result['code'] = 404;
                }
            } else {
                $result['code'] = 403;
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


        if (($channel = $request->get('channel')) && ($user = $request->get('user'))) {
            $this->getStreamingDb();

            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $streamingBlacklistModel = new MySQL_Streaming_BlacklistModel($this->streamingDb);

            if (($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid)) {
                $result['count'] = $streamingBlacklistModel->remove($channel, array($user));
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

        if ($channel = $request->get('channel')) {
            $this->getStreamingDb();

            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingBlacklistModel = new MySQL_Streaming_BlacklistModel($this->streamingDb);

            if (($channel == $userid) || ($streamingEditorModel->isMember($channel, $userid))) {
                $where = '`channel`=' . (int) $channel;

                $searchResult = $streamingBlacklistModel->search('user,name', $where);

                $result['data'] = $searchResult['data'];
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
}