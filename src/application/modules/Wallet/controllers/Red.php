<?php
class RedController extends ApiController
{
    protected $authActions = array(
        'publish',
        'open',
        'mysend',
        'myget',
        'detail',
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

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

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    protected function fixUncompletedOpen($red, $broadcastChannel = null)
    {
        $this->getStreamingDb();
        $this->getPassportDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);

        if ($redInfo = $redRedModel->getRow($red)) {
            $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);
            $redLogModel = new MySQL_Red_LogModel($this->streamingDb);
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            $completed = array();

            $consumed = $redisRedRedModel->consumedAll($red);
            $members = $redLogModel->members($red);

            foreach ($members as $member) {
                $completed[] = $member['user'];
            }

            foreach ($consumed as $user => $points) {
                if (!in_array($user, $completed)) {
                    try {
                        $timestamp = time();

                        $this->streamingDb->beginTransaction();

                        $userInfo = $userAccountModel->getRow($user, array('id', 'name'));

                        $logID = $redLogModel->insert(array(
                            'red'       => $red,
                            'user'      => $user,
                            'name'      => $userInfo['name'],
                            'points'    => $points,
                            'title'     => $redInfo['name'],
                            'dealt_on'  => $timestamp,
                        ));

                        $redRedModel->consume($red, $points);

                        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

                        $pointAccountModel->incr($user, $points);

                        $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                        $pointLogModel->insert(array(
                            'user'     => $user,
                            'number'   => $points,
                            'type'     => MySQL_Point_LogModel::LOG_TYPE_EARN_RED,
                            'dealt_on' => $timestamp,
                        ));

                        if ($broadcastChannel) {
                            // Broadcast to channel
                            $data = array(
                                'from'      => array(
                                    'id'    => $user,
                                    'name'  => $userInfo['name'],
                                ),
                                'red'       => $red,
                                'points'    => $points,
                                'timestamp' => $timestamp,
                                'color'     => $this->getRequest()->get('color', 1),
                            );
                            $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                            $redisStreamingChatChannelModel->publishGetRed($broadcastChannel, $data);
                        }

                        $this->streamingDb->commit();

                        $result['data'] = $points;
                        $result['code'] = 200;
                    } catch (Exception $e) {
                        $this->streamingDb->rollBack();

                        Misc::log($e->getMessage(), Zend_Log::ERR);
                    }
                }
            }
        }

        return true;
    }

    public function publishAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $points = (int) $request->get('points', 0);
        $number = (int) $request->get('number', 0);
        $memo = strip_tags($request->get('memo', ''));
//        $channel = $request->get('channel', 0);
        $channel = $userid;

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

        if ($redRedModel->checkAlive($userid)) {
            $result['code'] = 410;
            $result['error'][] = array(
                'message' => 'Another red is opening',
            );
        } else if ($number <= 0) {
            $result['code'] = 411;
            $result['error'][] = array(
                'message' => 'Invalid number',
            );
        } else if ($points <= 0) {
            $result['code'] = 412;
            $result['error'][] = array(
                'message' => 'Invalid points',
            );
        } else if ($points > $pointAccountModel->number($userid)) {
            $result['code'] = 413;
            $result['error'][] = array(
                'message' => 'Lack of balance',
            );
        } else {
            try {
                $this->streamingDb->beginTransaction();

                $hash = uniqid();

                $redID = $redRedModel->insert(array(
                    'user'           => $userid,
                    'name'           => $currentUser['name'],
                    'points'         => $points,
                    'number'         => $number,
                    'memo'           => $memo,
                    'target_channel' => $channel,
                    'hash'           => $hash,
                    'created_on'     => $timestamp,
                ));

                $pointAccountModel->incr($userid, $points * -1);

                $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                $pointLogModel->insert(array(
                    'user'     => $userid,
                    'number'   => $points * -1,
                    'type'     => MySQL_Point_LogModel::LOG_TYPE_CONSUME_RED,
                    'dealt_on' => $timestamp,
                ));

                // Generate reds
                $reds = Mkjogo_Red_Generator::lucky($points, $number);

                // Push to list
//                $redisRedListModel = new Redis_Red_ListModel($this->redisStreaming);
                $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);
                $redisRedRedModel->push($redID, $reds);

                // Push to queue
                $redisRedQueueModel = new Redis_Red_QueueModel($this->redisStreaming);
                $redisRedQueueModel->add($redID);

                // Broadcast to channel
                $data = array(
                    'from'      => array(
                        'id'    => $userid,
                        'name'  => $currentUser['name'],
                    ),
                    'id'             => $redID,
                    'number'         => $number,
//                    'points'         => $points,
                    'memo'           => $memo,
                    'target_channel' => $channel,
                    'hash'           => $hash,
                    'expires'        => strtotime('+24 hour', $timestamp),
                    'timestamp'      => $timestamp,
//                    'color'          => $request->get('color', 1),
                );
                $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                $redisStreamingChatChannelModel->publishRed($channel, $data);

                $this->streamingDb->commit();

                $result['code'] = 200;
            } catch (Exception $e) {
                $this->streamingDb->rollBack();

                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        }

        $this->callback($result);

        return false;
    }

    public function openAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);

        $sender = $request->get('sender');
        $hash = $request->get('hash');

        if (($id = $request->get('id')) && ($redInfo = $redRedModel->getRow($id)) && ($redInfo['hash'] == $hash)) {
            $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);
            $redLogModel = new MySQL_Red_LogModel($this->streamingDb);

            $points = $redisRedRedModel->pop($id, $userid);

            $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
            $channels = $redisStreamingChannelOnlineChannelModel->getList();

            if ($points === false) {
                $result['error'][] = array(
                    'message' => 'Empty',
                );

                $this->fixUncompletedOpen($id, $redInfo['target_channel'] ?: $channels/*$sender*/);
            } else if ($points == -1) {
                $result['error'][] = array(
                    'message' => 'Already open',
                );
            } else {
                try {
                    $this->streamingDb->beginTransaction();

                    $logID = $redLogModel->insert(array(
                        'red'       => $id,
                        'user'      => $userid,
                        'name'      => $currentUser['name'],
                        'points'    => $points,
                        'title'     => $redInfo['name'],
                        'dealt_on'  => $request->getServer('REQUEST_TIME'),
                    ));

                    $redRedModel->consume($id, $points);

                    $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

                    $pointAccountModel->incr($userid, $points);

                    $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                    $pointLogModel->insert(array(
                        'user'     => $userid,
                        'number'   => $points,
                        'type'     => MySQL_Point_LogModel::LOG_TYPE_EARN_RED,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));

//                    if ($redInfo['target_channel']/* || $sender*/) {
                        // Broadcast to channel
                        $data = array(
                            'from'      => array(
                                'id'    => $userid,
                                'name'  => $currentUser['name'],
                            ),
                            'red'       => $id,
                            'points'    => $points,
                            'timestamp' => $request->getServer('REQUEST_TIME'),
                            'color'     => $request->get('color', 1),
                        );

                        $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                        $redisStreamingChatChannelModel->publishGetRed($redInfo['target_channel'] ?: $channels/*$sender*/, $data);
//                    }

                    $this->streamingDb->commit();

                    $result['data'] = $points;
                    $result['code'] = 200;
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    Misc::log($e->getMessage(), Zend_Log::ERR);
                }
            }

        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function mysendAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $where = array();

        $where[] = '`user`=' . (int) $userid;

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
        $result = $redRedModel->search('`id`,`user`,`points`,`number`,`consumed_points`,`consumed_number`,`memo`,`created_on`', $where, '`id` DESC', $offset, $limit);

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function mygetAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $where = array();

        $where[] = '`user`=' . (int) $userid;

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $redLogModel = new MySQL_Red_LogModel($this->streamingDb);
        $result = $redLogModel->search('`id`,`red`,`user`,`points`,`title`,`memo`,`dealt_on`', $where, '`id` DESC', $offset, $limit);

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function detailAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($id = $request->get('id')) {
            $data = $row = array();
            $this->getStreamingDb();

            $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
            $redLogModel = new MySQL_Red_LogModel($this->streamingDb);

            $redInfo = $redRedModel->getRow($id, array(
                'id',
                'user',
                'name',
                'points',
                'number',
                'consumed_points',
                'consumed_number',
                'memo',
                'created_on',
            ));

            if ($redInfo) {
                if ($redInfo['user'] == $userid) {
                    $redInfo['members'] = $redLogModel->members($id);

                    $result['data'] = $redInfo;
                    $result['code'] = 200;
                } else {
                    $result['code'] = 403;
                }
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function lastAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $limit = $request->get('limit', 0) ?: 10;

        if ($channel = $request->get('channel')) {
            $this->getStreamingDb();

            $redRedModel = new MySQL_Red_RedModel($this->streamingDb);

            if ($redInfo = $redRedModel->getLast($channel)) {
                $this->getRedisStreaming();
                $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);

                $history = $redisRedRedModel->consumedAll($redInfo['id']);

                $result['opened'] = $userid && isset($history[$userid]);

                arsort($history, SORT_NUMERIC);

                $history = array_slice($history, 0, $limit, true);

                $this->getPassportDb();
                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $users = $userAccountModel->getRows(array_keys($history), array('id', 'name'));

                foreach ($users as $user) {
                    $redInfo['top'][] = array(
                        'id'     => $user['id'],
                        'name'   => $user['name'],
                        'points' => $history[$user['id']],
                    );
                }

                $result['data'] = $redInfo;
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

    public function typesAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $redTypeModel = new MySQL_Red_TypeModel($this->streamingDb);

        $result['data'] = $redTypeModel->getAll();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function last_systemAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $client = $request->get('client');
        $limit = (int) $request->get('limit', 0) ?: 10;

        $this->getStreamingDb();

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);

        if (!is_null($client) && ($redInfo = $redRedModel->getLastSystem((int) $client))) {
            $this->getRedisStreaming();
            $redisRedRedModel = new Redis_Red_RedModel($this->redisStreaming);

            $history = $redisRedRedModel->consumedAll($redInfo['id']);

            $result['opened'] = $userid && isset($history[$userid]);

            arsort($history, SORT_NUMERIC);

            $history = array_slice($history, 0, $limit, true);

            $this->getPassportDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $users = $userAccountModel->getRows(array_keys($history), array('id', 'name'));

            foreach ($users as $user) {
                $redInfo['top'][] = array(
                    'id'     => $user['id'],
                    'name'   => $user['name'],
                    'points' => $history[$user['id']],
                );
            }

            $result['data'] = $redInfo;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}