<?php
class StreamingController extends CliController
{
    const MAX_LOOP = 3600;

    protected $streamingDb;

    protected $redisStreaming;

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

    public function push_channelAction()
    {
        $this->getStreamingDb();
        $this->getRedisStreaming();

        $streamingPushScheduleModel = new MySQL_Streaming_PushScheduleModel($this->streamingDb);

        if ($channel = $streamingPushScheduleModel->getRightOne()) {
            $redisStreamingClientOneModel = new Redis_Streaming_ClientOneModel($this->redisStreaming);

            $redisStreamingClientOneModel->set($channel);
        }

        return false;
    }

    public function auto_push_channelAction()
    {
        $this->getStreamingDb();
        $this->getRedisStreaming();

        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
        $lives = $redisStreamingChannelOnlineChannelModel->getList();
        $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
        if ($lives = $redisStreamingChannelOnlineClientByChannelModel->mget($lives)) {
            asort($lives, SORT_NUMERIC);

            $parts = array();

            foreach ($lives as $key => $val) {
//                if ($val < 100) {
                if ($val < 20) {
                    $parts[$key] = 0;
                }
            }

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $channelInfos = $streamingChannelModel->getRows(array_keys($parts), array(
                'class',
            ));

            $streamingChannelClassModel = new MySQL_Streaming_ChannelClassModel($this->streamingDb);
            $levelMap = $streamingChannelClassModel->getLevelMap();
            foreach ($channelInfos as $row) {
                $parts[$row['id']] = $levelMap[$row['class']];
            }

            arsort($parts, SORT_NUMERIC);

            if ($channel = key($parts)) {
                $redisStreamingClientOneModel = new Redis_Streaming_ClientOneModel($this->redisStreaming);

                $redisStreamingClientOneModel->set($channel);
            }
        }

        return false;
    }

    public function check_broadcast_uploadAction()
    {
        $hours = $this->getRequest()->get('hours', 1);

        $this->getStreamingDb();

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

        // Disable fix no ending, trust media server will always notice
//        $streamingBroadcastModel->fixNoEnding(72);

        foreach ($streamingBroadcastModel->getFailedUpload($hours) as $row) {
            $client = new Yar_Client(sprintf('http://%s/service/broadcast/upload', $row['recording_ip']));
            $client->setOpt(YAR_OPT_CONNECT_TIMEOUT, 3000);
            $client->setOpt(YAR_OPT_TIMEOUT, 0);
            $result = $client->upload_v2($row['id']);
        }

        return false;
    }

    public function find_playing_game_onlineAction()
    {
        $request = $this->getRequest();

        $playingGame = 14;

        $this->getRedisStreaming();
        $this->getStreamingDb();

        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
        $channels = $redisStreamingChannelOnlineChannelModel->getList();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        if ($rows = $streamingChannelModel->getRows($channels, array('id', 'playing_game'))) {
//            $redisStreamingChannelOnlineClientModel = new Redis_Streaming_Channel_Online_ClientModel($this->redisStreaming);
            $redisStreamingChannelRankingGameModel = new Redis_Streaming_Channel_Ranking_GameModel($this->redisStreaming);
            $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
            $lives = $redisStreamingChannelOnlineClientByChannelModel->mget($channels);

            foreach ($rows as $row) {
                if ($row['playing_game'] == $playingGame) {
//                    $watchingNow = $redisStreamingChannelOnlineClientModel->total($row['id']);
                    $watchingNow = $lives[$row['id']];
                    $redisStreamingChannelRankingGameModel->update($playingGame, $row['id'], $watchingNow);
                }
            }
        }

        return false;
    }

    public function watching_length_dailyAction()
    {
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $result = $paypals = array();

        $timestamp  = $request->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        $to     = mktime(0, 0, 0, $month, $day, $year);
        $from   = strtotime('-1 day', $to);

        $date   = date('Ymd', $from);

        try {
            $this->getStreamingDb();

            $streamingWatchingLengthLogModel = new MySQL_Streaming_WatchingLengthLogModel($this->streamingDb);
            $summary = $streamingWatchingLengthLogModel->summary($from, $to);

            $data = array(
                'dt' => $date,
                'views' => $summary['v'],
                'length' => $summary['l'],
                'created_on' => time(),
            );
            $streamingWatchingLengthSummaryModel = new MySQL_Streaming_WatchingLengthSummaryModel($this->streamingDb);
            $streamingWatchingLengthSummaryModel->insert($data);
        } catch (Exception $e) {
            Misc::log($e->getMessage(), Zend_Log::ERR);
        }

        return false;
    }
}