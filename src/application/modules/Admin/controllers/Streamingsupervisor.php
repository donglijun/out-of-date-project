<?php
class StreamingsupervisorController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'add'       => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $streamingDb;

    protected $passportDb;

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

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $where = $where ? implode(' AND ', $where) : '';

        $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
        $result = $streamingSupervisorModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingsupervisor/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $result = array(
            'code'  => 500,
        );

        $user = $request->get('user', 0);

        $this->getStreamingDb();
        $this->getPassportDb();

        $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

        if ($row = $userAccountModel->getRow($user, array('name'))) {
            try {
                $data = array(
                    'id'         => $user,
                    'name'       => $row['name'],
                    'created_on' => $timestamp,
                );

                $streamingSupervisorModel->insert($data);

                $result['code'] = 200;
                $result['message'] = 'ok';
            } catch (Exception $e) {
                $result['message'] = 'Supervisor exists';
            }
        } else {
            $result['code'] = 404;
            $result['message'] = 'User not exists';
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingsupervisor/list');

        return false;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);
            $affectedCount = $streamingSupervisorModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingsupervisor/list');

        return false;
    }
}