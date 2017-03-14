<?php
use Aws\S3\S3Client;

class ChannelController extends ApiController
{
    protected $authActions = array(
        'create',
        'update',
        'resetkey',
        'getkey',
        'follow',
        'unfollow',
        'following',
        'livehistory',
        'upload_offline_image',
        'upload_background_image',
        'upload_show_image',
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

    protected $redisChat;

    protected $s3;

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
            $this->getStreamingDb();
            $this->getPassportDb();
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

            if ($streamingChannelModel->exists($userid)) {
                $row = $streamingChannelModel->getRow($userid);

                $result['data'] = array(
                    'channel'       => $userid,
                    'stream_key'    => MySQL_Streaming_ChannelModel::makeStreamKey($row['id'], $row['hash'])
                );
                $result['code'] = 200;
            } else {
                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);

                $userInfo = $userAccountModel->getRow($userid, array('name'));

                $data = array(
                    'id'            => $userid,
                    'title'         => $request->get('title'),
                    'owner_name'    => $userInfo['name'],
                    'class'         => $streamingChannelClassModel->getDefaultClassID(),
                    'created_on'    => $request->getServer('REQUEST_TIME'),
                );

                if ($channel = $streamingChannelModel->insert($data)) {
                    // Add owner as editor
                    $data = array(
                        'channel'       => $userid,
                        'user'          => $userid,
                        'name'          => $userInfo['name'],
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );
                    $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
                    $streamingEditorModel->insert($data);

                    // Create gift account
//                    $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
//                    $giftAccountModel->insert(array(
//                        'id'    => $userid,
//                    ));

                    $result['data'] = array(
                        'channel'       => $channel,
                        'stream_key'    => $streamingChannelModel->resetStreamKey($channel),
                    );
                    $result['code'] = 200;

                    // Set default logo
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $return = $this->s3->copyObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'CopySource'    => sprintf('%s/previews/default.jpg', $config['aws']['s3']['bucket']['streaming']),
                        'Key'           => sprintf('previews/live_%s-356x200.jpg', $userid),
                        'ContentType'   => 'image/jpeg',
                        'ACL'           => 'public-read',
                    ));
                }
            }
        }

        $this->callback($result);

        return false;
    }

    public function updateAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $data = array();

//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

            $channelInfo = $streamingChannelModel->getRow($userid, array('paypal'));

            if ($title = $request->get('title')) {
                $data['title'] = $title;
            }

            if (($alias = $request->get('alias')) && (preg_match('|^\w+{5, 100}$|', $alias) !== false) && !is_numeric($alias)) {
                $data['alias'] = $alias;
            }

            if ($playingGame = $request->get('playing_game')) {
                $data['playing_game'] = $playingGame;
            }

            if ($facebook = $request->get('facebook')) {
                $data['facebook'] = $facebook;
            }

            //@todo security
            if (!$channelInfo['paypal'] && ($paypal = $request->get('paypal'))) {
                $data['paypal'] = $paypal;
            }

            if ($data) {
                try {
                    $streamingChannelModel->update($userid, $data);

                    // Publish message
                    if (isset($data['title'])) {
                        $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->getRedisChat());
                        $redisStreamingChatChannelModel->operateBan($userid, array(
                            'title' => $data['title'],
                        ));
                    }

                    $result['code'] = 200;
                } catch (Exception $e) {
                    $result['code'] = 409;
                }
            } else {
                $result['code'] = 400;
            }
        } else {
            $result['code'] = 404;
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
        $timestamp = $request->getServer('REQUEST_TIME');
        $config = Yaf_Registry::get('config')->toArray();

//            $mkuser = Yaf_Registry::get('mkuser');
//            $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if ($alias = $request->get('alias')) {
            $channel = $streamingChannelModel->aliasToID($alias);
        } else {
            $channel = $request->get('channel');
        }

        if ($channel && ($row = $streamingChannelModel->getRow($channel))) {
            $data = array();

            $this->getRedisStreaming();

            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
            $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
            $redisStreamingChannelWatchingNowModel = new Redis_Streaming_Channel_WatchingNowModel($this->redisStreaming);
            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);

            $redisStreamingChannelTotalViewsModel->incr($channel);
//            $userSession = Yaf_Registry::get('user-session');

            $this->getPassportDb();
            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $profile = $userProfileModel->getRow($channel, array('avatar'));

            $data = array(
                'id'            => $row['id'],
                'title'         => $row['title'],
                'is_online'     => $row['is_online'],
                'is_banned'     => $row['is_banned'],
                'owner_name'    => $row['owner_name'],
                'followers'     => $row['followers'],
                'alias'         => $row['alias'],
                'playing_game'  => $row['playing_game'],
                'resolutions'   => $row['resolutions'],
                'facebook'      => $row['facebook'],
                'is_editor'     => ($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid),
                'total_views'   => (int) $redisStreamingChannelTotalViewsModel->get($channel),
//                'watching_now'  => (int) $redisStreamingChannelWatchingNowModel->get($channel),
                'watching_now'  => Mkjogo_Streaming_Cheat::watchingNow((int) $redisStreamingChannelOnlineClientByChannelModel->get($channel)),
//                'watching_now'  => (int) $redisStreamingChannelOnlineClientModel->total($channel),
                'giving_gifts'  => $giftAccountModel->remain($channel),
                'avatar'        => $profile['avatar'],
                'small_show_image' => $row['small_show_image'],
                'large_show_image' => $row['large_show_image'],
            );

            if ($userid) {
                $streamingFollowingModel = new MySQL_Streaming_FollowingModel($this->streamingDb);

                $data['followed'] = $streamingFollowingModel->exists($userid, $channel);

                if ($channel == $userid) {
                    $data['paypal'] = $row['paypal'];
                }

                // Check watching task
//                $redisStreamingWatchingTaskProgressModel = new Redis_Streaming_WatchingTask_ProgressModel($this->redisStreaming);
//                if (!$redisStreamingWatchingTaskProgressModel->completed($userid, $timestamp)) {
//                    $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);
//
//                    if (!$redisStreamingWatchingTaskProgressModel->exists($userid, $timestamp)) {
//                        $tasks = $streamingWatchingTaskModel->getAll();
//
//                        $redisStreamingWatchingTaskProgressModel->dailyInit($userid, array_map(function($val) {return $val['id'];}, $tasks), $timestamp);
//                    }
//
//                    // Try to start new task
//                    if (($task = $redisStreamingWatchingTaskProgressModel->currentPending($userid, $timestamp)) && !$redisStreamingWatchingTaskProgressModel->currentCompleted($userid, $timestamp)) {
//                        $taskInfo = $streamingWatchingTaskModel->getRow($task);
//
//                        $token = uniqid() . ':' . $task;
//
//                        $redisStreamingWatchingTaskCounterModel = new Redis_Streaming_WatchingTask_CounterModel($this->redisStreaming);
//                        $redisStreamingWatchingTaskCounterModel->add($userid, $token, $taskInfo['timer'], $timestamp);
//
//                        $data['watching_task_token'] = $token;
//                    } else {
//                        $data['watching_task_token'] = false;
//                    }
//                }
            } else {
                $data['followed'] = false;
            }

            $result['data'] = $data;
            $result['code'] = 200;
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

        $this->getStreamingDb();
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if ($streamKey = $streamingChannelModel->resetStreamKey($userid)) {
            $result['code'] = 200;
            $result['data'] = $streamKey;
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function getkeyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if ($row = $streamingChannelModel->getRow($userid)) {
            $result['code'] = 200;
            $result['data'] = MySQL_Streaming_ChannelModel::makeStreamKey($row['id'], $row['hash']);
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function followAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if (($channel = $request->get('channel')) && $streamingChannelModel->exists($channel)) {
            $streamingFollowingModel = new MySQL_Streaming_FollowingModel($this->streamingDb);

            if (!$streamingFollowingModel->exists($userid, $channel)) {
                $streamingFollowingModel->add($userid, $channel);

                $streamingChannelModel->follow($channel);
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function unfollowAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $streamingFollowingModel = new MySQL_Streaming_FollowingModel($this->streamingDb);
        if (($channel = $request->get('channel')) && $streamingFollowingModel->exists($userid, $channel)) {
            $streamingFollowingModel->remove($userid, $channel);

            $streamingChannelModel->follow($channel, false);

            $result['code'] = 200;
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function followingAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $streamingFollowingModel = new MySQL_Streaming_FollowingModel($this->streamingDb);

        if ($channelIds = $streamingFollowingModel->channels($userid)) {
            $channels = $streamingChannelModel->getRows($channelIds, array(
                'id',
                'title',
                'is_online',
                'owner_name',
                'alias',
                'special',
                'playing_game',
            ));

            $channelIds = array();

            foreach ($channels as $channelInfo) {
                if ($channelInfo['is_online']) {
                    $channelIds[] = $channelInfo['id'];
                }
            }

            if ($channelIds) {
//                $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
                $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
                $onlines = $redisStreamingChannelOnlineClientByChannelModel->mget($channelIds);

                foreach ($channels as $key => $val) {
                    $channels[$key]['watching_now'] = Mkjogo_Streaming_Cheat::watchingNow(isset($onlines[$val['id']]) ? $onlines[$val['id']] : 0);
//                    if (in_array($val['id'], $channelIds)) {
//                        $channels[$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($val['id']);
//                    }
                }
            }

            $result['data'] = $channels;
        } else {
            $result['data'] = array();
        }

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function getclientoneAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $data = array();

        $this->getRedisStreaming();
        $redisStreamingClientOneModel = new Redis_Streaming_ClientOneModel($this->redisStreaming);

        $result['data'] = $redisStreamingClientOneModel->get();
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function listhotAction()
    {
        $request = $this->getRequest();
        $lives = array();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page ?: 1;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = $limit * ($page - 1);

        $this->getRedisStreaming();

        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
        $lives = $redisStreamingChannelOnlineChannelModel->getList();
        $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
        if ($lives = $redisStreamingChannelOnlineClientByChannelModel->mget($lives)) {
            arsort($lives, SORT_NUMERIC);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);

            if ($parts = array_slice($lives, $offset, $limit, true)) {
                $streamingChannel = new MySQL_Streaming_ChannelModel($this->getStreamingDb());
                $result['data'] = $streamingChannel->getRows(array_keys($parts), array(
                    'title',
                    'is_online',
                    'is_banned',
                    'owner_name',
                    'alias',
                    'special',
                    'playing_game',
                    'small_show_image',
                    'large_show_image',
                ));

                $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
                foreach ($result['data'] as $key => $val) {
                    if (isset($parts[$val['id']])) {
                        $result['data'][$key]['watching_now'] = Mkjogo_Streaming_Cheat::watchingNow($parts[$val['id']]);
//                        $result['data'][$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($val['id']);
                        $result['data'][$key]['total_views'] = (int) $redisStreamingChannelTotalViewsModel->get($val['id']);
                    }
                }
            } else {
                $result['data'] = array();
            }
        } else {
            $result['data'] = array();
        }

        $result['page'] = $page;
        $result['total_found'] = count($lives);
        $result['page_count'] = ceil($result['total_found'] / $limit);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function listnewAction()
    {
        $request = $this->getRequest();
        $lives = $numbers = $finalLives = array();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page ?: 1;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = $limit * ($page - 1);

        $this->getRedisStreaming();

        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
        if ($lives = $redisStreamingChannelOnlineChannelModel->getRevList()) {
            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
            $numbers = $redisStreamingChannelOnlineClientByChannelModel->mget($lives);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);

            foreach ($lives as $val) {
                if (isset($numbers[$val])) {
                    $finalLives[$val] = $numbers[$val];
                }
            }

            if ($parts = array_slice($finalLives, $offset, $limit, true)) {
                $streamingChannel = new MySQL_Streaming_ChannelModel($this->getStreamingDb());
                $result['data'] = $streamingChannel->getRows(array_keys($parts), array(
                    'title',
                    'is_online',
                    'is_banned',
                    'owner_name',
                    'alias',
                    'special',
                    'playing_game',
                    'small_show_image',
                    'large_show_image',
                ));

                $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
                foreach ($result['data'] as $key => $val) {
                    if (isset($parts[$val['id']])) {
                        $result['data'][$key]['watching_now'] = Mkjogo_Streaming_Cheat::watchingNow($parts[$val['id']]);
//                        $result['data'][$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($val['id']);
                        $result['data'][$key]['total_views'] = (int) $redisStreamingChannelTotalViewsModel->get($val['id']);
                    }
                }
            } else {
                $result['data'] = array();
            }
        } else {
            $result['data'] = array();
        }

        $result['page'] = $page;
        $result['total_found'] = count($finalLives);
        $result['page_count'] = ceil($result['total_found'] / $limit);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function infoAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

        $channels = Misc::parseIds($request->get('channels'));
        if ($channels) {
            $this->getStreamingDb();
            $this->getRedisStreaming();

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
            $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);

            $rows = $streamingChannelModel->getRows($channels, array(
                'id',
                'title',
                'is_online',
                'is_banned',
                'owner_name',
                'followers',
                'alias',
                'special',
                'playing_game',
                'small_show_image',
                'large_show_image',
            ));

            if ($watchingNows = $redisStreamingChannelOnlineClientByChannelModel->mget($channels)) {
                foreach ($rows as $key => $val) {
                    $rows[$key]['watching_now'] = Mkjogo_Streaming_Cheat::watchingNow(isset($watchingNows[$val['id']]) ? $watchingNows[$val['id']] : 0);

//                    $rows[$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($val['id']);
                    $rows[$key]['total_views'] = (int) $redisStreamingChannelTotalViewsModel->get($val['id']);
                }
            }

            $result['data'] = $rows;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function livehistoryAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);

        if ($streamingChannelModel->exists($userid)) {
            $result['data'] = $streamingLiveLengthLogModel->recent($userid);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function detailAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if ($alias = $request->get('alias')) {
            $channel = $streamingChannelModel->aliasToID($alias);
        } else {
            $channel = $request->get('channel');
        }

        if ($channel && ($row = $streamingChannelModel->getRow($channel))) {
            $this->getRedisStreaming();

            $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
            $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);

            $this->getPassportDb();
            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $profile = $userProfileModel->getRow($channel, array('avatar'));

            $result['data'] = array(
                'id'            => $row['id'],
                'title'         => $row['title'],
                'is_online'     => $row['is_online'],
                'is_banned'     => $row['is_banned'],
                'owner_name'    => $row['owner_name'],
                'followers'     => $row['followers'],
                'alias'         => $row['alias'],
                'playing_game'  => $row['playing_game'],
                'resolutions'   => $row['resolutions'],
                'facebook'      => $row['facebook'],
                'is_editor'     => $userid && (($channel == $userid) || $streamingEditorModel->isMember($channel, $userid) || $streamingSupervisorModel->isMember($userid)),
                'total_views'   => (int) $redisStreamingChannelTotalViewsModel->get($channel),
                'watching_now'  => Mkjogo_Streaming_Cheat::watchingNow((int) $redisStreamingChannelOnlineClientByChannelModel->get($channel)),
//                'watching_now'  => (int) $redisStreamingChannelOnlineClientModel->total($channel),
                'giving_gifts'  => (int) $giftAccountModel->remain($channel),
                'avatar'        => $profile['avatar'],
                'small_show_image' => $row['small_show_image'],
                'large_show_image' => $row['large_show_image'],
            );

            if ($channel == $userid) {
                $result['data']['paypal'] = $row['paypal'];
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function list_hot_by_gameAction()
    {
        $request = $this->getRequest();
        $lives = array();

        $result = array(
            'code'  => 500,
        );
        $config = Yaf_Registry::get('config')->toArray();

        if ($game = $request->get('game')) {
            $page = intval($request->get('page', 0));
            $page = $page ?: 1;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 20;

            $offset = $limit * ($page - 1);

            $this->getRedisStreaming();

            $redisStreamingChannelOnlineGameModel = new Redis_Streaming_Channel_Online_GameModel($this->redisStreaming);
            $lives = $redisStreamingChannelOnlineGameModel->getListByGame($game);

            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
            $lives = $redisStreamingChannelOnlineClientByChannelModel->mget($lives);
            arsort($lives, SORT_NUMERIC);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);

            if ($parts = array_slice($lives, $offset, $limit, true)) {
                $this->getStreamingDb();

                $streamingChannel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                $result['data'] = $streamingChannel->getRows(array_keys($parts), array(
                    'title',
                    'is_online',
                    'is_banned',
                    'owner_name',
                    'alias',
                    'special',
                    'playing_game',
                    'small_show_image',
                    'large_show_image',
                ));

                $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
                foreach ($result['data'] as $key => $val) {
                    if (isset($parts[$val['id']])) {
                        $result['data'][$key]['watching_now'] = Mkjogo_Streaming_Cheat::watchingNow($parts[$val['id']]);
//                        $result['data'][$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($val['id']);
                        $result['data'][$key]['total_views'] = (int) $redisStreamingChannelTotalViewsModel->get($val['id']);
                    }
                }
            } else {
                $result['data'] = array();
            }

            $result['page'] = $page;
            $result['total_found'] = count($lives);
            $result['page_count'] = ceil($result['total_found'] / $limit);
            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

    public function get_classesAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);
        $result['data'] = $streamingChannelClassModel->getAll();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
    
    public function upload_offline_imageAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();

            try {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

                if (!$_FILES || !isset($_FILES['offline_image_file'])) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'offline_image_file',
                        'message' => 'Offline image file required',
                    );
                } else {
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $data = array();

                    $src = null;
                    $timeFlag = date('YmdHis', $request->getServer('REQUEST_TIME'));

                    // Save file
                    $fileInfo = $_FILES['offline_image_file'];
                    $fileName = sprintf('offline/%s_%s.png', $userid, $timeFlag);

                    if (($fileType = exif_imagetype($fileInfo['tmp_name'])) !== false) {
                        if ($fileType == IMAGETYPE_JPEG) {
                            $src = imagecreatefromjpeg($fileInfo['tmp_name']);
                        } else if ($fileType == IMAGETYPE_GIF) {
                            $src = imagecreatefromgif($fileInfo['tmp_name']);
                        } else {
                            $src = null;
                        }

                        $src && imagepng($src, $fileInfo['tmp_name']);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fileName,
                            'SourceFile' => $fileInfo['tmp_name'],
                            'ContentType' => 'image/png', //$fSmallInfo['type'],
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fileName,
                        ));

                        $data['offline_image'] = $fileName; //$fSmallNameCopy;
                    }

                    if ($data) {
                        $streamingChannelModel->update($userid, $data);

                        $result['code'] = 200;
                        $result['data'] = $data;
                    } else {
                        $result['code'] = 400;
                    }
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function upload_background_imageAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();

            try {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

                if (!$_FILES || !isset($_FILES['background_image_file'])) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'background_image_file',
                        'message' => 'Background image file required',
                    );
                } else {
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $data = array();

                    $src = null;
                    $timeFlag = date('YmdHis', $request->getServer('REQUEST_TIME'));

                    // Save file
                    $fileInfo = $_FILES['background_image_file'];
                    $fileName = sprintf('background/%s_%s.png', $userid, $timeFlag);

                    if (($fileType = exif_imagetype($fileInfo['tmp_name'])) !== false) {
                        if ($fileType == IMAGETYPE_JPEG) {
                            $src = imagecreatefromjpeg($fileInfo['tmp_name']);
                        } else if ($fileType == IMAGETYPE_GIF) {
                            $src = imagecreatefromgif($fileInfo['tmp_name']);
                        } else {
                            $src = null;
                        }

                        $src && imagepng($src, $fileInfo['tmp_name']);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fileName,
                            'SourceFile' => $fileInfo['tmp_name'],
                            'ContentType' => 'image/png', //$fSmallInfo['type'],
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fileName,
                        ));

                        $data['background_image'] = $fileName;
                    }

                    if ($data) {
                        $streamingChannelModel->update($userid, $data);

                        $result['code'] = 200;
                        $result['data'] = $data;
                    } else {
                        $result['code'] = 400;
                    }
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function upload_show_imageAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();

            try {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

                if (!$_FILES || !isset($_FILES['large_show_image_file'])) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'large_show_image_file',
                        'message' => 'Show image file required',
                    );
                } else {
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $data = array();

                    $src = null;
                    $timeFlag = date('YmdHis', $request->getServer('REQUEST_TIME'));

                    // Save file
                    $fileInfo = $_FILES['large_show_image_file'];
                    $fileName = sprintf('show/%s-large_%s.png', $userid, $timeFlag);

                    if (($fileType = exif_imagetype($fileInfo['tmp_name'])) !== false) {
                        if ($fileType == IMAGETYPE_JPEG) {
                            $src = imagecreatefromjpeg($fileInfo['tmp_name']);
                        } else if ($fileType == IMAGETYPE_GIF) {
                            $src = imagecreatefromgif($fileInfo['tmp_name']);
                        } else {
                            $src = null;
                        }

                        $src && imagepng($src, $fileInfo['tmp_name']);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fileName,
                            'SourceFile' => $fileInfo['tmp_name'],
                            'ContentType' => 'image/png', //$fSmallInfo['type'],
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fileName,
                        ));

                        $data['large_show_image'] = $fileName;
                    }

                    if ($data) {
                        $streamingChannelModel->update($userid, $data);

                        $result['code'] = 200;
                        $result['data'] = $data;
                    } else {
                        $result['code'] = 400;
                    }
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}