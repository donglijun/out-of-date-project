<?php
class MoneyController extends AdminController
{
    protected $authActions = array(
        'withdraw_orders'         => MySQL_AdminAccountModel::GROUP_ADMIN,
        'view_withdraw_order'     => MySQL_AdminAccountModel::GROUP_ADMIN,
        'complete_withdraw_order' => MySQL_AdminAccountModel::GROUP_ADMIN,
    );

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

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

    public function withdraw_ordersAction()
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
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);
        $result = $streamingWithdrawOrderModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/money/withdraw_orders?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('money/withdraw-orders.phtml'));

        return false;
    }

    public function view_withdraw_orderAction()
    {
        $data = array();
        $request = $this->getRequest();

        $id = $request->get('id', 0);

        if ($id) {
            $this->getStreamingDb();

            $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);
            $data = $streamingWithdrawOrderModel->getRow($id);
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->_view->render('money/view-withdraw-order.phtml'));

        return false;
    }

    public function complete_withdraw_orderAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code' => 500,
        );

        $userid = $this->session->admin['user'];

        $id = $request->get('id');
        $payMoney = (float) $request->get('pay_money');

        $this->getStreamingDb();

        $streamingWithdrawOrderModel = new MySQL_Streaming_WithdrawOrderModel($this->streamingDb);

        if (($orderInfo = $streamingWithdrawOrderModel->getRow($id)) && ($orderInfo['status'] == MySQL_Streaming_WithdrawOrderModel::STATUS_NEW)) {
            if ($payMoney > 0) {
                try {
                    $this->streamingDb->beginTransaction();

                    $streamingWithdrawOrderModel->update($id, array(
                        'status' => MySQL_Streaming_WithdrawOrderModel::STATUS_COMPLETED,
                        'pay_money' => $payMoney,
                        'processed_on' => $request->getServer('REQUEST_TIME'),
                        'processed_by' => $userid,
                    ));

                    $result['code'] = 200;
                    $result['message'] = 'ok';

                    $this->streamingDb->commit();
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    $result['message'] = 'Internal error';
                }
            } else {
                $result['code'] = 400;
                $result['message'] = 'Invalid pay money';
            }
        } else {
            $result['code'] = 403;
            $result['message'] = 'No privilege';
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/money/withdraw_orders');

        return false;
    }

//    public function cancel_withdraw_orderAction()
//    {
//        $request = $this->getRequest();
//        $result = array(
//            'code' => 500,
//        );
//
//        $id = $request->get('id');
//
//        $this->getStreamingDb();
//
//        $goldWithdrawOrderModel = new MySQL_Gold_WithdrawOrderModel($this->streamingDb);
//
//        if (($orderInfo = $goldWithdrawOrderModel->getRow($id)) && ($orderInfo['status'] == MySQL_Gold_WithdrawOrderModel::STATUS_NEW)) {
//            try {
//                $this->streamingDb->beginTransaction();
//
//                $goldWithdrawOrderModel = new MySQL_Gold_WithdrawOrderModel($this->streamingDb);
//                $goldWithdrawOrderModel->update($id, array(
//                    'status' => MySQL_Gold_WithdrawOrderModel::STATUS_CANCELED,
//                    'processed_on' => $request->getServer('REQUEST_TIME'),
//                ));
//
//                $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
//                $goldAccountModel->withdraw($orderInfo['user'], $orderInfo['money'] * -1, $orderInfo['golds'] * -1);
//
//                $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);
//                $goldLogModel->insert(array(
//                    'user'     => $orderInfo['user'],
//                    'number'   => $orderInfo['golds'],
//                    'type'     => MySQL_Gold_LogModel::LOG_TYPE_CANCEL_WITHDRAW,
//                    'dealt_on' => $request->getServer('REQUEST_TIME'),
//                ));
//
//                $this->streamingDb->commit();
//
//                $result['code'] = 200;
//                $result['message'] = 'ok';
//            } catch (Exception $e) {
//                $this->streamingDb->rollBack();
//
//                Misc::log($e->getMessage(), Zend_Log::ERR);
//            }
//        } else {
//            $result['code'] = 403;
//            $result['message'] = 'No privilege';
//        }
//
//        if ($request->isXmlHttpRequest()) {
//            header('Content-Type: application/json; charset=utf-8');
//
//            echo json_encode($result);
//
//            return false;
//        }
//
//        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/gold/withdraw_orders');
//
//        return false;
//    }

}