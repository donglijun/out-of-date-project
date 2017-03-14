<?php
use Aws\S3\S3Client;

class LeagueapplicationController extends AdminController
{
    protected $authActions = array(
        'list' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'approve' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'deny' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'view' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'memo' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'modify_teams' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

    protected $s3;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function getS3()
    {
        if (empty($this->s3)) {
            $config = Yaf_Registry::get('config')->toArray();

            $this->s3 = S3Client::factory(array(
                'key' => $config['aws']['s3']['key'],
                'secret' => $config['aws']['s3']['secret'],
                'region' => $config['aws']['s3']['region'],
            ));
        }

        return $this->s3;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $classes = $names = $channelClasses = array();

        $this->getStreamingDb();
        $this->getPassportDb();
//        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);

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
        if (($appStatus = $request->get('app_status', '')) !== '') {
            $where[] = "`app_status`=" . (int) $appStatus;
            $filter['app_status'] = $appStatus;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $leagueApplicationModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/leagueapplication/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['states'] = MySQL_League_ApplicationModel::getStatusMap();
        $result['seasons'] = $leagueSeasonModel->getAll();

        $this->getView()->assign($result);
    }

    public function approveAction()
    {
        $result = array(
            'code' => 500,
        );
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);

            if ($appInfo = $leagueApplicationModel->getRow($id)) {
                $affectedCount = $leagueApplicationModel->approve($id, $userid);

                $result['code'] = 200;
                $result['message'] = 'ok';
            } else {
                $result['code'] = 404;
                $result['message'] = 'Invalid application';
            }
        } else {
            $result['code'] = 404;
            $result['message'] = 'Missing parameter';
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueapplication/list');

        return false;
    }

    public function denyAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id')) {
            $this->getStreamingDb();

            $userid = $this->session->admin['user'];

            $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
            $affectedCount = $leagueApplicationModel->deny($id, $userid, $request->get('reason', ''));
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueapplication/list');

        return false;
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getPassportDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);
        $leagueSeasonModel = new MySQL_League_SeasonModel($this->streamingDb);

        if ($id = $request->get('id')) {
            $appInfo = $leagueApplicationModel->getRow($id, $leagueApplicationModel->getFields());

            $appInfo['teams'] = json_decode($appInfo['teams'], true);
        }

        $this->_view->assign(array(
            'data'  => $appInfo,
            'states' => MySQL_Streaming_ApplicationModel::getStatusMap(),
            'seasons' => $leagueSeasonModel->getAll(),
        ));
    }

    public function memoAction()
    {
        $result = array();
        $request = $this->getRequest();

        if (($id = $request->get('id')) && ($memo = $request->get('memo'))) {
            $this->getStreamingDb();
            $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);

            $leagueApplicationModel->update($id, array(
                'memo'  => $memo,
            ));

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueapplication/list');

        return false;
    }

    public function modify_teamsAction()
    {
        $result = array();
        $request = $this->getRequest();

        if (($id = $request->get('id')) && ($teams = $request->get('teams'))) {
            $teams = strtr($teams, array(
                "\r"    => "\n",
            ));
            $teams = array_filter(explode("\n", $teams));

            $members = array();

            foreach ($teams as $key => $val) {
                $member = array();

                $val = array_filter(explode("\t", $val));
                foreach ($val as $chunk) {
                    list($k, $v, ) = explode(':', $chunk);

                    $member[$k] = $v;
                }

                $members[] = $member;
            }

            $this->getStreamingDb();
            $leagueApplicationModel = new MySQL_League_ApplicationModel($this->streamingDb);

            $leagueApplicationModel->update($id, array(
                'teams'  => json_encode($members),
            ));

            $result['code'] = 200;
            $result['message'] = 'ok';
        } else {
            $result['code'] = 404;
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/leagueapplication/list');

        return false;
    }
}