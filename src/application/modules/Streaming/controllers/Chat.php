<?php
class ChatController extends ApiController
{
    protected $authActions = array(
        'send',
        'gag',
        'remove_gag',
        'list_gag',
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisChat;

    protected $redisStreaming;

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

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function filterBadWords($val)
    {
        $this->getRedisChat();

        $redisStreamingBadWordModel = new Redis_Streaming_BadWordModel($this->redisChat);
        if ($words = $redisStreamingBadWordModel->get()) {
            $pattern = "#($words)#i";

            return preg_replace_callback($pattern, function ($matches) {
                return str_repeat('*', strlen($matches[1]));
            }, $val);
        } else {
            return $val;
        }
    }

    protected function filterBadWords2($val)
    {
        $this->getRedisChat();

        $redisStreamingBadWordModel = new Redis_Streaming_BadWordModel($this->redisChat);
        if (($words = $redisStreamingBadWordModel->get()) && (preg_match("#($words)#i", $val))) {
            $pattern = "#^($words)$#i";
            $val = preg_replace_callback($pattern, function ($matches) {
                return str_repeat('*', strlen($matches[1]));
            }, $val);

            $pattern = "#^($words)([^0-9a-z])#i";
            $val = preg_replace_callback($pattern, function ($matches) {
                return str_repeat('*', strlen($matches[1])) . $matches[2];
            }, $val);

            $pattern = "#([^0-9a-z])($words)$#i";
            $val = preg_replace_callback($pattern, function ($matches) {
                return $matches[1] . str_repeat('*', strlen($matches[2]));
            }, $val);

            $pattern = "#([^0-9a-z])($words)([^0-9a-z])#i";
            $val = preg_replace_callback($pattern, function ($matches) {
                return $matches[1] . str_repeat('*', strlen($matches[2])) . $matches[3];
            }, $val);
        }

        return $val;
    }

    public function sendAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();
        $this->getPassportDb();
        $this->getRedisChat();
        $this->getRedisStreaming();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
//        $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);
        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

        if (($channel = $request->get('channel')) && ($body = $request->get('body')) && ($streamingChannelModel->exists($channel))) {
            $streamingBlacklistModel = new MySQL_Streaming_BlacklistModel($this->streamingDb);
            $redisStreamingChatGagModel = new Redis_Streaming_Chat_GagModel($this->redisStreaming);

            if (!$streamingBlacklistModel->isMember($channel, $userid) && !$redisStreamingChatGagModel->check($channel, $userid)) {
                //Check frequency
                $chatSession = array();
                $sendIntervalLimit = $config['chat']['send_interval_limit'] ?: 5;

                if (!$this->session->offsetExists('chat') || (($chatSession = $this->session->chat) &&  ($request->getServer('REQUEST_TIME') - (int) $chatSession['last_send_at'] > $sendIntervalLimit - 1))) {
                    $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
                    $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
//                    $userInfo = $mkjogoUserModel->getRow($userid, array('username'));
                    $userInfo = $userAccountModel->getRow($userid, array('name'));
                    $data = array(
                        'body'      => $this->filterBadWords2(strip_tags($body)),
                        'from'      => array(
                            'id'        => $userid,
                            'name'      => $userInfo['name'],
                            'is_editor' => ($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid),
                        ),
                        'timestamp' => $request->getServer('REQUEST_TIME'),
                        'color'     => $request->get('color', 1),
                    );

                    $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                    $redisStreamingChatChannelModel->publishPublic($channel, $data);

                    $chatSession['last_send_at'] = $data['timestamp'];
                    $this->session->offsetSet('chat', $chatSession);

                    $result['code'] = 200;
                } else {
                    $result['code'] = 403;
                }
            } else {
                $result['code'] = 403;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function last_system_broadcastAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $streamingSystemBroadcastModel = new MySQL_Streaming_SystemBroadcastModel($this->streamingDb);
        if ($info = $streamingSystemBroadcastModel->getLast()) {
            $result['data'] = $info;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function gagAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        // Check parameters
        if (($channel = $request->get('channel')) && ($user = $request->get('user'))) {
            $expire = (int) $request->get('expire', 600);

            $this->getPassportDb();
            $this->getStreamingDb();
            $this->getRedisStreaming();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $redisStreamingChatGagModel = new Redis_Streaming_Chat_GagModel($this->redisStreaming);

            // Check privileges
            if (($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid)) {
                // Check user exists
                if ($userInfo = $userAccountModel->getRow($user, array('name'))) {
                    // Cannot ban editor and owner
                    if (($user != $channel) && !$streamingEditorModel->isMember($channel, $user) && !$streamingSupervisorModel->isMember($user)) {
                        try {
                            $expire = $redisStreamingChatGagModel->add($channel, $user, $expire);

                            $this->getRedisChat();
                            // Publish message
                            $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                            $redisStreamingChatChannelModel->operateGag($channel, array(
                                'user'          => $user,
                                'user_name'     => $userInfo['name'],
                                'editor'        => $userid,
                                'editor_name'   => $currentUser['name'],
                                'expire'        => $expire,
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
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function remove_gagAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        // Check parameters
        if (($channel = $request->get('channel')) && ($user = $request->get('user'))) {
            $this->getPassportDb();
            $this->getStreamingDb();
            $this->getRedisStreaming();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $redisStreamingChatGagModel = new Redis_Streaming_Chat_GagModel($this->redisStreaming);

            // Check privileges
            if (($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid)) {
                $redisStreamingChatGagModel->remove($channel, $user);

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

    public function list_gagAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        // Check parameters
        if ($channel = $request->get('channel')) {
            $this->getPassportDb();
            $this->getStreamingDb();
            $this->getRedisStreaming();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $redisStreamingChatGagModel = new Redis_Streaming_Chat_GagModel($this->redisStreaming);

            // Check privileges
            if (($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid)) {
                $data = $members = $names = array();

                $members = $redisStreamingChatGagModel->members($channel);
                $names = $userAccountModel->getRows(array_keys($members), array('name'));

                foreach ($names as $row) {
                    $data[] = array(
                        'user' => $row['id'],
                        'name' => $row['name'],
                        'expire' => $members[$row['id']],
                    );
                }

                $result['code'] = 200;
                $result['data'] = $data;
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