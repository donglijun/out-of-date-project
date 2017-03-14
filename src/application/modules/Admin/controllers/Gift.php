<?php
class GiftController extends AdminController
{
    protected $authActions = array(
        'list'           => MySQL_AdminAccountModel::GROUP_ADMIN,
        'report_total'   => MySQL_AdminAccountModel::GROUP_ADMIN,
        'report_channel' => MySQL_AdminAccountModel::GROUP_ADMIN,
        'channel_log'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'user_log'       => MySQL_AdminAccountModel::GROUP_ADMIN,
        'get_races'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'new_race'       => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'cancel_race'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'top'            => MySQL_AdminAccountModel::GROUP_ADMIN,
        'growth_schemes' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'create_growth_scheme' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update_growth_scheme' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $streamingDb;

    protected $passportDb;

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

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function gotoEditGrowthScheme($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gift/edit-growth-scheme.phtml'));
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
            $where[] = "`id`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
        $result = $giftAccountModel->search('*', $where, '`id` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gift/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function report_totalAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');

        $dateFrom = date('Ymd', strtotime($from));
        $dateTo = date('Ymd', strtotime($to));

//        $giftReportTotalDailyModel = new MySQL_Gift_Report_TotalDailyModel($this->streamingDb);
        $giftReportTotalDailyModel = new MySQL_Gift_Report_Total_DailyModel($this->streamingDb);
        $data = $giftReportTotalDailyModel->between($dateFrom, $dateTo);

        $this->_view->assign(array(
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gift/report-total.phtml'));

        return false;
    }

    public function report_channelAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();

        $from = $request->get('from', '');
        $to   = $request->get('to', '');
        $channel = $request->get('channel', '');

        $dateFrom = date('Ymd', strtotime($from));
        $dateTo = date('Ymd', strtotime($to));

//        $giftReportChannelDailyModel = new MySQL_Gift_Report_ChannelDailyModel($this->streamingDb);
        $giftReportChannelDailyModel = new MySQL_Gift_Report_Channel_DailyModel($this->streamingDb);
        $data = $giftReportChannelDailyModel->between($dateFrom, $dateTo, $channel);

        $this->_view->assign(array(
            'from'    => $from,
            'to'      => $to,
            'channel' => $channel,
            'data'    => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gift/report-channel.phtml'));

        return false;
    }

    public function channel_logAction()
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
        if ($channel = $request->get('channel')) {
            $where[] = "`channel`=" . (int) $channel;
            $filter['channel'] = $channel;
        }
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);
        $result = $giftChannelLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gift/channel_log?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gift/channel-log.phtml'));

        return false;
    }

    public function user_logAction()
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
        $where[] = "`number`>0";
        $where = $where ? implode(' AND ', $where) : '';

        $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
        $result = $giftUserLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gift/user_log?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gift/user-log.phtml'));

        return false;
    }

    public function get_racesAction()
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
        $where = $where ? implode(' AND ', $where) : '';

        $giftRaceModel = new MySQL_Gift_RaceModel($this->streamingDb);
        $result = $giftRaceModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gift/get_races?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('gift/races.phtml'));

        return false;
    }

    public function new_raceAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 500,
        );

        $fromDate = $request->get('from_date');
        $fromTime = $request->get('from_time');
        $toDate = $request->get('to_date');
        $toTime = $request->get('to_time');

        $fromDatetime = strtotime($fromDate . ' ' . $fromTime);
        $toDatetime = strtotime($toDate . ' ' . $toTime);

        if ($fromDatetime && $toDatetime) {
            $data = array(
                'id'         => date('YmdH', $fromDatetime),
                'from'       => $fromDatetime,
                'to'         => $toDatetime,
                'created_on' => time(),
            );

            try {
                $this->getStreamingDb();

                $giftRaceModel = new MySQL_Gift_RaceModel($this->streamingDb);
                $giftRaceModel->insert($data);

                $result['code'] = 200;
            } catch (Exception $e) {
                $result['message'] = 'Duplicate from datetime';
            }
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/gift/get_races');

        return false;
    }

    public function cancel_raceAction()
    {
        $request = $this->getRequest();

        if ($ids = Misc::parseIds($request->get('ids'))) {
            $this->getStreamingDb();

            $giftRaceModel = new MySQL_Gift_RaceModel($this->streamingDb);

            $affectedCount = $giftRaceModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/gift/get_races');

        return false;
    }

    public function topAction()
    {
        $request = $this->getRequest();
        $data = $filter = $channels = array();
        $dateFrom = $dateTo = 0;
        $today = date('Y-m-d');

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        if ($from = $request->get('from', $today)) {
            $filter['from'] = $from;

            $dateFrom = strtotime($from);
        }

        if ($to = $request->get('to', $today)) {
            $filter['to'] = $to;

            $dateTo = strtotime($to);
            $dateTo = strtotime('+1 day', $dateTo);
        }

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;
        $filter['limit'] = $limit;

        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);
        $ranking = $giftChannelLogModel->sumByChannel($dateFrom, $dateTo);
        $ranking = array_slice($ranking, 0, $limit);

        foreach ($ranking as $row) {
            $channels[] = $row['channel'];
            $map[$row['channel']] = $row['sum'];
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
        }

        $this->getView()->assign(array(
            'filter' => $filter,
            'data'   => $data,
        ));
    }

    public function growth_schemesAction()
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

        $giftGrowthSchemeModel = new MySQL_Gift_GrowthSchemeModel($this->streamingDb);
        $result = $giftGrowthSchemeModel->search('*', $where, '`id` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/gift/growth_schemes?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->_view->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->_view->render('gift/growth-schemes.phtml'));

        return false;
    }

    public function create_growth_schemeAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        if ($request->isPost()) {
            $data = array(
                'title'      => $request->get('title', ''),
                'level'      => $request->get('level', 0),
                'points'      => $request->get('points', 0),
                'created_on' => time(),
            );

            $giftGrowthSchemeModel = new MySQL_Gift_GrowthSchemeModel($this->streamingDb);
            $giftGrowthSchemeModel->insert($data);

            $this->redirect('/admin/gift/growth_schemes');

            return false;
        }

        $this->gotoEditGrowthScheme($request->getActionName(), $data);

        return false;
    }

    public function update_growth_schemeAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        $id = $request->get('id', 0);
        if ($id) {
            $giftGrowthSchemeModel = new MySQL_Gift_GrowthSchemeModel($this->streamingDb);

            if ($request->isPost()) {
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if ($level = $request->get('level')) {
                    $data['level'] = $level;
                }

                if ($points = $request->get('points')) {
                    $data['points'] = $points;
                }

                if ($data) {
                    $affectedCount = $giftGrowthSchemeModel->update($id, $data);
                }

                $this->redirect('/admin/gift/growth_schemes');

                return false;
            } else {
                $data = $giftGrowthSchemeModel->getRow($id);

                $this->_view->assign(array(
                    'id'    => $id,
                ));
            }

            $this->gotoEditGrowthScheme($request->getActionName(), $data);
        } else {
            $this->forward('create_growth_scheme');
        }

        return false;
    }
}