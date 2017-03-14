<?php
use Aws\S3\S3Client;

class RequestController extends ApiController
{
    protected $authActions = array(
        'show_image',
//        'get_show_image_status',
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

    public function show_imageAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();

            try {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                // Enable request audit
                $streamingShowImageRequestModel = new MySQL_Streaming_ShowImageRequestModel($this->streamingDb);
//                $reqInfo = $streamingShowImageRequestModel->getLastReq($userid);
//
//                if ($reqInfo && ($reqInfo['req_status'] == MySQL_Streaming_ShowImageRequestModel::REQ_STATUS_PENDING)) {
//                    $result['code'] = 409;
//                    $result['error'][] = array(
//                        'message' => 'A request is pending',
//                    );
//                } else if (!$_FILES || !isset($_FILES['small_show_image_file'])) {
                if (!$_FILES || !isset($_FILES['small_show_image_file'])) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'small_show_image_file',
                        'message' => 'Small show image file required',
                    );
                } else if (!$_FILES || !isset($_FILES['large_show_image_file'])) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'large_show_image_file',
                        'message' => 'Large show image file required',
                    );
                } else {
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $data = array(
                        'channel' => $userid,
                        'created_on' => $request->getServer('REQUEST_TIME'),
                    );

                    $src = null;
                    $timeFlag = date('YmdHis', $request->getServer('REQUEST_TIME'));

                    // Save small photo
                    $fSmallInfo = $_FILES['small_show_image_file'];
                    $fSmallExt = strtolower(pathinfo($fSmallInfo['name'], PATHINFO_EXTENSION));
                    $fSmallName = sprintf('show/%s-small_%s.png', $userid, $timeFlag);
                    $fSmallNameCopy = sprintf('show/%s-small.png', $userid);

                    if (($fSmallType = exif_imagetype($fSmallInfo['tmp_name'])) !== false) {
                        if ($fSmallType == IMAGETYPE_JPEG) {
                            $src = imagecreatefromjpeg($fSmallInfo['tmp_name']);
                        } else if ($fSmallType == IMAGETYPE_GIF) {
                            $src = imagecreatefromgif($fSmallInfo['tmp_name']);
                        } else {
                            $src = null;
                        }

                        $src && imagepng($src, $fSmallInfo['tmp_name']);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fSmallName,
                            'SourceFile' => $fSmallInfo['tmp_name'],
                            'ContentType' => 'image/png', //$fSmallInfo['type'],
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fSmallName,
                        ));

//                        $return = $this->s3->copyObject(array(
//                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
//                            'CopySource'    => sprintf('%s/%s', $config['aws']['s3']['bucket']['streaming'], $fSmallName),
//                            'Key'           => $fSmallNameCopy,
//                            'ContentType'   => 'image/png',
//                            'ACL'           => 'public-read',
//                        ));

                        $data['small_show_image'] = $fSmallName; //$fSmallNameCopy;
                    }

                    // Save large photo
                    $fLargeInfo = $_FILES['large_show_image_file'];
                    $fLargeExt = strtolower(pathinfo($fLargeInfo['name'], PATHINFO_EXTENSION));
                    $fLargeName = sprintf('show/%s-large_%s.png', $userid, $timeFlag);
                    $fLargeNameCopy = sprintf('show/%s-large.png', $userid);

                    if (($fLargeType = exif_imagetype($fLargeInfo['tmp_name'])) !== false) {
                        if ($fLargeType == IMAGETYPE_JPEG) {
                            $src = imagecreatefromjpeg($fLargeType['tmp_name']);
                        } else if ($fLargeType == IMAGETYPE_GIF) {
                            $src = imagecreatefromgif($fLargeType['tmp_name']);
                        } else {
                            $src = null;
                        }

                        $src && imagepng($src, $fLargeType['tmp_name']);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fLargeName,
                            'SourceFile' => $fLargeInfo['tmp_name'],
                            'ContentType' => 'image/png', //$fLargeInfo['type'],
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fLargeName,
                        ));

//                        $return = $this->s3->copyObject(array(
//                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
//                            'CopySource'    => sprintf('%s/%s', $config['aws']['s3']['bucket']['streaming'], $fLargeName),
//                            'Key'           => $fLargeNameCopy,
//                            'ContentType'   => 'image/png',
//                            'ACL'           => 'public-read',
//                        ));

                        $data['large_show_image'] = $fLargeName; // $fLargeNameCopy;
                    }

                    if ($data) {
//                        $streamingChannelModel->update($userid, $data);
                        $streamingShowImageRequestModel->insert($data);
                    }

                    $result['code'] = 200;
//                    $result['data'] = $data;
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

//    public function get_show_image_statusAction()
//    {
//        $request = $this->getRequest();
//
//        $result = array(
//            'code'  => 500,
//        );
//
//        $userdata = Yaf_Registry::get('user');
//        $userid = $userdata['id'];
//
//        $this->getStreamingDb();
//
//        $streamingShowImageRequestModel = new MySQL_Streaming_ShowImageRequestModel($this->streamingDb);
//        if ($reqInfo = $streamingShowImageRequestModel->getLastReq($userid, array('req_status', 'created_on', 'processed_on'))) {
//            $result['data'] = $reqInfo;
//            $result['code'] = 200;
//        } else {
//            $result['code'] = 404;
//        }
//
//        $this->callback($result);
//
//        return false;
//    }
}