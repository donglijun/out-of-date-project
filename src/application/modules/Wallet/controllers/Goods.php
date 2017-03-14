<?php
class GoodsController extends ApiController
{
    protected $authActions = array(
        'send',
        'account',
        'history',
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

    protected $redisChat;

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

    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = Daemon::getMemcached('memcached-front', 'memcached-front');
        }

        return $this->cache;
    }

    public function sendAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $config = Yaf_Registry::get('config')->toArray();
        $defaultRate = $config['wallet']['gold']['withdraw']['rate'];

        $this->getStreamingDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $goods = (int) $request->get('goods', 0);
        $number = (int) $request->get('number', 0);
        $channel = $request->get('channel', 0);

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $goodsGoodsModel = new MySQL_Goods_GoodsModel($this->streamingDb);
        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);

        $goldAccountInfo = $goldAccountModel->getRow($userid);

        if (!($channelInfo = $streamingChannelModel->getRow($channel, array('class')))) {
            $result['code'] = 404;
            $result['error'][] = array(
                'message' => 'Invalid channel',
            );
        } else if (!($row = $goodsGoodsModel->getRow($goods))) {
            $result['code'] = 404;
            $result['error'][] = array(
                'message' => 'Invalid goods',
            );
        } else if ($number <= 0) {
            $result['code'] = 404;
            $result['error'][] = array(
                'message' => 'Invalid number',
            );
        } else if (($cost = $row['price'] * $number) > $goldAccountInfo['recharge_num']) {
            $result['code'] = 403;
            $result['error'][] = array(
                'message' => 'Not enough golds',
            );
        } else {
            try {
                $withdrawRate = 0;

                $this->streamingDb->beginTransaction();

                $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);
                if ($classRow = $streamingChannelClassModel->getRow($channelInfo['class'])) {
                    $withdrawRate = $classRow['withdraw_rate'];
                }

                // Log goods
                $goodsLogModel = new MySQL_Goods_LogModel($this->streamingDb);
                $goodsLogModel->insert(array(
                    'sender'        => $userid,
                    'receiver'      => $channel,
                    'goods'         => $goods,
                    'number'        => $number,
                    'golds'         => $cost,
                    'withdraw_rate' => $withdrawRate,
                    'created_on'    => $timestamp,
                ));

                // Handle golds
                $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);

                $goldAccountModel->consume($userid, $cost);
                $goldLogModel->insert(array(
                    'user'     => $userid,
                    'number'   => $cost * -1,
                    'type'     => MySQL_Gold_LogModel::LOG_TYPE_CONSUME,
                    'dealt_on' => $timestamp,
                ));

                $goldAccountModel->earn($channel, $cost, MySQL_Gold_AccountModel::numToMoney($cost) * $withdrawRate);
                $goldLogModel->insert(array(
                    'user'     => $channel,
                    'number'   => $cost,
                    'type'     => MySQL_Gold_LogModel::LOG_TYPE_EARN,
                    'dealt_on' => $timestamp,
                ));

                // Site user ranking
                $redisGoldRankingSiteUserDailyModel = new Redis_Gold_Ranking_Site_User_DailyModel($this->redisStreaming);
                $redisGoldRankingSiteUserDailyModel->incr($userid, $cost, $timestamp);
                $redisGoldRankingSiteUserWeeklyModel = new Redis_Gold_Ranking_Site_User_WeeklyModel($this->redisStreaming);
                $redisGoldRankingSiteUserWeeklyModel->incr($userid, $cost, $timestamp);
                $redisGoldRankingSiteUserMonthlyModel = new Redis_Gold_Ranking_Site_User_MonthlyModel($this->redisStreaming);
                $redisGoldRankingSiteUserMonthlyModel->incr($userid, $cost, $timestamp);

                // Site channel ranking
                $redisGoldRankingSiteChannelDailyModel = new Redis_Gold_Ranking_Site_Channel_DailyModel($this->redisStreaming);
                $redisGoldRankingSiteChannelDailyModel->incr($channel, $cost, $timestamp);
                $redisGoldRankingSiteChannelWeeklyModel = new Redis_Gold_Ranking_Site_Channel_WeeklyModel($this->redisStreaming);
                $redisGoldRankingSiteChannelWeeklyModel->incr($channel, $cost, $timestamp);
                $redisGoldRankingSiteChannelMonthlyModel = new Redis_Gold_Ranking_Site_Channel_MonthlyModel($this->redisStreaming);
                $redisGoldRankingSiteChannelMonthlyModel->incr($channel, $cost, $timestamp);

                // Channel ranking
                $redisGoldRankingChannelDailyModel = new Redis_Gold_Ranking_Channel_DailyModel($this->redisStreaming);
                $redisGoldRankingChannelDailyModel->incr($channel, $userid, $cost, $timestamp);
                $redisGoldRankingChannelWeeklyModel = new Redis_Gold_Ranking_Channel_WeeklyModel($this->redisStreaming);
                $redisGoldRankingChannelWeeklyModel->incr($channel, $userid, $cost, $timestamp);
                $redisGoldRankingChannelMonthlyModel = new Redis_Gold_Ranking_Channel_MonthlyModel($this->redisStreaming);
                $redisGoldRankingChannelMonthlyModel->incr($channel, $userid, $cost, $timestamp);

                // Goods account
                $redisGoodsChannelTotalModel = new Redis_Goods_Channel_TotalModel($this->redisStreaming);
                $redisGoodsChannelTotalModel->incrBy($channel, $goods, $number);

                // Broadcast to channel
                $data = array(
                    'from'      => array(
                        'id'    => $userid,
                        'name'  => $currentUser['name'],
                    ),
                    'goods'          => $goods,
                    'number'         => $number,
                    'target_channel' => $channel,
                    'timestamp'      => $timestamp,
                    'color'          => $request->get('color', 1),
                );
                $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                $redisStreamingChatChannelModel->publishGoods($channel, $data);

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

    public function listAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $goodsGoodsModel = new MySQL_Goods_GoodsModel($this->streamingDb);
        $result['data'] = $goodsGoodsModel->getAllActive(array(
            'id',
            'title',
            'price',
            'description',
            'slogan',
            'effect_trigger',
            'rarity',
        ));

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function groupsAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $goodsGroupModel = new MySQL_Goods_GroupModel($this->streamingDb);
        $result['data'] = $goodsGroupModel->getAll(array(
            'id',
            'number',
            'title',
            'description',
        ));

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_site_user_todayAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGoldRankingSiteUserDailyModel = new Redis_Gold_Ranking_Site_User_DailyModel($this->redisStreaming);
        if ($ranking = $redisGoldRankingSiteUserDailyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $data = $userAccountModel->getRows($ids, array('id', 'name'));

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            foreach ($data as $key => $val) {
                $data[$key]['golds'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_site_user_weeklyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGoldRankingSiteUserWeeklyModel = new Redis_Gold_Ranking_Site_User_WeeklyModel($this->redisStreaming);
        if ($ranking = $redisGoldRankingSiteUserWeeklyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $data = $userAccountModel->getRows($ids, array('id', 'name'));

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            foreach ($data as $key => $val) {
                $data[$key]['golds'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_site_user_monthlyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGoldRankingSiteUserMonthlyModel = new Redis_Gold_Ranking_Site_User_MonthlyModel($this->redisStreaming);
        if ($ranking = $redisGoldRankingSiteUserMonthlyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $data = $userAccountModel->getRows($ids, array('id', 'name'));

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            foreach ($data as $key => $val) {
                $data[$key]['golds'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_channel_todayAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        if ($channel = $request->get('channel')) {
            $this->getRedisStreaming();
            $this->getStreamingDb();
            $this->getPassportDb();

            $redisGoldRankingChannelDailyModel = new Redis_Gold_Ranking_Channel_DailyModel($this->redisStreaming);
            if ($ranking = $redisGoldRankingChannelDailyModel->top($channel, $limit)) {
                $ids = array_keys($ranking);
                $avatars = array();

                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $data = $userAccountModel->getRows($ids, array('id', 'name'));

                $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
                foreach ($userProfiles as $row) {
                    $avatars[$row['user']] = $row['avatar'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['golds'] = $ranking[$val['id']];
                    $data[$key]['avatar'] = $avatars[$val['id']];
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

    public function top_channel_weeklyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        if ($channel = $request->get('channel')) {
            $this->getRedisStreaming();
            $this->getStreamingDb();
            $this->getPassportDb();

            $redisGoldRankingChannelWeeklyModel = new Redis_Gold_Ranking_Channel_WeeklyModel($this->redisStreaming);
            if ($ranking = $redisGoldRankingChannelWeeklyModel->top($channel, $limit)) {
                $ids = array_keys($ranking);
                $avatars = array();

                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $data = $userAccountModel->getRows($ids, array('id', 'name'));

                $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
                foreach ($userProfiles as $row) {
                    $avatars[$row['user']] = $row['avatar'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['golds'] = $ranking[$val['id']];
                    $data[$key]['avatar'] = $avatars[$val['id']];
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

    public function top_channel_monthlyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        if ($channel = $request->get('channel')) {
            $this->getRedisStreaming();
            $this->getStreamingDb();
            $this->getPassportDb();

            $redisGoldRankingChannelMonthlyModel = new Redis_Gold_Ranking_Channel_MonthlyModel($this->redisStreaming);
            if ($ranking = $redisGoldRankingChannelMonthlyModel->top($channel, $limit)) {
                $ids = array_keys($ranking);
                $avatars = array();

                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $data = $userAccountModel->getRows($ids, array('id', 'name'));

                $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
                foreach ($userProfiles as $row) {
                    $avatars[$row['user']] = $row['avatar'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['golds'] = $ranking[$val['id']];
                    $data[$key]['avatar'] = $avatars[$val['id']];
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

    public function latest_channel_userAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = $where = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        if ($channel = $request->get('channel')) {
            $this->getRedisStreaming();
            $this->getStreamingDb();
            $this->getPassportDb();

            $where[] = '`receiver`=' . (int) $channel;
            $where = $where ? implode(' AND ', $where) : '';

            $goodsLogModel = new MySQL_Goods_LogModel($this->streamingDb);
            $searchResult = $goodsLogModel->search('*', $where, '`id` DESC', 0, $limit);
            if ($data = $searchResult['data']) {
                $ids = $avatars = $names = array();

                foreach ($data as $row) {
                    $ids[] = $row['sender'];
                }

                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $userAccounts = $userAccountModel->getRows($ids, array('id', 'name'));
                foreach ($userAccounts as $row) {
                    $names[$row['id']] = $row['name'];
                }

                $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
                foreach ($userProfiles as $row) {
                    $avatars[$row['user']] = $row['avatar'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['sender_name'] = $names[$val['sender']];
                    $data[$key]['sender_avatar'] = $avatars[$val['sender']];
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

    public function accountAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getRedisStreaming();
        $redisGoodsChannelTotalModel = new Redis_Goods_Channel_TotalModel($this->redisStreaming);
        $result['data'] = $redisGoodsChannelTotalModel->getAll($userid);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function historyAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $goodsLogModel = new MySQL_Goods_LogModel($this->streamingDb);
        $result['data'] = $goodsLogModel->getHistoryByDay($userid, 30, $tzOffset ?: 2);

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }


    public function top_site_channel_todayAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGoldRankingSiteChannelDailyModel = new Redis_Gold_Ranking_Site_Channel_DailyModel($this->redisStreaming);
        if ($ranking = $redisGoldRankingSiteChannelDailyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $data = $streamingChannelModel->getRows($ids, array(
                'id',
                'title',
                'is_online',
                'owner_name',
            ));

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            foreach ($data as $key => $val) {
                $data[$key]['golds'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_site_channel_weeklyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGoldRankingSiteChannelWeeklyModel = new Redis_Gold_Ranking_Site_Channel_WeeklyModel($this->redisStreaming);
        if ($ranking = $redisGoldRankingSiteChannelWeeklyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $data = $streamingChannelModel->getRows($ids, array(
                'id',
                'title',
                'is_online',
                'owner_name',
            ));

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            foreach ($data as $key => $val) {
                $data[$key]['golds'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function top_site_channel_monthlyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();
        $limit = $request->get('limit', 0) ?: 10;
        $limit = min($limit, 100);

        $this->getRedisStreaming();
        $this->getStreamingDb();
        $this->getPassportDb();

        $redisGoldRankingSiteChannelMonthlyModel = new Redis_Gold_Ranking_Site_Channel_MonthlyModel($this->redisStreaming);
        if ($ranking = $redisGoldRankingSiteChannelMonthlyModel->top($limit)) {
            $ids = array_keys($ranking);
            $avatars = array();

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $data = $streamingChannelModel->getRows($ids, array(
                'id',
                'title',
                'is_online',
                'owner_name',
            ));

            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $userProfiles = $userProfileModel->getRows($ids, array('user', 'avatar'));
            foreach ($userProfiles as $row) {
                $avatars[$row['user']] = $row['avatar'];
            }

            foreach ($data as $key => $val) {
                $data[$key]['golds'] = $ranking[$val['id']];
                $data[$key]['avatar'] = $avatars[$val['id']];
            }
        }

        $result['data'] = $data;
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}