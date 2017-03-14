<?php
class TimecardController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'import'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'view'      => MySQL_AdminAccountModel::GROUP_ADMIN,
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
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
        if ($code = $request->get('code')) {
            $where[] = "`code`=" . $this->streamingDb->quote($code);
            $filter['code'] = $code;
        }
        if ($user = $request->get('user')) {
            $where[] = "`consumed_by`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $cardCardModel = new MySQL_Card_CardModel($this->streamingDb);
        $result = $cardCardModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/timecard/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);
        $result['types'] = $cardTypeModel->getAll();

        $this->getView()->assign($result);
    }

    public function importAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        $this->getStreamingDb();

        $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);

        $cards = $request->get('cards');
        $type = $request->get('type');

        if ($request->isPost() && $cardTypeModel->getRow($type, array('id'))) {
            $count = 0;

            $cards = strtr($cards, array(
                "\r"    => "\n",
            ));
            $cards = preg_split('|\s+|', $cards);

            $cardCardModel = new MySQL_Card_CardModel($this->streamingDb);

            foreach ($cards as $code) {
                if ($code) {
                    try {
                        $this->streamingDb->beginTransaction();

                        $cardCardModel->insert(array(
                            'code'       => $code,
                            'type'       => $type,
                            'created_on' => $request->getServer('REQUEST_TIME'),
                            'created_by' => $this->session->admin['user'],
                        ));

                        $cardTypeModel->incrNumber($type);

                        $this->streamingDb->commit();

                        $count++;
                    } catch (Exception $e) {
                        $this->streamingDb->rollBack();

                        $result['data']['failed'][] = $code;
                    }
                }
            }

            if ($count) {
                $result['data']['count'] = $count;
                $result['code'] = 200;
            }
        } else {
            $result['code'] = 404;
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/timecard/list');

        return false;
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $id = $request->get('id', 0);

        if ($id) {
            $this->getStreamingDb();

            $cardCardModel = new MySQL_Card_CardModel($this->streamingDb);

            if ($data = $cardCardModel->getRow($id)) {
                $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);

                $cardTypeInfo = $cardTypeModel->getRow($data['type']);

                $data['type_title'] = $cardTypeInfo['title'];
            }
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));
    }
}