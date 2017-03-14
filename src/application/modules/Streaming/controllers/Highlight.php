<?php
use Aws\S3\S3Client;

class HighlightController extends ApiController
{
    protected $authActions = array(
        'update',
        'delete',
        'my',
        'my_by_broadcast',
        'check_upload',
    );

    protected $streamingDb;

    protected $redisStreaming;

    protected $redisSession;

    protected $s3;

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

    protected function getRedisSession()
    {
        if (empty($this->redisSession)) {
            $this->redisSession = Daemon::getRedis('redis-session', 'redis-session');
        }

        return $this->redisSession;
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

    public function updateAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost() && ($id = $request->get('id'))) {
            $data = array();
            $this->getStreamingDb();

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);

            if (($highlightInfo = $streamingBroadcastHighlightModel->getRow($id, array('id', 'channel'))) && ($highlightInfo['channel'] == $userid)) {
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if ($memo = $request->get('memo')) {
                    $data['memo'] = $memo;
                }

                if ($data) {
                    $streamingBroadcastHighlightModel->update($id, $data);
                }

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

    public function deleteAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost() && ($id = $request->get('id'))) {
            $config = Yaf_Registry::get('config')->toArray();
            $data = array();

            $this->getStreamingDb();
            $this->getS3();

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);

            if (($highlightInfo = $streamingBroadcastHighlightModel->getRow($id)) && ($highlightInfo['channel'] == $userid)) {
                $this->s3->deleteObjects(array(
                    'Bucket'    => $config['aws']['s3']['bucket']['streaming'],
                    'Objects'   => array(
                        array(
                            'Key'   => $highlightInfo['remote_path'],
                        ),
                        array(
                            'Key'   => $highlightInfo['preview_path'],
                        ),
                    ),
                ));

                $streamingBroadcastHighlightModel->delete(array($id));

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

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

            $data = $streamingBroadcastHighlightModel->getRow($id, array(
                'id',
                'channel',
                'length',
                'title',
                'memo',
                'uploaded_on',
                'remote_path',
                'preview_path',
                'total_views',
            ));

            if ($data && ($row = $streamingChannelModel->getRow($data['channel'], array('owner_name')))) {
                $data['owner_name'] = $row['owner_name'];

                $streamingBroadcastHighlightModel->view($id);

                $this->getRedisStreaming();

                $redisStreamingBroadcastHighlightRankingDailyModel = new Redis_Streaming_Broadcast_Highlight_Ranking_DailyModel($this->redisStreaming);
                $redisStreamingBroadcastHighlightRankingDailyModel->incr($id);

                $redisStreamingBroadcastHighlightRankingWeeklyModel = new Redis_Streaming_Broadcast_Highlight_Ranking_WeeklyModel($this->redisStreaming);
                $redisStreamingBroadcastHighlightRankingWeeklyModel->incr($id);

                $redisStreamingBroadcastHighlightRankingMonthlyModel = new Redis_Streaming_Broadcast_Highlight_Ranking_MonthlyModel($this->redisStreaming);
                $redisStreamingBroadcastHighlightRankingMonthlyModel->incr($id);

                $result['data'] = $data;
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

    public function myAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $where = array();

        $where[] = '`channel`=' . (int) $userid;

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $result = $streamingBroadcastHighlightModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`uploaded_on`,`total_views`,`submitted_on`', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $key => $val) {
            $result['data'][$key]['owner_name'] = $currentUser['name'];
        }

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function my_by_broadcastAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($broadcast = $request->get('broadcast')) {
            $where = array();

            $where[] = '`channel`=' . (int) $userid;

            $where[] = '`broadcast`=' . (int) $broadcast;

            $where = $where ? implode(' AND ', $where) : '';

            $this->getStreamingDb();

            $page = intval($request->get('page', 0));
            $page = $page < 1 ? 1 : $page;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 20;

            $offset = ($page - 1) * $limit;

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $result = $streamingBroadcastHighlightModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`uploaded_on`,`total_views`,`submitted_on`', $where, '`id` DESC', $offset, $limit);

            foreach ($result['data'] as $key => $val) {
                $result['data'][$key]['owner_name'] = $currentUser['name'];
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function check_uploadAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $ids = $request->get('ids', '');

        if ($ids = Misc::parseIds($ids)) {
            $this->getStreamingDb();

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $result['data'] = $streamingBroadcastHighlightModel->getRows($ids, array(
                'id',
                'length',
                'preview_path',
                'uploaded_on',
            ));

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
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

        if ($channel = $request->get('channel')) {
            $where = array();

            $where[] = '`channel`=' . (int) $channel;
            $where[] = '`uploaded_on`>0';
            $where[] = '`is_hidden`=0';

            $where = $where ? implode(' AND ', $where) : '';

            $this->getStreamingDb();

            $page = intval($request->get('page', 0));
            $page = $page < 1 ? 1 : $page;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 20;

            $offset = ($page - 1) * $limit;

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $result = $streamingBroadcastHighlightModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`remote_path`,`uploaded_on`,`total_views`', $where, '`id` DESC', $offset, $limit);

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $row = $streamingChannelModel->getRow($channel, array('owner_name'));

            foreach ($result['data'] as $key => $val) {
                $result['data'][$key]['owner_name'] = $row['owner_name'];
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function list_hotAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $where = $channels = array();

        $where[] = '`uploaded_on`>0';
        $where[] = '`is_hidden`=0';

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $result = $streamingBroadcastHighlightModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`remote_path`,`uploaded_on`,`total_views`', $where, '`total_views` DESC', $offset, $limit);

        foreach ($result['data'] as $row) {
            $channels[] = $row['channel'];
        }

        if ($channels) {
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            foreach ($streamingChannelModel->getRows($channels, array('owner_name')) as $row) {
                $names[$row['id']] = $row['owner_name'];
            }

            foreach ($result['data'] as $key => $val) {
                $result['data'][$key]['owner_name'] = $names[$val['channel']];
            }
        }

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function list_newAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $where = $channels = $names = array();

        $where[] = '`uploaded_on`>0';
        $where[] = '`is_hidden`=0';

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $result = $streamingBroadcastHighlightModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`remote_path`,`uploaded_on`,`total_views`', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $row) {
            $channels[] = $row['channel'];
        }

        if ($channels) {
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            foreach ($streamingChannelModel->getRows($channels, array('owner_name')) as $row) {
                $names[$row['id']] = $row['owner_name'];
            }

            foreach ($result['data'] as $key => $val) {
                $result['data'][$key]['owner_name'] = $names[$val['channel']];
            }
        }

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function list_hot_todayAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = $channels = $names = array();

        $page = intval($request->get('page', 0));
        $page = $page ?: 1;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = $limit * ($page - 1);

        $this->getRedisStreaming();
        $this->getStreamingDb();

        $redisStreamingBroadcastHighlightRankingDailyModel = new Redis_Streaming_Broadcast_Highlight_Ranking_DailyModel($this->redisStreaming);
        if ($ranking = $redisStreamingBroadcastHighlightRankingDailyModel->range($offset, $offset + $limit - 1)) {
            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $data = $streamingBroadcastHighlightModel->getRows(array_keys($ranking), array(
                'id',
                'channel',
                'length',
                'title',
                'preview_path',
                'remote_path',
                'uploaded_on',
                'total_views',
            ));

            foreach ($data as $row) {
                $channels[] = $row['channel'];
            }

            if ($channels) {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                foreach ($streamingChannelModel->getRows($channels, array('owner_name')) as $row) {
                    $names[$row['id']] = $row['owner_name'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['owner_name'] = $names[$val['channel']];
                    $data[$key]['ranking_views'] = $ranking[$val['id']];
                }
            }
        }

        $result['data'] = $data;
        $result['page'] = $page;
        $result['total_found'] = $redisStreamingBroadcastHighlightRankingDailyModel->len();
        $result['page_count'] = ceil($result['total_found'] / $limit);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function list_hot_weekAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = $channels = $names = array();

        $page = intval($request->get('page', 0));
        $page = $page ?: 1;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = $limit * ($page - 1);

        $this->getRedisStreaming();
        $this->getStreamingDb();

        $redisStreamingBroadcastHighlightRankingWeeklyModel = new Redis_Streaming_Broadcast_Highlight_Ranking_WeeklyModel($this->redisStreaming);
        if ($ranking = $redisStreamingBroadcastHighlightRankingWeeklyModel->range($offset, $offset + $limit - 1)) {
            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $data = $streamingBroadcastHighlightModel->getRows(array_keys($ranking), array(
                'id',
                'channel',
                'length',
                'title',
                'preview_path',
                'remote_path',
                'uploaded_on',
                'total_views',
            ));

            foreach ($data as $row) {
                $channels[] = $row['channel'];
            }

            if ($channels) {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                foreach ($streamingChannelModel->getRows($channels, array('owner_name')) as $row) {
                    $names[$row['id']] = $row['owner_name'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['owner_name'] = $names[$val['channel']];
                    $data[$key]['ranking_views'] = $ranking[$val['id']];
                }
            }
        }

        $result['data'] = $data;
        $result['page'] = $page;
        $result['total_found'] = $redisStreamingBroadcastHighlightRankingWeeklyModel->len();
        $result['page_count'] = ceil($result['total_found'] / $limit);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function list_hot_monthAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = $channels = $names = array();

        $page = intval($request->get('page', 0));
        $page = $page ?: 1;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = $limit * ($page - 1);

        $this->getRedisStreaming();
        $this->getStreamingDb();

        $redisStreamingBroadcastHighlightRankingMonthlyModel = new Redis_Streaming_Broadcast_Highlight_Ranking_MonthlyModel($this->redisStreaming);
        if ($ranking = $redisStreamingBroadcastHighlightRankingMonthlyModel->range($offset, $offset + $limit - 1)) {
            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $data = $streamingBroadcastHighlightModel->getRows(array_keys($ranking), array(
                'id',
                'channel',
                'length',
                'title',
                'preview_path',
                'remote_path',
                'uploaded_on',
                'total_views',
            ));

            foreach ($data as $row) {
                $channels[] = $row['channel'];
            }

            if ($channels) {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                foreach ($streamingChannelModel->getRows($channels, array('owner_name')) as $row) {
                    $names[$row['id']] = $row['owner_name'];
                }

                foreach ($data as $key => $val) {
                    $data[$key]['owner_name'] = $names[$val['channel']];
                    $data[$key]['ranking_views'] = $ranking[$val['id']];
                }
            }
        }

        $result['data'] = $data;
        $result['page'] = $page;
        $result['total_found'] = $redisStreamingBroadcastHighlightRankingMonthlyModel->len();
        $result['page_count'] = ceil($result['total_found'] / $limit);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}