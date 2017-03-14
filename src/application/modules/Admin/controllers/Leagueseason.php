<?php
class LeagueseasonController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'update'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'open'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'lock'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'close'     => MySQL_AdminAccountModel::GROUP_DIRECTOR,
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
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('leagueseason/edit.phtml'));
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

        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
        $result = $leagueSeasonModel->search('*', null, null, $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/leagueseason/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

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
            );

            $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
            $leagueSeasonModel->insert($data);

            $this->redirect('/admin/leagueseason/list');

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
            $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);

            if ($request->isPost()) {
                $data = array(
                    'title'  => $request->get('title', ''),
                );

                $affectedCount = $leagueSeasonModel->update($id, $data);

                $this->redirect('/admin/leagueseason/list');

                return false;
            } else {
                $data = $leagueSeasonModel->getRow($id, $leagueSeasonModel->getFields());
                $this->_view->assign('id', $id);
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function openAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
            $affectedCount = $leagueSeasonModel->open($id);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueseason/list');

        return false;
    }

    public function lockAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
            $affectedCount = $leagueSeasonModel->lock($id);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueseason/list');

        return false;
    }

    public function closeAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);
            $affectedCount = $leagueSeasonModel->close($id);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueseason/list');

        return false;
    }
}