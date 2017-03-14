<?php
use Aws\S3\S3Client;

class ApplicationController extends ApiController
{
    protected $authActions = array(
        'signed',
        'exclusive',
        'get_signed_status',
        'get_exclusive_status',
    );

    protected $streamingDb;

    protected $redisStreaming;

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

    protected function getS3()
    {
        if (empty($this->s3)) {
            $config = Yaf_Registry::get('config')->toArray();

            $this->s3 = S3Client::factory(array(
                'key'       => $config['aws']['s3']['key'],
                'secret'    => $config['aws']['s3']['secret'],
                'region'    => $config['aws']['s3']['region'],
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

    public function signedAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        if ($request->isPost()) {
            $data = array();
            $this->getStreamingDb();

            try {
                $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);
                $appInfo = $streamingApplicationModel->getLastApp($userid, MySQL_Streaming_ApplicationModel::APP_TYPE_SIGNED);

                if ($appInfo && ($appInfo['app_status'] != MySQL_Streaming_ApplicationModel::APP_STATUS_DENIED)) {
                    $result['code'] = 409;
                } else if (!$_FILES || !isset($_FILES['id_photo_front_file']) || (exif_imagetype($_FILES['id_photo_front_file']['tmp_name']) === false)) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'id_photo_front_file',
                        'message' => 'ID photo front file required',
                    );
                } else if (!$_FILES || !isset($_FILES['id_photo_back_file']) || (exif_imagetype($_FILES['id_photo_back_file']['tmp_name']) === false)) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'id_photo_back_file',
                        'message' => 'ID photo back file required',
                    );
                } else if (!($phone = $request->get('phone'))) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'phone',
                        'message' => 'Phone required',
                    );
                } else if (!($name = $request->get('name'))) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'name',
                        'message' => 'Name required',
                    );
                } else {
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    // Save front photo
                    $fFrontInfo = $_FILES['id_photo_front_file'];
                    $fFrontExt = strtolower(pathinfo($fFrontInfo['name'], PATHINFO_EXTENSION));
                    $fFrontName = sprintf('secure/application/%s-%s.%s', $userid, uniqid(), $fFrontExt);

                    $return = $this->s3->putObject(array(
                        'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                        'Key' => $fFrontName,
                        'SourceFile' => $fFrontInfo['tmp_name'],
                        'ContentType' => $fFrontInfo['type'],
                        'ACL' => 'bucket-owner-read',
                    ));

                    $this->s3->waitUntil('ObjectExists', array(
                        'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                        'Key' => $fFrontName,
                    ));

                    // Save back photo
                    $fBackInfo = $_FILES['id_photo_back_file'];
                    $fBackExt = strtolower(pathinfo($fBackInfo['name'], PATHINFO_EXTENSION));
                    $fBackName = sprintf('secure/application/%s-%s.%s', $userid, uniqid(), $fBackExt);

                    $return = $this->s3->putObject(array(
                        'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                        'Key' => $fBackName,
                        'SourceFile' => $fBackInfo['tmp_name'],
                        'ContentType' => $fBackInfo['type'],
                        'ACL' => 'bucket-owner-read',
                    ));

                    $this->s3->waitUntil('ObjectExists', array(
                        'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                        'Key' => $fBackName,
                    ));

                    $data = array(
                        'id_photo_front' => $fFrontName,
                        'id_photo_back' => $fBackName,
                        'name' => $name,
                        'phone' => $phone,
                        'skype' => $request->get('skype', ''),
                        'twitch' => $request->get('twitch', ''),
                        'facebook' => $request->get('facebook', ''),
                        'channel' => $userid,
                        'app_type' => MySQL_Streaming_ApplicationModel::APP_TYPE_SIGNED,
                        'created_on' => $request->getServer('REQUEST_TIME'),
                    );

                    $streamingApplicationModel->insert($data);

                    $result['code'] = 200;
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

    public function exclusiveAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        if ($request->get('agree')) {
            $data = array();
            $this->getStreamingDb();

            $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);
            $appInfo = $streamingApplicationModel->getLastApp($userid, MySQL_Streaming_ApplicationModel::APP_TYPE_EXCLUSIVE);

            if ($appInfo && ($appInfo['app_status'] != MySQL_Streaming_ApplicationModel::APP_STATUS_DENIED)) {
                $result['code'] = 409;
            } else {
                $data = array(
                    'channel' => $userid,
                    'app_type' => MySQL_Streaming_ApplicationModel::APP_TYPE_EXCLUSIVE,
                    'created_on' => $request->getServer('REQUEST_TIME'),
                );

                $streamingApplicationModel->insert($data);

                $result['code'] = 200;
            }
        } else {
            $result['code'] = 412;
        }

        $this->callback($result);

        return false;
    }

    public function get_exclusive_statusAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $this->getStreamingDb();

        $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);
        if ($appInfo = $streamingApplicationModel->getLastApp($userid, MySQL_Streaming_ApplicationModel::APP_TYPE_EXCLUSIVE, array('app_status', 'created_on', 'processed_on'))) {
            $result['data'] = $appInfo;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function get_signed_statusAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $this->getStreamingDb();

        $streamingApplicationModel = new MySQL_Streaming_ApplicationModel($this->streamingDb);
        if ($appInfo = $streamingApplicationModel->getLastApp($userid, MySQL_Streaming_ApplicationModel::APP_TYPE_SIGNED, array('app_status', 'created_on', 'processed_on'))) {
            $result['data'] = $appInfo;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}