<?php
class StreamingController extends ServiceController
{
    protected $authActions = array();

    protected $streamingDb;

    protected $redisStreaming;

    protected function parseRawPostData()
    {
        $data = file_get_contents("php://input");

        return json_decode($data, true);
    }

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

    public function validatekeyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
//            if ($streamKey = $request->get('stream_key', '')) {
//                list($prefix, $channelID, $channelHash, ) = explode('_', $streamKey);
//            } else {
//                $channelID = $request->get('channel_id', '');
//                $channelHash = $request->get('channel_hash', '');
//            }
            $streamKey = isset($postdata['stream_key']) ? $postdata['stream_key'] : '';
            if ($streamKey) {
                list($prefix, $channelID, $channelHash, ) = explode('_', $streamKey);
                $channelID = (int) $channelID;
            }
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';

            if ($channelID && $channelHash) {
                $this->getStreamingDb();
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

                if ($row = $streamingChannelModel->getRow($channelID)) {
                    if ($row['is_banned']) {
                        $result['code'] = 509;
                    } else if ($row['hash'] != $channelHash) {
                        $result['code'] = 403;
                    } else {
                        $result['code'] = 200;
                        $result['channel_id'] = $channelID;
                        $timestamp = time();
                        $hourlyPay = $exclusiveBonus = 0;

                        $this->getRedisStreaming();

                        $redisStreamingChannelLogModel = new Redis_Streaming_Channel_LogModel($this->redisStreaming);
                        $redisStreamingChannelLogModel->log($channelID, $ip, $session, 'Media server triggered opening');

                        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
                        $redisStreamingChannelOnlineChannelModel->open($channelID);

                        $redisStreamingChannelOnlineGameModel = new Redis_Streaming_Channel_Online_GameModel($this->redisStreaming);
                        $redisStreamingChannelOnlineGameModel->open($channelID, $row['playing_game']);

                        $streamingChannelModel->online($channelID, $ip);

                        $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);
                        if ($classRow = $streamingChannelClassModel->getRow($row['class'])) {
                            $hourlyPay = $classRow['hourly_pay'];
                            $exclusiveBonus = $row['is_exclusive'] ? $classRow['exclusive_bonus'] : 0;

//                            if ($row['is_exclusive']) {
//                                $hourlyPay *= 1 + $classRow['exclusive_bonus'];
//                            }
                        }

                        $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);

                        // Check unclosed streaming
                        if (($logInfo = $streamingLiveLengthLogModel->last($channelID)) && (!$logInfo['to'])) {
                            $length = 0;
//                            $length = $timestamp - $logInfo['from'];
//                            $streamingLiveLengthLogModel->update($logInfo['id'], array(
//                                'to'        => $timestamp,
//                                'length'    => $length,
//                            ));

                            $redisStreamingChannelLogModel->log($channelID, $logInfo['upstream_ip'], $logInfo['session'], 'Streaming service found unclosed session');

                            // Stop recording
                            $workload = array(
                                'channel'       => $channelID,
                                'upstream_ip'   => $logInfo['upstream_ip'],
                                'session'       => $logInfo['session'],
                                'length'        => $length,
                                'ending_on'     => $timestamp,
                            );

                            $gearmanClient = Daemon::getGearmanClient();
                            $gearmanClient->doBackground('streaming-stop-recording', json_encode($workload));

                            if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                                Misc::log(sprintf("gearman job (streaming-stop-recording) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                            }
                        }

                        $row = array(
                            'channel'         => $channelID,
                            'upstream_ip'     => $ip,
                            'session'         => $session,
                            'from'            => $timestamp,
                            'hourly_pay'      => $hourlyPay,
                            'exclusive_bonus' => $exclusiveBonus,
                            'stream_key'      => $streamKey,
                        );

                        $streamingLiveLengthLogModel->insert($row);

                        $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
                        $streamingColumnItemModel->liftStreaming($channelID);

                        // Start recording
                        $gearmanClient = Daemon::getGearmanClient();
                        $gearmanClient->doBackground('streaming-start-recording', json_encode($row));

                        if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                            Misc::log(sprintf("gearman job (streaming-start-recording) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                        }
                    }
                } else {
                    $result['code'] = 404;
                }
            } else {
                $result['code'] = 403;
            }

            $result['id'] = $session;
        }

        echo json_encode($result);

        return false;
    }

    public function getkeyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
//            $channelID = isset($postdata['channel_id']) ? (int) $postdata['channel_id'] : '';
            if (strpos($postdata['channel_id'], '_') !== false) {
                list($channelID, $width, ) = explode('_', $postdata['channel_id']);
            } else {
                $channelID = $postdata['channel_id'];
                $width = 0;
            }

            $channelID = (int) $channelID;
            $playerCount = (int) $postdata['player_count'];
            $width = (int) $width;
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';
            $timestamp = time();

            if ($channelID) {
                $this->getStreamingDb();
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

                if ($row = $streamingChannelModel->getRow($channelID, array('hash', 'is_banned'))) {
                    if ($row['is_banned']) {
                        $result['code'] = 509;
                        $result['message'] = 'Channel is banned';
                    } else {
                        $result['code'] = 200;
                        $result['data']['stream_key'] = MySQL_Streaming_ChannelModel::makeStreamKey($channelID, $row['hash']) . ($width ? '_' . $width : '' );

                        $this->getRedisStreaming();

                        $redisStreamingChannelOnlineClientByServerModel = new Redis_Streaming_Channel_Online_ClientByServerModel($this->redisStreaming);
//                        $redisStreamingChannelOnlineClientByServerModel->incr($ip, $channelID);
                        $redisStreamingChannelOnlineClientByServerModel->set($channelID, $ip, $playerCount);

                        $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//                        $redisStreamingChannelOnlineClientByChannelModel->incr($channelID);
                        $redisStreamingChannelOnlineClientByChannelModel->set($channelID, $redisStreamingChannelOnlineClientByServerModel->getChannel($channelID));
//
//                        $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
//                        $redisStreamingChannelOnlineClientModel->join($channelID, $ip, $session);

                        $streamingWatchingLengthLogModel = new MySQL_Streaming_WatchingLengthLogModel($this->streamingDb);
                        $streamingWatchingLengthLogModel->insert(array(
                            'channel' => $channelID,
                            'upstream_ip' => $ip,
                            'session' => $session,
                            'from' => $timestamp,
                        ));
                    }
                } else {
                    $result['code'] = 404;
                }
            } else {
                $result['code'] = 403;
            }

            $result['id'] = $session;
        }

        echo json_encode($result);

        return false;
    }

    public function client_closeAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
//            $channelID = isset($postdata['channel_id']) ? (int) $postdata['channel_id'] : '';
            if (strpos($postdata['channel_id'], '_') !== false) {
                list($channelID, $width, ) = explode('_', $postdata['channel_id']);
            } else {
                $channelID = $postdata['channel_id'];
                $width = 0;
            }

            $channelID = (int) $channelID;
            $playerCount = (int) $postdata['player_count'];
            $width = (int) $width;
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';
            $timestamp = time();

            $this->getRedisStreaming();
            $this->getStreamingDb();

            $redisStreamingChannelOnlineClientByServerModel = new Redis_Streaming_Channel_Online_ClientByServerModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientByServerModel->decr($ip, $channelID);
            $redisStreamingChannelOnlineClientByServerModel->set($channelID, $ip, $playerCount);

            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientByChannelModel->decr($channelID);
            $redisStreamingChannelOnlineClientByChannelModel->set($channelID, $redisStreamingChannelOnlineClientByServerModel->getChannel($channelID));
//
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientModel->leave($channelID, $ip, $session);

//            $redisStreamingChannelPausedModel = new Redis_Streaming_Channel_PausedModel($this->redisStreaming);
//            $redisStreamingChannelPausedModel->remove($channelID, $ip, $session);

            $streamingWatchingLengthLogModel = new MySQL_Streaming_WatchingLengthLogModel($this->streamingDb);
            if ($logInfo = $streamingWatchingLengthLogModel->locate($channelID, $ip, $session)) {
                $streamingWatchingLengthLogModel->update($logInfo['id'], array(
                    'to' => $timestamp,
                    'length' => $timestamp - $logInfo['from'],
                ));
            }

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function client_pauseAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
//            $channelID = isset($postdata['channel_id']) ? (int) $postdata['channel_id'] : '';
            if (strpos($postdata['channel_id'], '_') !== false) {
                list($channelID, $width, ) = explode('_', $postdata['channel_id']);
            } else {
                $channelID = $postdata['channel_id'];
                $width = 0;
            }
            $channelID = (int) $channelID;
            $width = (int) $width;
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';

            $this->getRedisStreaming();

            if (isset($postdata['pause'])) {
//                $redisStreamingChannelPausedModel = new Redis_Streaming_Channel_PausedModel($this->redisStreaming);
//
//                if ($postdata['pause']) {
//                    $redisStreamingChannelPausedModel->add($channelID, $ip, $session);
//                } else {
//                    $redisStreamingChannelPausedModel->remove($channelID, $ip, $session);
//                }

                $result['code'] = 200;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function channel_closeAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
            $channelID = isset($postdata['channel_id']) ? (int) $postdata['channel_id'] : '';
            $timestamp = time();
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';

            $this->getRedisStreaming();

            $redisStreamingChannelLogModel = new Redis_Streaming_Channel_LogModel($this->redisStreaming);
            $redisStreamingChannelLogModel->log($channelID, $ip, $session, 'Media server triggered closing');

            $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
            $redisStreamingChannelOnlineChannelModel->close($channelID);

            $redisStreamingChannelOnlineGameModel = new Redis_Streaming_Channel_Online_GameModel($this->redisStreaming);
            $redisStreamingChannelOnlineGameModel->close($channelID);

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->getStreamingDb());
            $streamingChannelModel->offline($channelID);

            $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
            $streamingColumnItemModel->lowerStreaming($channelID);

            $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);
            if (($logInfo = $streamingLiveLengthLogModel->locate($channelID, $ip, $session)) && (!$logInfo['to'])) {
                $length = $timestamp - $logInfo['from'];
                $streamingLiveLengthLogModel->update($logInfo['id'], array(
                    'to'        => $timestamp,
                    'length'    => $length,
                ));

                // Stop recording
                $workload = array(
                    'channel'       => $channelID,
                    'upstream_ip'   => $ip,
                    'session'       => $session,
                    'length'        => $length,
                    'ending_on'     => $timestamp,
                );

                $gearmanClient = Daemon::getGearmanClient();
                $gearmanClient->doBackground('streaming-stop-recording', json_encode($workload));

                if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                    Misc::log(sprintf("gearman job (streaming-stop-recording) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                }
            }

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function server_restartAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
            $this->getRedisStreaming();
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';

//            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
//            $redisStreamingChannelOnlineClientByServerModel = new Redis_Streaming_Channel_Online_ClientByServerModel($this->redisStreaming);
//            $affectedChannel = $redisStreamingChannelOnlineClientByServerModel->getAll($ip);
//
//            foreach ($affectedChannel as $channelID => $num) {
//                $redisStreamingChannelOnlineClientByChannelModel->decr($channelID, $num);
//            }
//
//            $redisStreamingChannelOnlineClientByServerModel->clear($ip);

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function transcode_videoAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($postdata = $this->parseRawPostData()) {
            Misc::log(sprintf('Receive trancode request: %s', var_export($postdata, true)), Zend_Log::WARN);
            $ip = Misc::getClientIp();
            $session = isset($postdata['id']) ? $postdata['id'] : '';
            $channelID = isset($postdata['channel_id']) ? (int) $postdata['channel_id'] : '';
            $width = isset($postdata['width']) ? (int) $postdata['width'] : 0;
            $height = isset($postdata['height']) ? (int) $postdata['height'] : 0;

            if ($resolutions = Mkjogo_Streaming_Recording::getAvailableResolutions($height)) {
                $p = array();
                foreach ($resolutions as $val) {
                    $p[] = $val['h'];
                }
                $resolutions = implode(',', $p);
            } else {
                $resolutions = '';
            }

            $this->getStreamingDb();

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $streamingChannelModel->update($channelID, array(
                'resolutions' => $resolutions,
            ));

            $channelInfo = $streamingChannelModel->getRow($channelID, array('id', 'hash'));
            $streamKey = MySQL_Streaming_ChannelModel::makeStreamKey($channelInfo['id'], $channelInfo['hash']);

            $workload = array(
                'channel'       => $channelID,
                'upstream_ip'   => $ip,
                'session'       => $session,
                'width'         => $width,
                'height'        => $height,
                'stream_key'    => $streamKey,
            );

            $gearmanClient = Daemon::getGearmanClient();
            $gearmanClient->doBackground('streaming-transcode-video', json_encode($workload));

            Misc::log(sprintf('Send job data: %s', var_export($workload, true)), Zend_Log::WARN);

            if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                Misc::log(sprintf("gearman job (streaming-transcode-video) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
            }

            $result['code'] = 200;
            $result['id'] = $session;
        }

        echo json_encode($result);

        return false;
    }
}