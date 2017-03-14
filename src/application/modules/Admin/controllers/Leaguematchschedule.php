<?php
class LeaguematchscheduleController extends AdminController
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
        $leagueSeasonModel = new MySQL_League_SeasonModel($this->getStreamingDb());

        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
            'seasons' => $leagueSeasonModel->getAll(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('leaguematchschedule/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

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
        $where = $where ? implode(' AND ', $where) : '';

        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);
        $result = $leagueMatchScheduleModel->search('*', $where, '`from` ASC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/leaguematchschedule/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;
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
                'title' => $request->get('title', ''),
                'season' => $request->get('season', 0),
                'from' => strtotime($request->get('from')),
                'to' => strtotime($request->get('to')),
                'created_on' => $request->getServer('REQUEST_TIME'),
            );

            $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);
            $leagueMatchScheduleModel->insert($data);

            $this->redirect('/admin/leaguematchschedule/list');

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
            $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);

            if ($request->isPost()) {
                $data = array(
                    'title' => $request->get('title', ''),
                    'season' => $request->get('season', ''),
                    'from' => strtotime($request->get('from')),
                    'to' => strtotime($request->get('to')),
                );

                $affectedCount = $leagueMatchScheduleModel->update($id, $data);

                $this->redirect('/admin/leaguematchschedule/list');

                return false;
            } else {
                $data = $leagueMatchScheduleModel->getRow($id, $leagueMatchScheduleModel->getFields());
                $this->_view->assign('id', $id);
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
            $leagueMatchScheduleModel = new MySQL_League_MatchScheduleModel($this->streamingDb);
            $affectedCount = $leagueMatchScheduleModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leaguematchschedule/list');

        return false;
    }
}