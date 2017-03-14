<?php
use Aws\S3\S3Client;

class StreamingchannelController extends AdminController
{
    protected $authActions = array(
        'list'              => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'            => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'            => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'view'              => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'ban'               => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'setclientone'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'pushschedule'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'cancelschedule'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'getschedules'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'history'           => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'memo'              => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'times'             => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'lengths'           => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'watching_lengths'  => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'live_log'          => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

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

    protected function gotoEdit($action, $data)
    {
        $this->getStreamingDb();
        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);

        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
            'classes'   => $streamingChannelClassModel->getAll(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingchannel/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $filter = array();

//        $this->getRedisVideo();
        $this->getStreamingDb();
        $this->getRedisStreaming();
//        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;
        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $search_field = $request->get('search_field', '');
        if ($search_field) {
            $filter['search_field'] = $search_field;
        }
        $search_value = $request->get('search_value', '');
        if ($search_value) {
            $where[] = $streamingChannelModel->quoteIdentifier($search_field) . '=' . $this->streamingDb->quote(strtolower(trim($search_value)));
            $filter['search_value'] = $search_value;
        }
        if ($isOnline = $request->get('is_online')) {
            $where[] = "`is_online`=1";
            $filter['is_online'] = 1;
        }
        if ($isBanned = $request->get('is_banned')) {
            $where[] = "`is_banned`=1";
            $filter['is_banned'] = 1;
        }
        if ($isSigned = $request->get('is_signed')) {
            $where[] = "`is_signed`=1";
            $filter['is_signed'] = 1;
        }
        if ($isExclusive = $request->get('is_exclusive')) {
            $where[] = "`is_exclusive`=1";
            $filter['is_exclusive'] = 1;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
        $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//        $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
//        $redisStreamingChannelPausedModel = new Redis_Streaming_Channel_PausedModel($this->redisStreaming);
        $result = $streamingChannelModel->search('*', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $key => $row) {
            $result['data'][$key]['stream_key'] = MySQL_Streaming_ChannelModel::makeStreamKey($row['id'], $row['hash']);
//            $result['data'][$key]['total_views'] = (int) $redisStreamingChannelTotalViewsModel->get($row['id']);
//            $result['data'][$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($row['id']);
            $result['data'][$key]['watching_now'] = (int) $redisStreamingChannelOnlineClientByChannelModel->get($row['id']);
//            $result['data'][$key]['paused'] = (int) $redisStreamingChannelPausedModel->count($row['id']);
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingchannel/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $redisStreamingClientOneModel = new Redis_Streaming_ClientOneModel($this->getRedisStreaming());
        $result['clientone'] = $redisStreamingClientOneModel->get();

        $classes = array();
        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);
        foreach ($streamingChannelClassModel->getAll(array('id', 'title')) as $row) {
            $classes[$row['id']] = $row['title'];
        }
        $result['classes'] = $classes;

        $this->getView()->assign($result);
    }

    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getPassportDb();
        $this->getStreamingDb();

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);

        if ($request->isPost() && ($id = $request->get('id', ''))) {
            $userid = $username = null;
            if (is_numeric($id)) {
                $userid = $id;
//                if ($userInfo = $mkjogoUserModel->getRow($id, array('username'))) {
                if ($userInfo = $userAccountModel->getRow($id, array('name'))) {
                    $username = $userInfo['name'];
                }
            } else {
                $username = $id;
                $userid = $userAccountModel->getIdByName($username);
            }

            if ($userid && $username) {
                $data = array(
                    'id'            => $userid,
                    'owner_name'    => $username,
                    'created_on'    => $request->getServer('REQUEST_TIME'),
                );

                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if (($class = $request->get('class')) && $streamingChannelClassModel->exists($class)) {
                    $data['class'] = $class;
                }

                if ($special = $request->get('special')) {
                    $data['special'] = $special;
                }

                $data['is_signed'] = $request->get('is_signed', 0) ? 1 : 0;

                $data['is_exclusive'] = $request->get('is_exclusive', 0) ? 1 : 0;

                if ($streamingChannelModel->insert($data)) {
                    $streamingChannelModel->resetStreamKey($userid);

                    // Add owner as editor
                    $data = array(
                        'channel'       => $userid,
                        'user'          => $userid,
                        'name'          => $username,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );
                    $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
                    $streamingEditorModel->insert($data);

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

                $this->redirect('/admin/streamingchannel/list');
            } else {
                echo "Invalid user: " . $id;
            }

            return false;
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        $id = $request->get('id', 0);
        if ($id) {
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);

            if ($request->isPost()) {
                if ($title = $request->get('title', '')) {
                    $data['title'] = $title;
                }

                if (($class = $request->get('class')) && $streamingChannelClassModel->exists($class)) {
                    $data['class'] = $class;
                }

                $data['special'] = $request->get('special');

                $data['is_signed'] = $request->get('is_signed', 0) ? 1 : 0;

                $data['is_exclusive'] = $request->get('is_exclusive', 0) ? 1 : 0;

                if ($data) {
                    $affectedCount = $streamingChannelModel->update($id, $data);
                }

                $this->redirect('/admin/streamingchannel/list');

                return false;
            } else {
                $data = $streamingChannelModel->getRow($id);

                $this->getview()->assign(array(
                    'id'    => $id,
                ));
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $channel = $request->get('channel', 0);

        if ($channel) {
            $this->getStreamingDb();
            $this->getRedisStreaming();
            $this->getS3();
            $config = Yaf_Registry::get('config')->toArray();

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $redisStreamingChannelTotalViewsModel = new Redis_Streaming_Channel_TotalViewsModel($this->redisStreaming);
            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
//            $redisStreamingChannelPausedModel = new Redis_Streaming_Channel_PausedModel($this->redisStreaming);

            if ($data = $streamingChannelModel->getRow($channel, $streamingChannelModel->getFields())) {
                $data['stream_key'] = MySQL_Streaming_ChannelModel::makeStreamKey($channel, $data['hash']);
                $data['total_views'] = (int) $redisStreamingChannelTotalViewsModel->get($channel);
//                $data['watching_now'] = (int) $redisStreamingChannelOnlineClientModel->total($channel);
                $data['watching_now'] = (int) $redisStreamingChannelOnlineClientByChannelModel->get($channel);
//                $data['paused'] = (int) $redisStreamingChannelPausedModel->count($channel);

                if ($data['small_show_image']) {
                    $data['small_show_image_url'] = $this->s3->getObjectUrl(
                        $config['aws']['s3']['bucket']['streaming'],
                        $data['small_show_image'],
                        '+30 minutes'
                    );
                }

                if ($data['large_show_image']) {
                    $data['large_show_image_url'] = $this->s3->getObjectUrl(
                        $config['aws']['s3']['bucket']['streaming'],
                        $data['large_show_image'],
                        '+30 minutes'
                    );
                }

            }
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));
    }

    public function banAction()
    {
        $request = $this->getRequest();

        $ids = Misc::parseIds($request->get('ids'));
        $status = $request->get('status') ? 1 : 0;

        $this->getStreamingDb();
        $this->getRedisStreaming();

        if ($ids) {
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $streamingChannelModel->ban($ids, $status);

            $config = Yaf_Registry::get('config')->toArray();
            $rtmp = Daemon::getRtmpClient();

            $rows = $streamingChannelModel->getRows($ids, array('hash', 'is_online', 'upstream_ip'));
            foreach ($rows as $row) {
                if ($row['is_online']) {
                    try {
                        $streamKey = $streamingChannelModel->makeStreamKey($row['id'], $row['hash']);

                        $rtmp->connect($row['upstream_ip'], $config['rtmp-client']['application'],
                            $config['rtmp-client']['port'], $config['rtmp-client']['params']);
                        $rtmp->call('Drop', array('proxypublish', $streamKey));
                    } catch (Exception $e) {
                        Misc::log(sprintf('Drop stream error: %s', $e->getMessage()), Zend_Log::ERR);
                    }

                    $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
                    $redisStreamingChannelOnlineChannelModel->close($row['id']);

                    $streamingChannelModel->offline($row['id']);
                }
            }

            $rtmp->close();
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingchannel/list');

        return false;
    }

    public function setclientoneAction()
    {
        $request = $this->getRequest();

        if ($channel = $request->get('channel')) {
            $redisStreamingClientOneModel = new Redis_Streaming_ClientOneModel($this->getRedisStreaming());

            $redisStreamingClientOneModel->set($channel);
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingchannel/list');

        return false;
    }

    public function pushscheduleAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 200,
        );

        $channel = $request->get('channel');
        $dates = $request->get('dates');
        $time = $request->get('time');

        $dates = strtr($dates, array(
            "\r"    => "\n",
        ));
        $dates = preg_split('|\s+|', $dates);

        if ($channel && $dates) {
            $streamingPushScheduleModel = new MySQL_Streaming_PushScheduleModel($this->getStreamingDb());

            foreach ($dates as $date) {
                if ($date) {
                    $datetime = strtotime($date . ' ' . $time);

                    $data = array(
                        'channel' => $channel,
                        'push_on' => $datetime,
                    );
                    $streamingPushScheduleModel->insert($data);
                }
            }

            $result['code'] = 200;
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingchannel/list');

        return false;
    }

    public function cancelscheduleAction()
    {
        $request = $this->getRequest();

        if ($ids = Misc::parseIds($request->get('ids'))) {
            $streamingPushScheduleModel = new MySQL_Streaming_PushScheduleModel($this->getStreamingDb());

            $affectedCount = $streamingPushScheduleModel->delete($ids);
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
                'data'  => array(
                    'affected'  => $affectedCount,
                ),
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingchannel/getschedules');

        return false;
    }

    public function getschedulesAction()
    {
        $request = $this->getRequest();

        $streamingPushScheduleModel = new MySQL_Streaming_PushScheduleModel($this->getStreamingDb());

        $this->getView()->assign(array(
            'data'  => $streamingPushScheduleModel->getFuture(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingchannel/schedules.phtml'));

        return false;
    }

    public function historyAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($channel = $request->get('channel')) {
            $where[] = "`channel`=" . $this->streamingDb->quote($channel);
            $filter['channel'] = $channel;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);
        $result = $streamingLiveLengthLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingchannel/history?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function memoAction()
    {
        $result = array();
        $request = $this->getRequest();

        if (($id = $request->get('id')) && ($memo = $request->get('memo'))) {
            $this->getStreamingDb();
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

            $streamingChannelModel->update($id, array(
                'memo'  => $memo,
            ));

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingchannel/list');

        return false;
    }

    public function timesAction()
    {
        $request = $this->getRequest();
        $data = $filter = $campaignResult = $ids = array();
        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');

        $this->getStreamingDb();

        if ($channel = $request->get('channel')) {
            $filter['channel'] = $channel;
        }
        if ($times = $request->get('times')) {
            $filter['times'] = $times;
        }
        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);
        $campaignResult = $streamingLiveLengthLogModel->campaign($times, $dateFrom, $dateTo);

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
        $campaignResult2 = $streamingBroadcastModel->campaign($times, $dateFrom, $dateTo);

        $ids = array_keys($campaignResult);

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        if ($rows = $streamingChannelModel->getRows($ids, array('id', 'owner_name', 'is_online', 'memo'))) {
            foreach ($rows as $row) {
                $row['times'] = $campaignResult[$row['id']]['times'];
                $row['raw_lengths'] = $campaignResult[$row['id']]['lengths'];
                $row['fixed_lengths'] = isset($campaignResult2[$row['id']]) ? $campaignResult2[$row['id']]['lengths'] : '0';
                $data[$row['id']] = $row;
            }
        }

        if ($channel && isset($data[$channel])) {
            $row = $data[$channel];
            $data = array();
            $data[$row['id']] = $row;
        }

        $this->getView()->assign(array(
            'filter'    => $filter,
            'data'      => $data,
        ));
    }

    public function lengthsAction()
    {
        $request = $this->getRequest();
        $data = $filter = $campaignResult = $ids = array();
        $dateFrom = $dateTo = $minFragment = 0;
        $today = date('Y-m-d');

        $this->getStreamingDb();

        if ($channel = $request->get('channel')) {
            $filter['channel'] = $channel;
        }
        if ($minFragment = $request->get('min_fragment')) {
            $filter['min_fragment'] = $minFragment;
        }
        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);
        $lengthRanking = $streamingLiveLengthLogModel->lengthRanking($dateFrom, $dateTo, $minFragment);

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
        $lengthRanking2 = $streamingBroadcastModel->lengthRanking($dateFrom, $dateTo, $minFragment);

        $ids = array_keys($lengthRanking);

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        if ($rows = $streamingChannelModel->getRows($ids, array('id', 'owner_name', 'is_online', 'memo'))) {
            foreach ($rows as $row) {
                $row['times'] = $lengthRanking[$row['id']]['times'];
                $row['raw_lengths'] = $lengthRanking[$row['id']]['lengths'];
                $row['fixed_lengths'] = isset($lengthRanking2[$row['id']]) ? $lengthRanking2[$row['id']]['lengths'] : '0';
                $data[$row['id']] = $row;
            }
        }

        if ($channel && isset($data[$channel])) {
            $row = $data[$channel];
            $data = array();
            $data[$row['id']] = $row;
        }

        $this->getView()->assign(array(
            'filter'    => $filter,
            'data'      => $data,
        ));
    }

    public function watching_lengthsAction()
    {
        $request = $this->getRequest();

        $rows = $data = $filter = $ids = array();

        $dateFrom = $dateTo = $minFragment = 0;
        $today = date('Y-m-d');

        if ($channel = $request->get('channel')) {
            $filter['channel'] = $channel;
        }
        if ($minFragment = $request->get('min_fragment', 60)) {
            $filter['min_fragment'] = $minFragment;
        }
        if ($from = $request->get('from', date('Y-m-01'))) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }
        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        $this->getStreamingDb();

        $streamingWatchingLengthLogModel = new MySQL_Streaming_WatchingLengthLogModel($this->streamingDb);
        $rows = $streamingWatchingLengthLogModel->summaryByChannel($dateFrom, $dateTo, $minFragment);

        foreach ($rows as $row) {
            $data[$row['channel']] = $row;
        }

        if ($channel && isset($data[$channel])) {
            $row = $data[$channel];
            $data = array();
            $data[$row['channel']] = $row;
        }

        $this->getView()->assign(array(
            'filter' => $filter,
            'data' => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->_view->render('streamingchannel/watching-lengths.phtml'));

        return false;
    }

    public function live_logAction()
    {
        $result = $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();
        $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);

        if (($channel = $request->get('channel')) and ($ip = $request->get('ip')) and ($session = $request->get('session'))) {
            $this->getRedisStreaming();
            $redisStreamingChannelLogModel = new Redis_Streaming_Channel_LogModel($this->redisStreaming);

            $logs = $redisStreamingChannelLogModel->get($channel, $ip, $session);
            ksort($logs);

            foreach ($logs as $timestamp => $content) {
                $data[] = array(
                    'timestamp' => date('Y-m-d H:i:s', $timestamp),
                    'content' => $content,
                );
            }

            $result['data'] = $data;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($result);

        return false;
    }
}