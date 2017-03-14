<?php
class RedController extends AdminController
{
    protected $authActions = array(
        'list'            => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'view'            => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'log'             => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'publish'         => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'schedules'       => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'new_schedule'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'view_schedule'   => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'cancel_schedule' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'summary'         => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $streamingDb;

    protected $redisStreaming;

    protected $redisChat;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
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

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
        $result = $redRedModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/red/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['clients'] = MySQL_Red_RedModel::getClientMap();

        $this->getView()->assign($result);
    }

    public function logAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($red = $request->get('red')) {
            $where[] = "`red`=" . (int) $red;
            $filter['red'] = $red;
        }
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $redLogModel = new MySQL_Red_LogModel($this->streamingDb);
        $result = $redLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/red/log?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $id = $request->get('id', 0);

        if ($id) {
            $this->getStreamingDb();

            $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
            $data = $redRedModel->getRow($id);

            $redLogModel = new MySQL_Red_LogModel($this->streamingDb);
            $log = $redLogModel->members($id);
        }

        $this->_view->assign(array(
            'data'    => $data,
            'log'     => $log,
            'clients' => MySQL_Red_RedModel::getClientMap(),
        ));
    }

//    public function typesAction()
//    {
//        $request = $this->getRequest();
//        $where = array();
//
//        $this->getStreamingDb();
//        $config = Yaf_Registry::get('config')->toArray();
//
//        $page = intval($request->get('page', 0));
//        $page = $page < 1 ? 1 : $page;
//
//        $limit = intval($request->get('limit', 0));
//        $limit = $limit ?: 50;
//
//        $offset = ($page - 1) * $limit;
//
//        $filter['page'] = '0page0';
//
//        $where = $where ? implode(' AND ', $where) : '';
//
//        $redTypeModel = new MySQL_Red_TypeModel($this->streamingDb);
//        $result = $redTypeModel->search('*', $where, '`id` DESC', $offset, $limit);
//
//        $result['filter'] = $filter;
//        $result['pageUrlPattern'] = '/admin/red/types?' . http_build_query($filter);
//
//        $paginator = Zend_Paginator::factory($result['total_found']);
//        $paginator->setCurrentPageNumber($page)
//            ->setItemCountPerPage($limit)
//            ->setPageRange(10);
//        $result['paginator'] = $paginator;
//
//        $this->getView()->assign($result);
//    }

    public function publishAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $result = array(
            'code'  => 500,
        );

        $userid = $this->session->admin['user'];
        $username = $this->session->admin['name'];

        $this->getStreamingDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $points = (int) $request->get('points', 0);
        $number = (int) $request->get('number', 0);
        $memo = strip_tags($request->get('memo', ''));
        $targetClient = (int) $request->get('target_client', 0);
//        $channel = $request->get('channel', 0);
        $channel = 0;

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

        if ($number <= 0) {
            $result['code'] = 411;
            $result['message'] = 'Invalid number';
        } else if ($points < $number) {
            $result['code'] = 412;
            $result['message'] = 'Invalid points';
        } else if ($points > $pointAccountModel->number($userid)) {
            $result['code'] = 413;
            $result['message'] = 'Lack of balance';
        } else {
            try {
                $this->streamingDb->beginTransaction();

                $hash = uniqid();

                $redID = $redRedModel->insert(array(
                    'user'           => $userid,
                    'name'           => $username,
                    'points'         => $points,
                    'number'         => $number,
                    'memo'           => $memo,
                    'target_channel' => $channel,
                    'target_client'  => $targetClient,
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
                        'id'    => 0,
                        'name'  => '',
                    ),
                    'id'             => $redID,
                    'number'         => $number,
//                    'points'         => $points,
                    'memo'           => $memo,
                    'target_channel' => $channel,
                    'target_client'  => $targetClient,
                    'hash'           => $hash,
                    'expires'        => strtotime('+24 hour', $timestamp),
                    'timestamp'      => $timestamp,
//                    'color'          => $request->get('color', 1),
                );

                $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
                $channels = $redisStreamingChannelOnlineChannelModel->getList();

                $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
                $redisStreamingChatChannelModel->publishRed($channels, $data);

                $this->streamingDb->commit();

                $result['code'] = 200;
                $result['message'] = 'ok';
            } catch (Exception $e) {
                $this->streamingDb->rollBack();

                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        }

        echo json_encode($result);

        return false;
    }

    public function schedulesAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $where = $where ? implode(' AND ', $where) : '';

        $redScheduleModel = new MySQL_Red_ScheduleModel($this->streamingDb);
        $result = $redScheduleModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/red/schedules?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['publishStatusMessages'] = $redScheduleModel->publishStatusMessages();

        $result['clients'] = MySQL_Red_RedModel::getClientMap();

        $this->getView()->assign($result);
    }

    public function new_scheduleAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $result = array(
            'code'  => 500,
        );

        $userid = $this->session->admin['user'];
        $username = $this->session->admin['name'];

        $this->getStreamingDb();

        $points = (int) $request->get('points', 0);
        $number = (int) $request->get('number', 0);
        $memo = strip_tags($request->get('memo', ''));
        $targetClient = (int) $request->get('target_client', 0);
        $date = $request->get('date');
        $time = $request->get('time');
        $channel = 0;

        $datetime = strtotime($date . ' ' . $time);

        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

        if ($number <= 0) {
            $result['code'] = 411;
            $result['message'] = 'Invalid number';
        } else if ($points <= 0) {
            $result['code'] = 412;
            $result['message'] = 'Invalid points';
        } else if ($points > $pointAccountModel->number($userid)) {
            $result['code'] = 413;
            $result['message'] = 'Lack of balance';
        } else {
            $data = array(
                'points'         => $points,
                'number'         => $number,
                'memo'           => $memo,
                'target_channel' => $channel,
                'target_client'  => $targetClient,
                'publish_on'     => $datetime,
                'created_on'     => $timestamp,
                'created_by'     => $userid,
                'created_name'   => $username,
            );

            $redScheduleModel = new MySQL_Red_ScheduleModel($this->streamingDb);
            $redScheduleModel->insert($data);

            $result['code'] = 200;
            $result['message'] = 'ok';
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/red/schedules');

        return false;
    }

    public function view_scheduleAction()
    {
        $request = $this->getRequest();
        $data = array();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $redScheduleModel = new MySQL_Red_ScheduleModel($this->streamingDb);

            $data = $redScheduleModel->getRow($id);
        }

        $this->_view->assign(array(
            'data'                  => $data,
            'publishStatusMessages' => $redScheduleModel->publishStatusMessages(),
            'clients'               => MySQL_Red_RedModel::getClientMap(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('red/view-schedule.phtml'));

        return false;
    }

    public function cancel_scheduleAction()
    {
        $request = $this->getRequest();

        if ($ids = Misc::parseIds($request->get('ids'))) {
            $this->getStreamingDb();

            $redScheduleModel = new MySQL_Red_ScheduleModel($this->streamingDb);

            $affectedCount = $redScheduleModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/red/schedules');

        return false;
    }

    public function summaryAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();
        list($tzDiff, $tzOffset, ) = Misc::timezoneOffset();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        $redRedModel = new MySQL_Red_RedModel($this->streamingDb);
        $result = $redRedModel->summary($offset, $limit, $tzOffset ?: 2);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/red/summary?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}