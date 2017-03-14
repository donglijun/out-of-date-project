<?php
use Aws\S3\S3Client;

class StreamingapplicationController extends AdminController
{
    protected $authActions = array(
        'list' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'approve' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'deny' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'view' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'memo' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
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
                'key' => $config['aws']['s3']['key'],
                'secret' => $config['aws']['s3']['secret'],
                'region' => $config['aws']['s3']['region'],
            ));
        }

        return $this->s3;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $channels = $classes = $names = $channelClasses = array();

        $this->getStreamingDb();
        $this->getPassportDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);

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
        if ($appType = $request->get('app_type')) {
            $where[] = "`app_type`=" . (int) $appType;
            $filter['app_type'] = $appType;
        }
//        if ($appStatus = $request->get('app_status')) {
//            $where[] = "`app_status`=" . (int) $appStatus;
//            $filter['app_status'] = $appStatus;
//        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $streamingApplicationModel->search('*', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $key => $row) {
            if ($row['id_photo_front']) {
                $result['data'][$key]['id_photo_front_url'] = $this->s3->getObjectUrl(
                    $config['aws']['s3']['bucket']['streaming'],
                    $row['id_photo_front'],
                    '+30 minutes'
                );
            }
            if ($row['id_photo_back']) {
                $result['data'][$key]['id_photo_back_url'] = $this->s3->getObjectUrl(
                    $config['aws']['s3']['bucket']['streaming'],
                    $row['id_photo_back'],
                    '+30 minutes'
                );
            }
            $channels[] = $row['channel'];
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingapplication/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['types'] = MySQL_Streaming_ApplicationModel::getTypeMap();
        $result['states'] = MySQL_Streaming_ApplicationModel::getStatusMap();

        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);
        $result['classes'] = $streamingChannelClassModel->getAll(array('id', 'title'));
        foreach ($result['classes'] as $row) {
            $classes[$row['id']] = $row['title'];
        }

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        foreach ($streamingChannelModel->getRows($channels, array('id', 'class')) as $row) {
            $row['class_title'] = $classes[$row['class']];
            $channelClasses[$row['id']] = $row;
        }
        $result['channelClasses'] = $channelClasses;

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        foreach ($userAccountModel->getRows($channels, array('id', 'name')) as $row) {
            $names[$row['id']] = $row['name'];
        }

        foreach ($result['data'] as $key => $val) {
            $result['data'][$key]['user_name'] = $names[$val['channel']];
            $result['data'][$key]['class_title'] = $channelClasses[$val['channel']]['class_title'];
        }

        $this->getView()->assign($result);
    }

    public function approveAction()
    {
        $result = array(
            'code' => 500,
        );
        $request = $this->getRequest();

        if (($id = $request->get('id'))/* && ($class = $request->get('class'))*/) {
            $this->getStreamingDb();
            $config = Yaf_Registry::get('config')->toArray();

            $userid = $this->session->admin['user'];

            $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);
            $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);

            if (($appInfo = $streamingApplicationModel->getRow($id))/* && ($classInfo = $streamingChannelClassModel->getRow($class))*/) {
                $data = array();

                try {
                    $this->streamingDb->beginTransaction();

                    if ($appInfo['app_type'] == MySQL_Streaming_ApplicationModel::APP_TYPE_SIGNED) {
                        $data['is_signed'] = 1;
                        // Set default signed class "Level X"
                        $data['class'] = isset($config['streaming']['application']['default-signed-class']) ? $config['streaming']['application']['default-signed-class'] : 2;
                    } else if ($appInfo['app_type'] == MySQL_Streaming_ApplicationModel::APP_TYPE_EXCLUSIVE) {
                        $data['is_exclusive'] = 1;
                    }

                    if (($affectedCount = $streamingApplicationModel->approve($id, $userid)) && $data) {
                        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                        $streamingChannelModel->update($appInfo['channel'], $data);
                    }

                    $this->streamingDb->commit();

                    $result['code'] = 200;
                    $result['message'] = 'ok';
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    Misc::log($e->getMessage(), Zend_Log::ERR);
                }
            } else {
                $result['code'] = 404;
                $result['message'] = 'Invalid parameter';
            }
        } else {
            $result['code'] = 404;
            $result['message'] = 'Missing parameter';
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingapplication/list');

        return false;
    }

    public function denyAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);
            $affectedCount = $streamingApplicationModel->deny($id, $userid);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingapplication/list');

        return false;
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getPassportDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);

        if (($id = $request->get('id')) && ($appInfo = $streamingApplicationModel->getRow($id, $streamingApplicationModel->getFields()))) {
            if ($appInfo['id_photo_front']) {
                $appInfo['id_photo_front_url'] = $this->s3->getObjectUrl(
                    $config['aws']['s3']['bucket']['streaming'],
                    $appInfo['id_photo_front'],
                    '+30 minutes'
                );
            }

            if ($appInfo['id_photo_back']) {
                $appInfo['id_photo_back_url'] = $this->s3->getObjectUrl(
                    $config['aws']['s3']['bucket']['streaming'],
                    $appInfo['id_photo_back'],
                    '+30 minutes'
                );
            }

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $accountInfo = $userAccountModel->getRow($appInfo['channel'], array('id', 'name'));
            $appInfo['user_name'] = $accountInfo['name'];
        }

        $this->_view->assign(array(
            'data'  => $appInfo,
            'types' => MySQL_Streaming_ApplicationModel::getTypeMap(),
            'states' => MySQL_Streaming_ApplicationModel::getStatusMap(),
        ));
    }

    public function memoAction()
    {
        $result = array();
        $request = $this->getRequest();

        if (($id = $request->get('id')) && ($memo = $request->get('memo'))) {
            $this->getStreamingDb();
            $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);

            $streamingApplicationModel->update($id, array(
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingapplication/list');

        return false;
    }
}