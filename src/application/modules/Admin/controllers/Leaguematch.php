<?php
class LeaguematchController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'update'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'delete'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'get_schedules_by_season' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'get_teams_by_season' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function gotoEdit($action, $data)
    {
        $this->getStreamingDb();
        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
            'seasons' => $leagueSeasonModel->getAll(),
            'channels' => $streamingChannelModel->getRowsBySpecial('league', array('alias')),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('leaguematch/edit.phtml'));
    }

    public function get_schedules_by_seasonAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        if ($season = $request->get('season')) {
            $this->getStreamingDb();
            $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);
            $result['data'] = $leagueMatchScheduleModel->getBySeason($season);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function get_teams_by_seasonAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        if ($season = $request->get('season')) {
            $this->getStreamingDb();

            $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
            $result['data'] = $leagueApplicationModel->getSeasonTeams($season, array('title'));
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
        $where = $teams = $teamTitles = array();

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($season = $request->get('season')) {
            $where[] = "`season`=" . (int) $season;
            $filter['season'] = $season;
        }
        if ($schedule = $request->get('schedule')) {
            $where[] = "`schedule`=" . (int) $schedule;
            $filter['schedule'] = $schedule;
        }
        $where = $where ? implode(' AND ', $where) : '0 = 1';

        $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);
        $result = $leagueMatchModel->search('*', $where, '`id` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/leaguematch/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        if ($season) {
            $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
            $teams = $leagueApplicationModel->getSeasonTeams($season, array('title'));
            foreach ($teams as $team) {
                $teamTitles[$team['id']] = $team['title'];
            }
            $teamTitles[-1] = '-- Cancelled --';
            $result['teamTitles'] = $teamTitles;

            $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);
            $result['schedules'] = $leagueMatchScheduleModel->getBySeason($season);
        }

        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);

        $result['seasons'] = $leagueSeasonModel->getAll();


        $this->getView()->assign($result);
    }

    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        if ($request->isPost()) {
            $data = array(
                'season' => $request->get('season', 0),
                'schedule' => $request->get('schedule', 0),
                'group_tag' => $request->get('group_tag', ''),
                'team1' => $request->get('team1', 0),
                'team2' => $request->get('team2', 0),
                'winner' => $request->get('winner', 0),
                'channel' => $request->get('channel', 0),
                'score_data' => json_encode($request->get('score_data', array())),
                'video_data' => json_encode(array_merge(array_filter(explode("\n", strtr($request->get('video_data', ''), array("\r" => "\n")))), array())),
                'datetime' => strtotime($request->get('datetime')),
                'created_on' => $request->getServer('REQUEST_TIME'),
            );

            $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);
            $leagueMatchModel->insert($data);

            $this->redirect("/admin/leaguematch/list?season={$data['season']}&schedule={$data['schedule']}");

            return false;
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        $id = $request->get('id', 0);
        if ($id) {
            $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);

            if ($request->isPost()) {
                $data = array(
                    'season' => $request->get('season', 0),
                    'schedule' => $request->get('schedule', 0),
                    'group_tag' => $request->get('group_tag', ''),
                    'team1' => $request->get('team1', 0),
                    'team2' => $request->get('team2', 0),
                    'winner' => $request->get('winner', 0),
                    'channel' => $request->get('channel', 0),
                    'score_data' => json_encode($request->get('score_data', array())),
                    'video_data' => json_encode(array_merge(array_filter(explode("\n", strtr($request->get('video_data', ''), array("\r" => "\n")))), array())),
                    'datetime' => strtotime($request->get('datetime')),
                );

                $affectedCount = $leagueMatchModel->update($id, $data);

                $this->redirect("/admin/leaguematch/list?season={$data['season']}&schedule={$data['schedule']}");

                return false;
            } else {
                $data = $leagueMatchModel->getRow($id, $leagueMatchModel->getFields());
                $data['score_data'] = json_decode($data['score_data'], true);
                $data['video_data'] = implode("\n", json_decode($data['video_data'], true));

                $teamTitles = $schedules = array();

                if ($data['season']) {
                    $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
                    $teams = $leagueApplicationModel->getSeasonTeams($data['season'], array('title'));
                    foreach ($teams as $team) {
                        $teamTitles[$team['id']] = $team['title'];
                    }

                    $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);
                    $schedules = $leagueMatchScheduleModel->getBySeason($data['season']);
                }

                $this->_view->assign(array(
                    'id' => $id,
                    'teamTitles' => $teamTitles,
                    'schedules' => $schedules,
                ));
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $leagueMatchModel = new MySQL_League_MatchModel($this->streamingDb);
            $affectedCount = $leagueMatchModel->delete($ids);
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
                'data'  => array(
                    'affected'  => $affectedCount,
                ),
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leaguematch/list');

        return false;
    }
}