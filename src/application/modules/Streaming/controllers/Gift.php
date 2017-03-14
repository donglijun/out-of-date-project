<?php
class GiftController extends ApiController
{
    const CACHE_EXPIRATION = 60;

    protected $authActions = array(
        'remain',
        'checkin',
        'share',
        'give',
        'checkcheckin',
        'checkshare',
        'watching_tasks',
        'complete_watching_task',
        'award_watching_task',
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

    protected $redisChat;

    protected $redisSession;

    protected $cache;

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

    protected function getRedisSession()
    {
        if (empty($this->redisSession)) {
            $this->redisSession = Daemon::getRedis('redis-session', 'redis-session');
        }

        return $this->redisSession;
    }

    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = Daemon::getMemcached('memcached-front', 'memcached-front');
        }

        return $this->cache;
    }

    public function remainAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
//        $userSession = Yaf_Registry::get('user-session');

        $bill = $giftAccountModel->bill($userid);

        $redisGiftCollectCheckinDailyTotalModel = new Redis_Gift_Collect_Checkin_DailyTotalModel($this->redisStreaming);
        $bill['today_checkin_collecting'] = (int) $redisGiftCollectCheckinDailyTotalModel->get($userid);

        $redisGiftCollectShareFacebookTotalModel = new Redis_Gift_Collect_Share_FacebookTotalModel($this->redisStreaming);
        $bill['today_facebook_collecting'] = (int) $redisGiftCollectShareFacebookTotalModel->get($userid);

        $result['data'] = $bill;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function checkinAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $ip = Misc::getClientIp();

//        $sessionKey = 'captcha-collect-gift';
//        $captchaSession = $this->session->{$sessionKey};

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
        $redisGiftCollectCheckinDailyTotalModel = new Redis_Gift_Collect_Checkin_DailyTotalModel($this->redisStreaming);
        $redisGiftCollectCheckinDailyTargetModel = new Redis_Gift_Collect_Checkin_DailyTargetModel($this->redisStreaming);
        $redisGiftCollectMarkModel = new Redis_Gift_Collect_MarkModel($this->redisStreaming);

        if (($channel = $request->get('channel')) && $streamingChannelModel->exists($channel)) {
            if ($redisGiftCollectCheckinDailyTotalModel->isFull($userid) || $redisGiftCollectCheckinDailyTargetModel->isMember($userid, $channel)) {
                $result['code'] = 403;
//            } else if (!$redisGiftCollectMarkModel->valid($ip) && (!($captcha_value = $request->get('captcha_value')) || strcasecmp($captcha_value, $captchaSession['word']) || ($captchaSession['timeout'] < $request->getServer('REQUEST_TIME')))) {
//                $result['code'] = 302;
//                $result['redirect'] = '/captcha.php?ns=collect-gift';
            } else {
//                $userSession = Yaf_Registry::get('user-session');
                $giftNumber = MySQL_Gift_AccountModel::UNIT_COLLECT_CHECKIN * 2;

                $giftAccountModel->collect($userid, $giftNumber);
//                $remain = $userSession->incrby($userid, 'gift_balance', $giftNumber);
                $bill = $giftAccountModel->bill($userid);
                $giftUserLogModel->insert(array(
                    'user'      => $userid,
                    'channel'   => $channel,
                    'number'    => $giftNumber,
                    'dealt_on'  => $request->getServer('REQUEST_TIME'),
                ));
                $bill['today_checkin_collecting'] = (int) $redisGiftCollectCheckinDailyTotalModel->add($userid, $giftNumber);
                $redisGiftCollectCheckinDailyTargetModel->add($userid, $channel);

                $redisGiftCollectMarkModel->mark($ip, $giftNumber);

                $result['code'] = 200;
                $result['data'] = $bill;
            }
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function shareAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $ip = Misc::getClientIp();

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
        $redisGiftCollectShareFacebookTotalModel = new Redis_Gift_Collect_Share_FacebookTotalModel($this->redisStreaming);
        $redisGiftCollectShareFacebookTargetModel = new Redis_Gift_Collect_Share_FacebookTargetModel($this->redisStreaming);
        $redisGiftCollectMarkModel = new Redis_Gift_Collect_MarkModel($this->redisStreaming);

        if (($highlight = $request->get('highlight')) && $streamingBroadcastHighlightModel->exists($highlight)) {
            if ($redisGiftCollectShareFacebookTotalModel->isFull($userid) || $redisGiftCollectShareFacebookTargetModel->isMember($userid, $highlight)) {
                $result['code'] = 403;
            } else {
                $giftNumber = MySQL_Gift_AccountModel::UNIT_COLLECT_SHARE_FACEBOOK;

                $giftAccountModel->collect($userid, $giftNumber);
                $bill = $giftAccountModel->bill($userid);
                $giftUserLogModel->insert(array(
                    'user'      => $userid,
                    'highlight' => $highlight,
                    'number'    => $giftNumber,
                    'dealt_on'  => $request->getServer('REQUEST_TIME'),
                ));
                $bill['today_facebook_collecting'] = (int) $redisGiftCollectShareFacebookTotalModel->add($userid, $giftNumber);
                $redisGiftCollectShareFacebookTargetModel->add($userid, $highlight);

//                $redisGiftCollectCheckinDailyTotalModel = new Redis_Gift_Collect_Checkin_DailyTotalModel($this->redisStreaming);
//                $bill['today_checkin_collecting'] = $redisGiftCollectCheckinDailyTotalModel->get($userid);

                $redisGiftCollectMarkModel->mark($ip);

                $result['code'] = 200;
                $result['data'] = $bill;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function giveAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];
        $username = $currentUser['name'];

        $this->getStreamingDb();
        $this->getPassportDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if (($channel = $request->get('channel')) && ($channel != $userid) && $streamingChannelModel->exists($channel)) {
            $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
            $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
            $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $giftGrowthSchemeModel = new MySQL_Gift_GrowthSchemeModel($this->streamingDb);

//            $userSession = Yaf_Registry::get('user-session');
            $giftNumber = MySQL_Gift_AccountModel::UNIT_CONSUME_GIVE;

            try {
                $this->streamingDb->beginTransaction();

                if ($giftAccountModel->give($userid, $channel, $giftNumber)) {
//                $remain = $userSession->incrby($userid, 'gift_balance', $giftNumber * -1);
//                $userSession->incrby($channel, 'gift_earned', $giftNumber);

                    $giftUserLogModel->insert(array(
                        'user' => $userid,
                        'channel' => $channel,
                        'number' => $giftNumber * -1,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    $giftChannelLogModel->insert(array(
                        'channel' => $channel,
                        'user' => $userid,
                        'number' => $giftNumber,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    //@todo Update growth level and points
//                    $bill = $giftAccountModel->bill($userid);
//                    $growthPoints = $bill['growth_points'] + $giftNumber;
//                    $growthScheme = $giftGrowthSchemeModel->getRowByLevel($bill['growth_level'], array('points'));
//                    if ($growthPoints < $growthScheme['points']) {
//                        $giftAccountModel->update($userid, array(
//                            'growth_points' => $growthPoints,
//                        ));
//                    } else {
//                        $giftAccountModel->update($userid, array(
//                            'growth_level' => $bill['growth_level'] + 1,
//                            'growth_points' => $growthPoints - $growthScheme['points'],
//                        ));
//                    }

                    // Publish message
                    $this->getRedisChat();

                    $userInfo = $userAccountModel->getRow($channel, array('name'));
                    $data = array(
                        'from' => array(
                            'id' => $userid,
                            'name' => $username,
                        ),
                        'to' => array(
                            'id' => $channel,
                            'name' => $userInfo['name'],
                        ),
                        'number' => $giftNumber,
                        'timestamp' => $request->getServer('REQUEST_TIME'),
                        'color' => $request->get('color', 1),
                    );

                    $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                    $redisStreamingChatChannelModel->publishGift($channel, $data);

                    $this->getRedisStreaming();

                    $redisGiftRankingDailyModel = new Redis_Gift_Ranking_DailyModel($this->redisStreaming);
                    $redisGiftRankingDailyModel->incr($channel);

                    $redisGiftRankingWeeklyModel = new Redis_Gift_Ranking_WeeklyModel($this->redisStreaming);
                    $redisGiftRankingWeeklyModel->incr($channel);

                    $redisGiftRankingMonthlyModel = new Redis_Gift_Ranking_MonthlyModel($this->redisStreaming);
                    $redisGiftRankingMonthlyModel->incr($channel);

                    $this->streamingDb->commit();

                    $bill = $giftAccountModel->bill($userid);

                    $result['code'] = 200;
                    $result['data'] = $bill;
                } else {
                    $this->streamingDb->commit();

                    $result['code'] = 403;
                    $result['message'] = 'Not enough gifts';
                }
            } catch (Exception $e) {
                $this->streamingDb->rollBack();

                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function checkcheckinAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($channel = $request->get('channel')) {
            $this->getRedisStreaming();

            $redisGiftCollectCheckinDailyTotalModel = new Redis_Gift_Collect_Checkin_DailyTotalModel($this->redisStreaming);
            $redisGiftCollectCheckinDailyTargetModel = new Redis_Gift_Collect_Checkin_DailyTargetModel($this->redisStreaming);

            $result['data'] = $redisGiftCollectCheckinDailyTotalModel->isFull($userid) || $redisGiftCollectCheckinDailyTargetModel->isMember($userid, $channel);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function checkshareAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($highlight = $request->get('highlight')) {
            $this->getRedisStreaming();

            $redisGiftCollectShareFacebookTotalModel = new Redis_Gift_Collect_Share_FacebookTotalModel($this->redisStreaming);
            $redisGiftCollectShareFacebookTargetModel = new Redis_Gift_Collect_Share_FacebookTargetModel($this->redisStreaming);

            $result['data'] = $redisGiftCollectShareFacebookTotalModel->isFull($userid) || $redisGiftCollectShareFacebookTargetModel->isMember($userid, $highlight);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function top_todayAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGiftRankingDailyModel = new Redis_Gift_Ranking_DailyModel($this->redisStreaming);
        if ($ranking = $redisGiftRankingDailyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $data = $streamingChannelModel->getRows($ids, array(
                'id',
                'title',
                'is_online',
                'owner_name',
            ));

            foreach ($data as $key => $val) {
                $data[$key]['gifts'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_weekAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGiftRankingWeeklyModel = new Redis_Gift_Ranking_WeeklyModel($this->redisStreaming);
        if ($ranking = $redisGiftRankingWeeklyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $data = $streamingChannelModel->getRows($ids, array(
                'id',
                'title',
                'is_online',
                'owner_name',
            ));

            foreach ($data as $key => $val) {
                $data[$key]['gifts'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_monthAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGiftRankingMonthlyModel = new Redis_Gift_Ranking_MonthlyModel($this->redisStreaming);
        if ($ranking = $redisGiftRankingMonthlyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $data = $streamingChannelModel->getRows($ids, array(
                'id',
                'title',
                'is_online',
                'owner_name',
            ));

            foreach ($data as $key => $val) {
                $data[$key]['gifts'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_hourAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $pattern = '|^(\d{4})(\d{2})(\d{2})(\d{2})$|';
        $data = $matches = array();
        $limit = $request->get('limit', 0) ?: 10;
        $hour = $request->get('hour');

        if (preg_match($pattern, $hour, $matches)) {
            $cacheKey   = Misc::cacheKey(array(
                $request->getControllerName(),
                $request->getActionName(),
                $hour,
                $limit,
            ));

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $ranking = $channels = $map = array();
                    $this->getStreamingDb();
                    $this->getPassportDb();

                    $giftRaceModel = new MySQL_Gift_RaceModel($this->streamingDb);

                    if ($row = $giftRaceModel->getRow($hour)) {
                        $from = $row['from'];
                        $to = $row['to'];

                        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);
                        $ranking = $giftChannelLogModel->sumByChannel($from, $to);
                        $ranking = array_slice($ranking, 0, $limit);

                        foreach ($ranking as $row) {
                            $channels[] = $row['channel'];
                            $map[$row['channel']] = $row['sum'];
                        }

                        $avatars = array();

                        $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                        $userProfiles = $userProfileModel->getRows($channels, array('user', 'avatar'));
                        foreach ($userProfiles as $row) {
                            $avatars[$row['user']] = $row['avatar'];
                        }

                        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                        $data = $streamingChannelModel->getRows($channels, array(
                            'id',
                            'title',
                            'is_online',
                            'owner_name',
                        ));

                        foreach ($data as $key => $val) {
                            $data[$key]['gifts'] = $map[$val['id']];
                            $data[$key]['avatar'] = $avatars[$val['id']];
                        }
                    }

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['data'] = $data;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function watching_tasksAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $tasks = $progress = array();

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);
        $redisStreamingWatchingTaskProgressModel = new Redis_Streaming_WatchingTask_ProgressModel($this->redisStreaming);

        $tasks = $streamingWatchingTaskModel->getAll();

        if ($userid) {
            $progress= $redisStreamingWatchingTaskProgressModel->all($userid);

            foreach ($tasks as $key => $val) {
                $tasks[$key]['progress'] = $progress[$val['id']];
            }
        }

        $result['data'] = $tasks;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function complete_watching_taskAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $timestamp = $request->getServer('REQUEST_TIME');

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($token = $request->get('token')) {
            $this->getStreamingDb();
            $this->getRedisStreaming();

            $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);
            $redisStreamingWatchingTaskProgressModel = new Redis_Streaming_WatchingTask_ProgressModel($this->redisStreaming);
            $redisStreamingWatchingTaskCounterModel = new Redis_Streaming_WatchingTask_CounterModel($this->redisStreaming);

            if ($redisStreamingWatchingTaskCounterModel->check($userid, $token, $timestamp)) {
                list($session, $task,) = explode(':', $token);

                $redisStreamingWatchingTaskProgressModel->complete($userid, $task, $timestamp);

                $redisStreamingWatchingTaskCounterModel->invalidate($userid, $token, $timestamp);

                $result['code'] = 200;
            } else {
                $result['code'] = 412;
            }
        } else {
            $result['code'] = 400;
        }

        $this->callback($result);

        return false;
    }

    public function award_watching_taskAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $timestamp = $request->getServer('REQUEST_TIME');

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($task = $request->get('task')) {
            $this->getStreamingDb();
            $this->getRedisStreaming();

            $streamingWatchingTaskModel = new MySQL_Streaming_WatchingTaskModel($this->streamingDb);
            $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
            $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
            $redisStreamingWatchingTaskProgressModel = new Redis_Streaming_WatchingTask_ProgressModel($this->redisStreaming);
            $redisStreamingWatchingTaskCounterModel = new Redis_Streaming_WatchingTask_CounterModel($this->redisStreaming);

            if ($redisStreamingWatchingTaskProgressModel->status($userid, $task, $timestamp) == Redis_Streaming_WatchingTask_ProgressModel::STATUS_COMPLETED) {
                $taskInfo = $streamingWatchingTaskModel->getRow($task);

                if ($giftNumber = $taskInfo['gifts']) {
                    $giftAccountModel->collect($userid, $giftNumber);
                    $bill = $giftAccountModel->bill($userid);
                    $giftUserLogModel->insert(array(
                        'user'      => $userid,
                        'task'      => $task,
                        'number'    => $giftNumber,
                        'dealt_on'  => $request->getServer('REQUEST_TIME'),
                    ));
                }

                if ($points = $taskInfo['points']) {
                    $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

                    $pointAccountModel->incr($userid, $points);

                    $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                    $pointLogModel->insert(array(
                        'user'     => $userid,
                        'number'   => $points,
                        'type'     => MySQL_Point_LogModel::LOG_TYPE_EARN_TASK,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));
                }

                $redisStreamingWatchingTaskProgressModel->award($userid, $task, $timestamp);

                // Try to start new task
                if ($task = $redisStreamingWatchingTaskProgressModel->currentPending($userid, $timestamp)) {
                    $taskInfo = $streamingWatchingTaskModel->getRow($task);

                    $token = uniqid() . ':' . $task;

                    $redisStreamingWatchingTaskCounterModel->add($userid, $token, $taskInfo['timer'], $timestamp);

                    $bill['watching_task_token'] = $token;
                }

                $result['code'] = 200;
                $result['data'] = $bill;
            } else {
                $result['code'] = 412;
            }
        } else {
            $result['code'] = 400;
        }

        $this->callback($result);

        return false;
    }
}