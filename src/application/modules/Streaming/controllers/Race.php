<?php
class RaceController extends ApiController
{
    protected $authActions = array(
    );

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

    public function playing_game_onlineAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $game = $request->get('game', 14);
        $date = $request->get('date');
        $timestamp = $date ? strtotime($date) : time();
        $limit = $request->get('limit', 3);

        $this->getRedisStreaming();

        $redisStreamingChannelRankingGameModel = new Redis_Streaming_Channel_Ranking_GameModel($this->redisStreaming);
        $result['data'] = $redisStreamingChannelRankingGameModel->top($game, $limit, $timestamp);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}