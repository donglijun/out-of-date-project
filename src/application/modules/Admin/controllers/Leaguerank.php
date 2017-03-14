<?php
class LeaguerankController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'update'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'delete'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
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

        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
            'seasons' => $leagueSeasonModel->getAll(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('leaguerank/edit.phtml'));
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

        $leagueRankModel = new MySQL_League_RankModel($this->streamingDb);
        $result = $leagueRankModel->search('*', $where, '`id` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/leaguerank/list?' . http_build_query($filter);

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
                'team' => $request->get('team', 0),
                'wins' => $request->get('wins', 0),
                'loses' => $request->get('loses', 0),
                'k' => $request->get('k', 0),
                'd' => $request->get('d', 0),
                'a' => $request->get('a', 0),
                'points' => $request->get('points', 0),
                'rank' => $request->get('rank', 0),
            );

            $leagueRankModel = new MySQL_League_RankModel($this->streamingDb);
            $leagueRankModel->insert($data);

            $this->redirect("/admin/leaguerank/list?season={$data['season']}&schedule={$data['schedule']}");

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
            $leagueRankModel = new MySQL_League_RankModel($this->streamingDb);

            if ($request->isPost()) {
                $data = array(
                    'season' => $request->get('season', 0),
                    'schedule' => $request->get('schedule', 0),
                    'group_tag' => $request->get('group_tag', ''),
                    'team' => $request->get('team', 0),
                    'wins' => $request->get('wins', 0),
                    'loses' => $request->get('loses', 0),
                    'k' => $request->get('k', 0),
                    'd' => $request->get('d', 0),
                    'a' => $request->get('a', 0),
                    'points' => $request->get('points', 0),
                    'rank' => $request->get('rank', 0),
                );

                $affectedCount = $leagueRankModel->update($id, $data);

                $this->redirect("/admin/leaguerank/list?season={$data['season']}&schedule={$data['schedule']}");

                return false;
            } else {
                $data = $leagueRankModel->getRow($id, $leagueRankModel->getFields());

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
            $leagueRankModel = new MySQL_League_RankModel($this->streamingDb);
            $affectedCount = $leagueRankModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leaguerank/list');

        return false;
    }
}