<?php
class ServerController extends ApiController
{
    protected $authActions = array();

    protected $redisStreaming;

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    public function rollAction()
    {
        $result = array(
            'code'  => 500,
        );

        $redisStreamingServerWeightingModel = new Redis_Streaming_Server_WeightingModel($this->getRedisStreaming());

        $result['data'] = $redisStreamingServerWeightingModel->retrieve();
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}