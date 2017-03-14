<?php
class MatchController extends ApiController
{
    protected $authActions = array();

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

    public function get_by_seasonAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);
        if (($season = $request->get('season')) && ($data  = $leagueMatchModel->getRowsBySeason($season))) {
            foreach ($data as $key => $val) {
                $val['score_data'] = json_decode($val['score_data'], true);
                $val['video_data'] = json_decode($val['video_data'], true);

                $data[$key] = $val;
            }

            $result['data'] = array_merge($data, array());
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}