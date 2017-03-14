<?php
use Aws\S3\S3Client;

class StreamingrequestController extends AdminController
{
    protected $authActions = array(
        'list' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'approve' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'deny' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'view' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
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
//                'curl.options' => array(
//                    'CURLOPT_PROXY' => 'socks5://192.168.1.134:5566',
//                ),
//                'request.options' => array(
//                    'proxy' => 'socks5://192.168.1.134:5566',
//                ),
            ));
        }

        return $this->s3;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $channels = $names = array();

        $this->getStreamingDb();
        $this->getPassportDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $streamingShowImageRequestModel = new MySQL_Streaming_ShowImageRequestModel($this->streamingDb);

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
        $where = $where ? implode(' AND ', $where) : '';

        $result = $streamingShowImageRequestModel->search('*', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $key => $row) {
            $channels[] = $row['channel'];
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingrequest/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['states'] = MySQL_Streaming_ShowImageRequestModel::getStatusMap();

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        foreach ($userAccountModel->getRows($channels, array('id', 'name')) as $row) {
            $names[$row['id']] = $row['name'];
        }

        foreach ($result['data'] as $key => $val) {
            $result['data'][$key]['user_name'] = $names[$val['channel']];
        }

        $this->getView()->assign($result);
    }

    public function approveAction()
    {
        $result = array(
            'code' => 500,
        );
        $request = $this->getRequest();

        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $streamingShowImageRequestModel = new MySQL_Streaming_ShowImageRequestModel($this->streamingDb);

            if ($reqInfo = $streamingShowImageRequestModel->getRow($id)) {
                $data = array();

                try {
                    $this->streamingDb->beginTransaction();

                    $data['small_show_image'] = preg_replace('|_\w+|', '', $reqInfo['small_show_image']);
                    $data['large_show_image'] = preg_replace('|_\w+|', '', $reqInfo['large_show_image']);

                    $return = $this->s3->copyObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'CopySource'    => sprintf('%s/%s', $config['aws']['s3']['bucket']['streaming'], $reqInfo['small_show_image']),
                        'Key'           => $data['small_show_image'],
                        'ContentType'   => 'image/png',
                        'ACL'           => 'public-read',
                    ));

                    $return = $this->s3->copyObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'CopySource'    => sprintf('%s/%s', $config['aws']['s3']['bucket']['streaming'], $reqInfo['large_show_image']),
                        'Key'           => $data['large_show_image'],
                        'ContentType'   => 'image/png',
                        'ACL'           => 'public-read',
                    ));

                    $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                    $streamingChannelModel->update($reqInfo['channel'], $data);

                    $affectedCount = $streamingShowImageRequestModel->approve($id, $userid);

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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingrequest/list');

        return false;
    }

    public function denyAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $streamingShowImageRequestModel = new MySQL_Streaming_ShowImageRequestModel($this->streamingDb);
            $affectedCount = $streamingShowImageRequestModel->deny($id, $userid);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingrequest/list');

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

        $streamingShowImageRequestModel = new MySQL_Streaming_ShowImageRequestModel($this->streamingDb);

        if (($id = $request->get('id')) && ($reqInfo = $streamingShowImageRequestModel->getRow($id, $streamingShowImageRequestModel->getFields()))) {
            if ($reqInfo['small_show_image']) {
                $reqInfo['small_show_image_url'] = $this->s3->getObjectUrl(
                    $config['aws']['s3']['bucket']['streaming'],
                    $reqInfo['small_show_image'],
                    '+30 minutes'
                );
            }

            if ($reqInfo['large_show_image']) {
                $reqInfo['large_show_image_url'] = $this->s3->getObjectUrl(
                    $config['aws']['s3']['bucket']['streaming'],
                    $reqInfo['large_show_image'],
                    '+30 minutes'
                );
            }

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $accountInfo = $userAccountModel->getRow($reqInfo['channel'], array('id', 'name'));
            $reqInfo['user_name'] = $accountInfo['name'];
        }

        $this->_view->assign(array(
            'data'  => $reqInfo,
            'states' => MySQL_Streaming_ShowImageRequestModel::getStatusMap(),
        ));
    }
}