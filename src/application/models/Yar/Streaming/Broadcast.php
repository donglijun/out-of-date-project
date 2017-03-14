<?php
use Aws\S3\S3Client;

class Yar_Streaming_BroadcastModel
{
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

    protected function checkStreamingDb()
    {
        if (!$this->streamingDb) {
            $this->getStreamingDb();
        } else {
            try {
                $this->streamingDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('streaming-db');
                $this->streamingDb = null;

                $this->getStreamingDb();
            }
        }
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

    public function upload($broadcastID)
    {
        $result = false;
        $data = array();
        $config = Yaf_Registry::get('config')->toArray();

        $this->getStreamingDb();
        $this->getS3();

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

        if ($broadcastInfo = $streamingBroadcastModel->getRow($broadcastID)) {
            $localPath = sprintf('%s/b-%s-%s.flv', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel']);
            $fixedLocalPath = sprintf('%s/b-%s-%s-fixed.flv', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel']);

            if (file_exists($localPath) && filesize($localPath)) {
                Misc::log(sprintf('Upload recording %d(%d)', $broadcastID, $broadcastInfo['channel']), Zend_Log::WARN);

                // Try to kill running rtmpdump process
//                $rtmpdump = $config['streaming']['recording']['bin']['rtmpdump'];
//                $cmd = sprintf("ps -ef | grep -v 'grep' | grep '%s -r rtmp://media.nikksy.com/play/%d -v -q -o %s' | awk '{print $2}' | xargs kill -s 9", $rtmpdump, $broadcastInfo['channel'], $localPath);
//                $lastLine = system($cmd);

                // Fix flv
                $yamdi = $config['streaming']['recording']['bin']['yamdi'];
//                $fixedLocalPath = sprintf('%s/b-%s-%s-fixed.flv', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel']);
                $cmd = sprintf('%s -i %s -o %s', $yamdi, $localPath, $fixedLocalPath);
                $lastLine = system($cmd);

                // Delete raw
//                $cmd = sprintf('rm -f %s', $localPath);
//                $lastLine = system($cmd);
            }

            if (file_exists($fixedLocalPath)) {

//                $localPath = file_exists($fixedLocalPath) ? $fixedLocalPath : $localPath;
                $localPath = $fixedLocalPath;

                // Clear cache before query size
                clearstatcache(true, $localPath);
                $size = filesize($localPath);

                // Snapshot
                $ffmpeg = $config['streaming']['recording']['bin']['ffmpeg'];
                $w = $config['streaming']['recording']['snapshot']['width'];
                $h = $config['streaming']['recording']['snapshot']['height'];
                $previewPath = sprintf('%s/b-%s-%s-%dx%d.jpg', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel'], $w, $h);
                $cmd = sprintf('%s -an -i %s -vframes 1 -f image2 -s %dx%d %s -y >/dev/null 2>&1', $ffmpeg, $localPath, $w, $h, $previewPath);
                $lastLine = system($cmd);

                $y = date('Y', $broadcastInfo['recording_on']);
                $m = date('m', $broadcastInfo['recording_on']);
                $d = date('d', $broadcastInfo['recording_on']);
                $remotePath = sprintf('broadcast/flv/%s/%s/%s/b-%s-%s.flv', $y, $m, $d, $broadcastID, $broadcastInfo['channel']);
                $remotePreviewPath = sprintf('broadcast/preview/%s/%s/%s/b-%s-%s-%dx%d.jpg', $y, $m, $d, $broadcastID, $broadcastInfo['channel'], $w, $h);

                try {
                    if (!$this->s3->doesObjectExist($config['aws']['s3']['bucket']['streaming'], $remotePath)) {
                        $expires = time() + $config['streaming']['recording']['ttl'];
//                        $this->s3->putObject(array(
//                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
//                            'Key'           => $remotePath,
//                            'SourceFile'    => $localPath,
//                            'ACL'           => 'public-read',
//                        ));

                        Misc::log(sprintf('Uploading preview %s', $remotePreviewPath), Zend_Log::WARN);
                        if (file_exists($previewPath)) {
                            $return = $this->s3->putObject(array(
                                'Bucket'      => $config['aws']['s3']['bucket']['streaming'],
                                'Key'         => $remotePreviewPath,
                                'SourceFile'  => $previewPath,
                                'ACL'         => 'public-read',
                                'ContentType' => 'image/jpeg',
                                'Expires'     => $expires,
                            ));
                        } else {
                            $return = $this->s3->copyObject(array(
                                'Bucket'      => $config['aws']['s3']['bucket']['streaming'],
                                'Key'         => $remotePreviewPath,
                                'CopySource'  => sprintf('%s/previews/default.jpg', $config['aws']['s3']['bucket']['streaming']),
                                'ContentType' => 'image/jpeg',
                                'ACL'         => 'public-read',
                                'Expires'     => $expires,
                            ));
                        }

                        Misc::log(sprintf('Uploading broadcast %s', $remotePath), Zend_Log::WARN);
                        $return = $this->s3->upload(
                            $config['aws']['s3']['bucket']['streaming'],
                            $remotePath,
                            fopen($localPath, 'r+'),
                            'public-read',
                            array(
                                'params' => array(
                                    'Expires' => $expires,
                                ),
                            )
                        );

                        $data['uploaded_on'] = time();
                        $data['remote_path'] = $remotePath;
                        $data['preview_path'] = $remotePreviewPath;
                        $data['w'] = $w;
                        $data['h'] = $h;
                        $data['size'] = $size;

                        // Get duration
//                        $mediainfo = $config['streaming']['recording']['bin']['mediainfo'];
//                        $cmd = sprintf("%s --Inform=\"General;%%Duration%%\" %s", $mediainfo, $localPath);
//                        $lastLine = system($cmd);
//
////                        Misc::log(sprintf('Get duration result: %s (%s)', $lastLine, $cmd), Zend_Log::WARN);
//
//                        if ($lastLine && is_numeric($lastLine)) {
//                            $data['length'] = round($lastLine / 1000);
//                        }

                        $ffprobe = $config['streaming']['recording']['bin']['ffprobe'];
                        $cmd = sprintf("%s -i %s -show_format -v quiet | sed -n 's/duration=//p'", $ffprobe, $localPath);
                        $lastLine = system($cmd);

//                        Misc::log('Get duration result2: ' . $lastLine, Zend_Log::WARN);
                        if ($lastLine && is_numeric($lastLine) && ($lastLine = round($lastLine))) {
                            $data['length'] = $lastLine;
                        }

//                        Misc::log(sprintf('Update %d(%d): %s', $broadcastID, $broadcastInfo['channel'], json_encode($data)), Zend_Log::WARN);

                        $this->checkStreamingDb();
                        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

                        $streamingBroadcastModel->update($broadcastID, $data);

                        Misc::log(sprintf('Uploaded %d(%d)', $broadcastID, $broadcastInfo['channel']), Zend_Log::WARN);
                    }
                } catch (Exception $e) {
                    Misc::log($e->getMessage(), Zend_Log::ERR);
                }
            }
//            } else {
//                $streamingBroadcastModel->update($broadcastID, array(
//                    'length' => 0,
//                ));
//            }

            $result = true;
        }

        return $result;
    }

    public function upload_v2($broadcastID)
    {
        $result = false;
        $data = array();
        $config = Yaf_Registry::get('config')->toArray();
        $timestamp = time();

        Misc::log(sprintf('Enter upload version 2: %s', $broadcastID), Zend_Log::WARN);

        $this->getStreamingDb();
        $this->getS3();
        $this->getRedisStreaming();

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
        $broadcastInfo = $streamingBroadcastModel->getRow($broadcastID);

        if (!$broadcastInfo || $broadcastInfo['uploaded_on']) {
            return false;
        }

        $redisStreamingChannelLogModel = new Redis_Streaming_Channel_LogModel($this->redisStreaming);
        $redisStreamingChannelLogModel->log($broadcastInfo['channel'], $broadcastInfo['upstream_ip'], $broadcastInfo['session'], 'Client started uploading process');

        $yamdi = $config['streaming']['recording']['bin']['yamdi'];
        $ffprobe = $config['streaming']['recording']['bin']['ffprobe'];
        $ffmpeg = $config['streaming']['recording']['bin']['ffmpeg'];
        $w = $config['streaming']['recording']['snapshot']['width'];
        $h = $config['streaming']['recording']['snapshot']['height'];
        $bucket = $config['aws']['s3']['bucket']['streaming'];

        $localPath = sprintf('%s/b-%s-%s.flv', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel']);
        $fixedLocalPath = sprintf('%s/b-%s-%s-fixed.flv', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel']);

        clearstatcache(true, $localPath);

        if (!file_exists($fixedLocalPath) && file_exists($localPath) && (filemtime($localPath) < $timestamp - 30)) {
            // Check fix process
            $cmd = sprintf("ps -ef | grep -v 'grep' | grep '%s -i %s -o %s'", $yamdi, $localPath, $fixedLocalPath);
            if ($lastLine = system($cmd)) {
                return false;
            }

            $redisStreamingChannelLogModel->log($broadcastInfo['channel'], $broadcastInfo['upstream_ip'], $broadcastInfo['session'], 'Client started fixing');

            // Fix flv
            Misc::log(sprintf('Try to fix %s', $localPath), Zend_Log::WARN);

            $cmd = sprintf('%s -i %s -o %s', $yamdi, $localPath, $fixedLocalPath);
            $lastLine = system($cmd);
        }

        if (!file_exists($fixedLocalPath)) {
            return false;
        }

        $localPath = $fixedLocalPath;

        if (!$broadcastInfo['remote_path']) {
            // Clear cache before query size
            clearstatcache(true, $localPath);
            $data['size'] = filesize($localPath);

            // Get duration
            $cmd = sprintf("%s -i %s -show_format -v quiet | sed -n 's/duration=//p'", $ffprobe, $localPath);
            $lastLine = system($cmd);

            if ($lastLine && is_numeric($lastLine) && ($lastLine = round($lastLine))) {
                $data['length'] = $lastLine;
            }

            $y = date('Y', $broadcastInfo['recording_on']);
            $m = date('m', $broadcastInfo['recording_on']);
            $d = date('d', $broadcastInfo['recording_on']);
            $remotePath = sprintf('broadcast/flv/%s/%s/%s/b-%s-%s.flv', $y, $m, $d, $broadcastID, $broadcastInfo['channel']);
            $remotePreviewPath = sprintf('broadcast/preview/%s/%s/%s/b-%s-%s-%dx%d.jpg', $y, $m, $d, $broadcastID, $broadcastInfo['channel'], $w, $h);
            $remoteSourcePreviewPath = sprintf('broadcast/preview/%s/%s/%s/b-%s-%s-source.jpg', $y, $m, $d, $broadcastID, $broadcastInfo['channel']);

            $data['remote_path'] = $remotePath;
            $data['preview_path'] = $remotePreviewPath;
            $data['w'] = $w;
            $data['h'] = $h;

            $this->checkStreamingDb();
            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

            $streamingBroadcastModel->update($broadcastID, $data);
        } else {
            $remotePath = $broadcastInfo['remote_path'];
            $remotePreviewPath = $broadcastInfo['preview_path'];
        }

        try {
            if (!$this->s3->doesObjectExist($bucket, $remotePreviewPath)) {
                // Snapshot
                $previewPath = sprintf('%s/b-%s-%s-%dx%d.jpg', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel'], $w, $h);
                $cmd = sprintf('%s -an -i %s -vframes 1 -f image2 -s %dx%d %s -y >/dev/null 2>&1', $ffmpeg, $localPath, $w, $h, $previewPath);
                $lastLine = system($cmd);

                $sourcePreviewPath = sprintf('%s/b-%s-%s-source.jpg', $config['streaming']['recording']['local-path'], $broadcastID, $broadcastInfo['channel']);
                $cmd = sprintf('%s -an -i %s -vframes 1 -f image2 %s -y >/dev/null 2>&1', $ffmpeg, $localPath, $sourcePreviewPath);
                $lastLine = system($cmd);

                Misc::log(sprintf('Uploading preview %s', $remotePreviewPath), Zend_Log::WARN);
                if (file_exists($previewPath)) {
                    $redisStreamingChannelLogModel->log($broadcastInfo['channel'], $broadcastInfo['upstream_ip'], $broadcastInfo['session'], 'Client started uploading preview file');

                    $return = $this->s3->putObject(array(
                        'Bucket' => $bucket,
                        'Key' => $remotePreviewPath,
                        'SourceFile' => $previewPath,
                        'ACL' => 'public-read',
                        'ContentType' => 'image/jpeg',
                    ));

                    $return = $this->s3->putObject(array(
                        'Bucket' => $bucket,
                        'Key' => $remoteSourcePreviewPath,
                        'SourceFile' => $sourcePreviewPath,
                        'ACL' => 'public-read',
                        'ContentType' => 'image/jpeg',
                    ));
                } else {
                    $redisStreamingChannelLogModel->log($broadcastInfo['channel'], $broadcastInfo['upstream_ip'], $broadcastInfo['session'], 'Client started copying default preview');

                    $return = $this->s3->copyObject(array(
                        'Bucket' => $bucket,
                        'Key' => $remotePreviewPath,
                        'CopySource' => sprintf('%s/previews/default.jpg', $bucket),
                        'ContentType' => 'image/jpeg',
                        'ACL' => 'public-read',
                    ));

                    $return = $this->s3->copyObject(array(
                        'Bucket' => $bucket,
                        'Key' => $remoteSourcePreviewPath,
                        'CopySource' => sprintf('%s/previews/default.jpg', $bucket),
                        'ACL' => 'public-read',
                        'ContentType' => 'image/jpeg',
                    ));
                }
            }

            if (!$this->s3->doesObjectExist($bucket, $remotePath)) {
                $redisStreamingChannelLogModel->log($broadcastInfo['channel'], $broadcastInfo['upstream_ip'], $broadcastInfo['session'], 'Client started uploading video file');

                Misc::log(sprintf('Uploading broadcast %s', $remotePath), Zend_Log::WARN);
                $return = $this->s3->upload(
                    $bucket,
                    $remotePath,
                    fopen($localPath, 'r+'),
                    'public-read'
                );

                $this->checkStreamingDb();
                $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

                $streamingBroadcastModel->update($broadcastID, array(
                    'uploaded_on' => time(),
                ));

                Misc::log(sprintf('Uploaded %d(%d)', $broadcastID, $broadcastInfo['channel']), Zend_Log::WARN);
            }
        } catch (Exception $e) {
            Misc::log($e->getMessage(), Zend_Log::ERR);
        }

        return true;
    }
}