<?php
use Aws\S3\S3Client;

class PanelController extends ApiController
{
    protected $authActions = array(
        'update',
        'upload_image',
        'resort',
        'remove',
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
            ));
        }

        return $this->s3;
    }

    public function upload_imageAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $x = $request->get('x', 0);
        $y = $request->get('y', 0);
        $w = $request->get('w', 0);
        $h = $request->get('h', 0);

        if ($request->isPost() && $w && $h) {
            try {
                $userdata = Yaf_Registry::get('user');
                $userid = $userdata['id'];

                if ($_FILES && isset($_FILES['panel_image_file']) && $_FILES['panel_image_file']['tmp_name']) {
                    $src = $dst = null;
                    $finfo = $_FILES['panel_image_file'];

                    $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                    $fname = sprintf('panels/%s-%s.png', $userid, uniqid());
                    $ftype = exif_imagetype($finfo['tmp_name']);

                    if ($ftype == IMAGETYPE_PNG) {
                        $src = imagecreatefrompng($finfo['tmp_name']);
                    } else if ($ftype == IMAGETYPE_JPEG) {
                        $src = imagecreatefromjpeg($finfo['tmp_name']);
                    } else if ($ftype == IMAGETYPE_GIF) {
                        $src = imagecreatefromgif($finfo['tmp_name']);
                    }

                    if ($src) {
                        $s3 = $this->getS3();
                        $config = Yaf_Registry::get('config')->toArray();

                        $fixedWidth = $config['streaming']['panel']['image']['width'];
                        $fixedHeight = round($h * ($fixedWidth / $w));
    //                    $rect = array(
    //                        'x'      => $x,
    //                        'y'      => $y,
    //                        'width'  => $w,
    //                        'height' => $h,
    //                    );
    //                    $dst = imagecrop($src, $rect);
                        $dst = imagecreatetruecolor($fixedWidth, $fixedHeight);
                        imagecopyresampled($dst, $src, 0, 0, $x, $y, $fixedWidth, $fixedHeight, $w, $h);

                        imagepng($dst, $finfo['tmp_name']);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fname,
                            'SourceFile' => $finfo['tmp_name'],
                            'ContentType' => 'image/png',
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fname,
                        ));

                        $result['code'] = 200;
                        $result['data'] = $fname;
                    } else {
                        $result['error'][] = array(
                            'message' => 'Invalid image type',
                        );
                    }
                } else {
                    $result['error'][] = array(
                        'message' => 'No file uploaded',
                    );
                }
            } catch (Exception $e) {
                $result['error'][] = array(
                    'message' => 'Internal error',
                );

                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function updateAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $panel = $request->get('panel');
        $title = $request->get('title', '');
        $image = $request->get('image', '');
        $link = $request->get('link', '');
        $description = $request->get('description', '');

        if ($request->isPost()) {
            $this->getStreamingDb();

            $streamingPanelModel = new MySQL_Streaming_PanelModel($this->streamingDb);

            if ($panel) {
                if (($panelInfo = $streamingPanelModel->getRow($panel)) && ($panelInfo['channel'] == $userid)) {
                    $data = array(
                        'title'       => $title,
                        'image'       => $image,
                        'link'        => $link,
                        'description' => $description,
                    );

                    $streamingPanelModel->update($panel, $data);

                    $result['code'] = 200;
                } else {
                    $result['code'] = 403;
                }
            } else {
                $data = array(
                    'channel'     => $userid,
                    'title'       => $title,
                    'image'       => $image,
                    'link'        => $link,
                    'description' => $description,
                    'created_on'  => time(),
                );

                $result['data'] = $streamingPanelModel->insert($data);
                $result['code'] = 200;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function resortAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $panels = $request->get('panels');

        $this->getRedisStreaming();

        $redisStreamingPanelChannelModel = new Redis_Streaming_Panel_ChannelModel($this->redisStreaming);
        $redisStreamingPanelChannelModel->set($userid, $panels);

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function removeAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $panel = $request->get('panel');

        $this->getStreamingDb();
        $this->getRedisStreaming();

        $streamingPanelModel = new MySQL_Streaming_PanelModel($this->streamingDb);
        if (($panelInfo = $streamingPanelModel->getRow($panel)) && ($panelInfo['channel'] == $userid)) {
            $streamingPanelModel->delete(array($panel));

            $redisStreamingPanelChannelModel = new Redis_Streaming_Panel_ChannelModel($this->redisStreaming);
            $ids = $redisStreamingPanelChannelModel->get($userid);
            if ($ids = explode(',', $ids)) {
                $ids = array_diff($ids, array($panel));

                $redisStreamingPanelChannelModel->set($userid, implode(',', $ids));
            }

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
            $data = array();

            $this->getRedisStreaming();
            $this->getStreamingDb();

            $redisStreamingPanelChannelModel = new Redis_Streaming_Panel_ChannelModel($this->redisStreaming);
            $ids = $redisStreamingPanelChannelModel->get($channel);
            if ($ids = explode(',', $ids)) {
                $streamingPanelModel = new MySQL_Streaming_PanelModel($this->streamingDb);

                $data = $streamingPanelModel->getRows($ids);
            }

            $result['code'] = 200;
            $result['data'] = $data;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}