<?php
use Aws\S3\S3Client;

class BroadcastController extends ApiController
{
    protected $authActions = array(
        'update',
        'delete',
        'highlight',
        'my',
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

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

            if (($broadcastInfo = $streamingBroadcastModel->getRow($id, array('id', 'channel', 'is_deleted'))) && ($broadcastInfo['channel'] == $userid) && !$broadcastInfo['is_deleted']) {
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if ($memo = $request->get('memo')) {
                    $data['memo'] = $memo;
                }

                if ($data) {
                    $streamingBroadcastModel->update($id, $data);
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

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

            if (($broadcastInfo = $streamingBroadcastModel->getRow($id)) && ($broadcastInfo['channel'] == $userid)) {
//                $this->s3->deleteObjects(array(
//                    'Bucket'    => $config['aws']['s3']['bucket']['streaming'],
//                    'Objects'   => array(
//                        array(
//                            'Key'   => $broadcastInfo['remote_path'],
//                        ),
//                        array(
//                            'Key'   => $broadcastInfo['preview_path'],
//                        ),
//                    ),
//                ));
//
//                $streamingBroadcastModel->delete(array($id));
                $streamingBroadcastModel->update($id, array(
                    'is_deleted' => 1,
                ));

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

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

            $data = $streamingBroadcastModel->getRow($id, array(
                'id',
                'channel',
                'title',
                'memo',
                'length',
                'uploaded_on',
                'remote_path',
                'preview_path',
                'total_views',
                'is_deleted',
            ));

            if ($data && !$data['is_deleted'] && ($row = $streamingChannelModel->getRow($data['channel'], array('owner_name')))) {
                $data['owner_name'] = $row['owner_name'];

                $streamingBroadcastModel->view($id);

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

    public function listAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($channel = $request->get('channel')) {
            $where = array();

            $where[] = '`channel`=' . (int) $channel;
            $where[] = '`uploaded_on`>=' . strtotime('-30 day');
            $where[] = '`is_deleted`=0';

            $where = $where ? implode(' AND ', $where) : '';

            $this->getStreamingDb();

            $page = intval($request->get('page', 0));
            $page = $page < 1 ? 1 : $page;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 20;

            $offset = ($page - 1) * $limit;

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
            $result = $streamingBroadcastModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`uploaded_on`,`total_views`', $where, '`id` DESC', $offset, $limit);

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

    public function highlightAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost()) {
            $broadcastID = $request->get('id');
            $start = $request->get('start', 0);
            $stop = $request->get('stop', 0);
            $title = $request->get('title', '');
            $memo = $request->get('memo', '');
            $length = $stop - $start;

            $data = array();
            $this->getStreamingDb();
            $config = Yaf_Registry::get('config')->toArray();
            $maxLength = $config['streaming']['highlight']['max-length'];

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

            if (($broadcastInfo = $streamingBroadcastModel->getRow($broadcastID, array('id', 'channel', 'length', 'is_deleted'))) && ($broadcastInfo['channel'] == $userid) && !$broadcastInfo['is_deleted']) {
                if (($start >= 0) && ($stop <= $broadcastInfo['length']) && ($start < $stop) && ($length <= $maxLength)) {
                    $data = array(
                        'channel'       => $userid,
                        'broadcast'     => $broadcastID,
                        'start'         => $start,
                        'stop'          => $stop,
                        'length'        => $length,
                        'title'         => $title,
                        'memo'          => $memo,
                        'submitted_on'  => time(),
                    );
                    $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);

                    $highlightID = $streamingBroadcastHighlightModel->insert($data);

                    $gearmanClient = Daemon::getGearmanClient();
                    $gearmanClient->doBackground('streaming-highlight', (string) $highlightID);

                    if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                        Misc::log(sprintf("gearman job (streaming-highlight) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                    }

                    $result['code'] = 200;
                } else {
                    $result['code'] = 400;
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
        $where[] = '`recording_on`>=' . strtotime('-30 day');
        $where[] = '`is_deleted`=0';

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
        $result = $streamingBroadcastModel->search('`id`,`channel`,`length`,`title`,`preview_path`,`uploaded_on`,`total_views`,`recording_on`,`ending_on`', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $key => $val) {
            $result['data'][$key]['owner_name'] = $currentUser['name'];
        }

        $result['code'] = 200;

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

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
            $result['data'] = $streamingBroadcastModel->getRows($ids, array(
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
}