<?php
class GoldController extends ServiceController
{
    protected $authActions = array();

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

    public function lz_pay_goodAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $data = array();
        $timestamp = time();

        $data['foreign_id'] = $request->get('order_id');
        $data['user'] = $request->get('user');
        $data['golds'] = $request->get('golds');
        $data['cost'] = $request->get('cost');
        $data['cost_unit'] = $request->get('cost_unit');
        $data['foreign_timestamp'] = $request->get('order_time');
        $data['created_on'] = $timestamp;
        $data['processed_on'] = $timestamp;
        $data['is_processed'] = 1;
        $token = $request->get('token');
        $secret = 'vOTTerKUq3WpFd9_omcv_DEYhrUm9S';

        $this->getPassportDb();
        $this->getStreamingDb();

        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        $goldRechargeOrderModel = new MySQL_Gold_RechargeOrderModel($this->streamingDb);

        if (!isset($_REQUEST['order_id']) || !isset($_REQUEST['user']) || !isset($_REQUEST['golds']) || !isset($_REQUEST['cost']) || !isset($_REQUEST['cost_unit']) || !isset($_REQUEST['order_time']) || !isset($_REQUEST['token'])) {
            $result['code'] = 8;
            $result['msg'] = '参数个数不对';
        } else if ($token != md5($data['foreign_id'] . $data['user'] . $data['golds'] . $data['cost'] . $data['cost_unit'] . $data['foreign_timestamp'] . $secret)) {
            $result['code'] = 7;
            $result['msg'] = '签名错误';
        } else if ($data['golds'] < 1) {
            $result['code'] = 3;
            $result['msg'] = '虚拟商品数量小于 1';
        } else if (!$userAccountModel->exists($data['user'])) {
            $result['code'] = 2;
            $result['msg'] = '用户不存在';
        } else if ($goldRechargeOrderModel->findForeign($data['foreign_id'])) {
            $result['code'] = 4;
            $result['msg'] = '订单号已经存在';
        } else {
            try {
                $this->streamingDb->beginTransaction();

                // Make order
                $goldRechargeOrderModel->insert($data);

                // Add log
                $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);
                $goldLogModel->insert(array(
                    'user'     => $data['user'],
                    'number'   => $data['golds'],
                    'type'     => MySQL_Gold_LogModel::LOG_TYPE_RECHARGE,
                    'dealt_on' => $timestamp,
                ));

                // Update account
                $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
                // Enable recharge bonus
                $goldAccountInfo = $goldAccountModel->getRow($data['user'], array('recharge_times'));
//                if ($goldAccountInfo['recharge_times'] == 0) {
//                    $bonus = $data['golds'];
//
//                    $goldLogModel->insert(array(
//                        'user'     => $data['user'],
//                        'number'   => $bonus,
//                        'type'     => MySQL_Gold_LogModel::LOG_TYPE_BONUS,
//                        'dealt_on' => $timestamp,
//                    ));
//
//                    $data['golds'] += $bonus;
//                }

                $goldAccountModel->recharge($data['user'], $data['golds']);

                $this->streamingDb->commit();

                $result['code'] = 0;
                $result['msg'] = '充值成功';
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);

                $this->streamingDb->rollBack();

                $result['code'] = 1;
                $result['msg'] = '未知原因';
            }
        }

        echo json_encode($result);

        return false;
    }

    public function lz_pay_badAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $foreignId = $request->get('order_id');
        $token = $request->get('token');
        $secret = 'fIWrnvtZOfkO2haGaOpKkRAiKd9l1N';

        if ($token == md5($foreignId . $secret)) {
            $this->getStreamingDb();

            $goldRechargeOrderModel = new MySQL_Gold_RechargeOrderModel($this->streamingDb);
            if ($goldRechargeOrderModel->bad($foreignId)) {
                $result['code'] = 200;
            }
        } else {
            $result['code'] = 403;
        }

        echo json_encode($result);

        return false;
    }
}