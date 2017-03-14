<?php
use Aws\S3\S3Client;

class ApplicationController extends ApiController
{
    protected $authActions = array(
        'apply',
        'modify',
        'check_status',
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

    public function applyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $this->getStreamingDb();
        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);

        if ($request->isPost() && ($season = $request->get('season')) && $leagueSeasonModel->validate($season)) {
            $data = array();

            try {
                $appInfo = $leagueApplicationModel->getLastApp($season, $userid);

                if ($appInfo && ($appInfo['app_status'] != MySQL_League_ApplicationModel::APP_STATUS_DENIED)) {
                    $result['code'] = 409;
                } else if (!($title = $request->get('title'))) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'title',
                        'message' => 'Title required',
                    );
                } else if (!($leaderName = $request->get('leader_name'))) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'leader_name',
                        'message' => 'Leader name required',
                    );
                } else if (!($leaderPhone = $request->get('leader_phone'))) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'leader_phone',
                        'message' => 'Leader phone required',
                    );
                } else if (!($leaderEmail = $request->get('leader_email'))) {
                    $result['code'] = 400;
                    $result['error'][] = array(
                        'element' => 'leader_email',
                        'message' => 'Leader email required',
                    );
                } else {
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $data = array(
                        'season' => $season,
                        'title' => $title,
                        'leader_name' => $leaderName,
                        'leader_phone' => $leaderPhone,
                        'leader_email' => $leaderEmail,
                        'teams' => json_encode($request->get('teams')),
                        'video' => $request->get('video', ''),
                        'description' => $request->get('description', ''),
                        'created_on' => $request->getServer('REQUEST_TIME'),
                        'created_by' => $userid,
                    );

                    $appID = $leagueApplicationModel->insert($data);

                    // Save logo
                    if ($_FILES && isset($_FILES['logo_file']) && (exif_imagetype($_FILES['logo_file']['tmp_name']) !== false)) {
                        $fLogoInfo = $_FILES['logo_file'];
                        $fLogoExt = strtolower(pathinfo($fLogoInfo['name'], PATHINFO_EXTENSION));
                        $fLogoName = sprintf('league/team/%s-%s.%s', $appID, uniqid(), $fLogoExt);

                        $return = $this->s3->putObject(array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fLogoName,
                            'SourceFile' => $fLogoInfo['tmp_name'],
                            'ContentType' => $fLogoInfo['type'],
                            'ACL' => 'public-read',
                        ));

                        $this->s3->waitUntil('ObjectExists', array(
                            'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                            'Key' => $fLogoName,
                        ));

                        $leagueApplicationModel->update($appID, array(
                            'logo' => $fLogoName,
                        ));
                    }

                    $result['code'] = 200;
//                    $result['data'] = $appID;
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

    public function modifyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $this->getStreamingDb();
        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);

        if ($request->isPost() && ($season = $request->get('season')) && $leagueSeasonModel->validate($season)) {
            try {
                $appInfo = $leagueApplicationModel->getLastApp($season, $userid);

                if ($appInfo && ($appInfo['app_status'] != MySQL_League_ApplicationModel::APP_STATUS_DENIED)) {
                    if (!($title = $request->get('title'))) {
                        $result['code'] = 400;
                        $result['error'][] = array(
                            'element' => 'title',
                            'message' => 'Title required',
                        );
                    } else if (!($leaderPhone = $request->get('leader_phone'))) {
                        $result['code'] = 400;
                        $result['error'][] = array(
                            'element' => 'leader_phone',
                            'message' => 'Leader phone required',
                        );
                    } else if (!($leaderEmail = $request->get('leader_email'))) {
                        $result['code'] = 400;
                        $result['error'][] = array(
                            'element' => 'leader_email',
                            'message' => 'Leader email required',
                        );
                    } else {
                        $this->getS3();
                        $config = Yaf_Registry::get('config')->toArray();
                        $appID = $appInfo['id'];

                        $data = array(
                            'title' => $title,
                            'leader_phone' => $leaderPhone,
                            'leader_email' => $leaderEmail,
                            'teams' => json_encode($request->get('teams')),
                            'video' => $request->get('video', ''),
                            'description' => $request->get('description', ''),
                            'app_status' => MySQL_League_ApplicationModel::APP_STATUS_PENDING,
                        );

                        $leagueApplicationModel->update($appID, $data);

                        // Save logo
                        if ($_FILES && isset($_FILES['logo_file']) && (exif_imagetype($_FILES['logo_file']['tmp_name']) !== false)) {
                            $fLogoInfo = $_FILES['logo_file'];
                            $fLogoExt = strtolower(pathinfo($fLogoInfo['name'], PATHINFO_EXTENSION));
                            $fLogoName = sprintf('league/team/%s-%s.%s', $appID, uniqid(), $fLogoExt);

                            $return = $this->s3->putObject(array(
                                'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                                'Key' => $fLogoName,
                                'SourceFile' => $fLogoInfo['tmp_name'],
                                'ContentType' => $fLogoInfo['type'],
                                'ACL' => 'public-read',
                            ));

                            $this->s3->waitUntil('ObjectExists', array(
                                'Bucket' => $config['aws']['s3']['bucket']['streaming'],
                                'Key' => $fLogoName,
                            ));

                            $leagueApplicationModel->update($appID, array(
                                'logo' => $fLogoName,
                            ));
                        }

                        $result['code'] = 200;
                    }
                } else {
                    $result['code'] = 404;
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

    public function check_statusAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $userdata = Yaf_Registry::get('user');
        $userid = $userdata['id'];

        $this->getStreamingDb();

        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
        if ($season = $request->get('season')) {
            if ($appInfo = $leagueApplicationModel->getLastApp($season, $userid, $leagueApplicationModel->getDefaultFields())) {
                $appInfo['teams'] = json_decode($appInfo['teams'], true);
                $result['data'] = $appInfo;
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
}