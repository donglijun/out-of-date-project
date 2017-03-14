<?php
class SeasonController extends ApiController
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

    public function check_statusAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        if ($season = $request->get('season')) {
            $result['data'] = $leagueSeasonModel->getRow($season);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function get_teamsAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);

        if (($season = $request->get('season')) && ($seasonInfo = $leagueSeasonModel->getRow($season, array('status'))) && ($seasonInfo['status'] >= MySQL_League_SeasonModel::STATUS_LOCKING)) {
            $teams = $leagueApplicationModel->getSeasonTeams($season, array(
                'id',
                'title',
                'teams',
                'logo',
                'video',
                'description',
            ));

            foreach ($teams as $key => $val) {
                $teams[$key]['teams'] = json_decode($val['teams'], true);
            }

            $result['data'] = $teams;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function get_schedulesAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();
        $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);

        if ($season = $request->get('season')) {
            $result['data'] = $leagueMatchScheduleModel->getBySeason($season);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function get_matchesAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);
        if ($season = $request->get('season')) {
            $data  = $leagueMatchModel->getRowsBySeason($season);

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

    public function get_team_videosAction()
    {
        $request = $this->getRequest();
        $data = $rows = array();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);
        if (($season = $request->get('season')) && ($team = $request->get('team'))) {
            $rows  = $leagueMatchModel->getVideosByTeam($season, $team);

            foreach ($rows as $key => $val) {
                $val['video_data'] = json_decode($val['video_data'], true);

                $data[$key] = $val;
            }

            $result['data'] = $data;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function get_ranksAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        $leagueRankModel = new MySQL_League_RankModel($this->streamingDb);
        if ($season = $request->get('season')) {
            $data  = $leagueRankModel->getRowsBySeason($season);

            $result['data'] = array_merge($data, array());
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}