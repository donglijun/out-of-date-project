<?php
class GoldpackageController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
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
            'clients'   => MySQL_Gold_PackageModel::getClientMap(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('goldpackage/edit.phtml'));
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
        if (($client = $request->get('client')) || ($client === '0')) {
            $where[] = "`client`=" . (int) $client;
            $filter['client'] = $client;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $goldPackageModel = new MySQL_Gold_PackageModel($this->streamingDb);
        $result = $goldPackageModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/goldpackage/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;
        $result['clients'] = MySQL_Gold_PackageModel::getClientMap();

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('goldpackage/list.phtml'));

        return false;
    }

    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();

        if ($request->isPost()) {
            $data = array(
                'title'      => $request->get('title', ''),
                'money'      => $request->get('money', 0),
                'money_unit' => $request->get('money_unit', ''),
                'golds'      => $request->get('golds', 0),
                'bonus'      => $request->get('bonus', 0),
                'client'     => $request->get('client', 0),
                'created_on' => time(),
            );

            $goldPackageModel = new MySQL_Gold_PackageModel($this->streamingDb);
            $goldPackageModel->insert($data);

            $this->redirect('/admin/goldpackage/list');

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
            $goldPackageModel = new MySQL_Gold_PackageModel($this->streamingDb);

            if ($request->isPost()) {
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                if ($money = $request->get('money')) {
                    $data['money'] = $money;
                }

                if ($moneyUnit = $request->get('money_unit')) {
                    $data['money_unit'] = $moneyUnit;
                }

                if ($golds = $request->get('golds')) {
                    $data['golds'] = $golds;
                }

                if ($bonus = $request->get('bonus')) {
                    $data['bonus'] = $bonus;
                }

                if (($client = $request->get('client')) || ($client === '0')) {
                    $data['client'] = $client;
                }

                if ($data) {
                    $affectedCount = $goldPackageModel->update($id, $data);
                }

                $this->redirect('/admin/goldpackage/list');

                return false;
            } else {
                $data = $goldPackageModel->getRow($id);

                $this->_view->assign(array(
                    'id'    => $id,
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
            $goldPackageModel = new MySQL_Gold_PackageModel($this->streamingDb);
            $affectedCount = $goldPackageModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/goldpackage/list');

        return false;
    }
}