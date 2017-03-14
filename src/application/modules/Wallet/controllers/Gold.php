<?php
class GoldController extends ApiController
{
    protected $authActions = array(
        'balance',
//        'withdraw',
        'android_prepare_order',
        'android_validate_order',
    );

    protected $streamingDb;

    protected $redisStreaming;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    public function balanceAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

//        $config = Yaf_Registry::get('config')->toArray();
//        $rate = $config['wallet']['gold']['withdraw']['rate'];

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);

        $result['data'] = $goldAccountModel->balance($userid);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

//    public function withdrawAction()
//    {
//        $request = $this->getRequest();
//        $timestamp = time();
//
//        $result = array(
//            'code'  => 500,
//        );
//
//        $config = Yaf_Registry::get('config')->toArray();
//        $min = $config['wallet']['gold']['withdraw']['min'];
////        $rate = $config['wallet']['gold']['withdraw']['rate'];
//
//        $currentUser = Yaf_Registry::get('user');
//        $userid = $currentUser['id'];
//
//        $this->getStreamingDb();
//
////        $money = (int) $request->get('money', 0);
//
//        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
//
//        $goldAccountInfo = $goldAccountModel->getRow($userid);
////        $maxWithdrawNum = floor($goldAccountInfo['remained_earn_num'] / $rate);
//        $money = $goldAccountInfo['remained_earn_money'];
//        $golds = $goldAccountInfo['remained_earn_num'];
//
//        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
//        $channelInfo = $streamingChannelModel->getRow($userid, array('paypal'));
//
//        if ($money < $min) {
//            $result['code'] = 421;
//            $result['error'][] = array(
//                'message' => 'Too less money',
//            );
//        } else if (!$channelInfo['paypal']) {
//            $result['code'] = 423;
//            $result['error'][] = array(
//                'message' => 'No Paypal setup',
//            );
//        } else {
//            try {
//                $this->streamingDb->beginTransaction();
//
//                $goldAccountModel->withdraw($userid, $money, $golds);
//
//                $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);
//                $goldLogModel->insert(array(
//                    'user'     => $userid,
//                    'number'   => $golds * -1,
//                    'type'     => MySQL_Gold_LogModel::LOG_TYPE_WITHDRAW,
//                    'dealt_on' => $timestamp,
//                ));
//
//                $goldWithdrawOrderModel = new MySQL_Gold_WithdrawOrderModel($this->streamingDb);
//                $goldWithdrawOrderModel->insert(array(
//                    'user'       => $userid,
//                    'golds'      => $golds,
//                    'money'      => $money,
//                    'created_on' => $timestamp,
//                    'paypal'     => $channelInfo['paypal'],
//                ));
//
//                $this->streamingDb->commit();
//
//                $result['code'] = 200;
//            } catch (Exception $e) {
//                $this->streamingDb->rollBack();
//
//                Misc::log($e->getMessage(), Zend_Log::ERR);
//            }
//        }
//
//        $this->callback($result);
//
//        return false;
//    }

    public function packagesAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $client = (int) $request->get('client', 0);

        $this->getStreamingDb();

        $goldPackageModel = new MySQL_Gold_PackageModel($this->streamingDb);
        $result['data'] = $goldPackageModel->getRowsByClient($client, array(
            'id',
            'title',
            'money',
            'money_unit',
            'golds',
            'bonus',
        ));

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function android_prepare_orderAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

//        $config = Yaf_Registry::get('config')->toArray();
//        $rate = $config['wallet']['gold']['withdraw']['rate'];

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $goldPackageModel = new MySQL_Gold_PackageModel($this->streamingDb);

        if (($package = $request->get('package', 0)) && ($packageInfo = $goldPackageModel->getRow($package))) {
            $goldRechargeOrderModel = new MySQL_Gold_RechargeOrderModel($this->streamingDb);

            $orderId = $goldRechargeOrderModel->insert(array(
                'user'       => $userid,
                'product'    => $packageInfo['title'],
                'golds'      => $packageInfo['golds'] + $packageInfo['bonus'],
                'cost'       => $packageInfo['money'],
                'cost_unit'  => $packageInfo['money_unit'],
                'created_on' => $request->getServer('REQUEST_TIME'),
            ));

            $result['data'] = $orderId;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function android_validate_orderAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $timestamp = time();

        $config = Yaf_Registry::get('config')->toArray();
        $clientId = $config['google']['client_id'];
        $clientSecret = $config['google']['client_secret'];
        $refreshToken = $config['google']['refresh_token'];
        $packageName = $config['google']['package_name'];

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $goldRechargeOrderModel = new MySQL_Gold_RechargeOrderModel($this->streamingDb);

        if (($orderId = $request->get('order_id')) && ($orderInfo = $goldRechargeOrderModel->getRow($orderId))) {
            //Ignore processed order
            if ($orderInfo['user'] != $userid) {
                $result['code'] = 403;
                $result['message'] = 'Not granted';
            } else if ($orderInfo['is_processed'] || $orderInfo['is_bad']) {
                $result['code'] = 403;
                $result['message'] = 'Order was processed and readonly';
            } else {
                $tokens = $request->get('tokens');

                try {
                    // Update tokens
                    $goldRechargeOrderModel->update($orderId, array(
                        'foreign_id' => $tokens,
                    ));

                    $this->streamingDb->beginTransaction();

                    // Build Google client
                    $googleClient = new Google_Client();
//                    $googleClient->setAccessType('offline');
                    $googleClient->setClientId($clientId);
                    $googleClient->setClientSecret($clientSecret);
                    $googleClient->addScope(Google_Service_AndroidPublisher::ANDROIDPUBLISHER);

//                    $googleClient->setClassConfig('Google_IO_Curl', 'options', array(
//                        CURLOPT_PROXY => 'socks5://192.168.1.134:5566',
//                    ));

                    $this->getRedisStreaming();
                    $redisOpenGoogleAccessTokenModel = new Redis_Open_Google_AccessTokenModel($this->redisStreaming);
                    if ($accessToken = $redisOpenGoogleAccessTokenModel->get()) {
                        $googleClient->setAccessToken($accessToken);
                    } else {
                        $googleClient->refreshToken($refreshToken);

                        if ($accessToken = $googleClient->getAccessToken()) {
                            $redisOpenGoogleAccessTokenModel->set($accessToken);
                        }
                    }

                    $gsAndroidPublisher = new Google_Service_AndroidPublisher($googleClient);
                    $resource = $gsAndroidPublisher->purchases_products->get($packageName, $orderInfo['product'], $tokens);

                    if ($resource->getPurchaseState() === 0) {
                        // Update order status
                        $goldRechargeOrderModel->update($orderId, array(
                            'foreign_timestamp' => round($resource->getPurchaseTimeMillis() / 1000),
                            'is_processed' => MySQL_Gold_RechargeOrderModel::PROCESSED_STATUS_PURCHASED,
                            'processed_on' => $timestamp,
                        ));

                        // Add log
                        $goldLogModel = new MySQL_Gold_LogModel($this->streamingDb);
                        $goldLogModel->insert(array(
                            'user' => $orderInfo['user'],
                            'number' => $orderInfo['golds'],
                            'type' => MySQL_Gold_LogModel::LOG_TYPE_RECHARGE,
                            'dealt_on' => $timestamp,
                        ));

                        // Update account
                        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
                        // Enable recharge bonus
//                        $goldAccountInfo = $goldAccountModel->getRow($orderInfo['user'], array('recharge_times'));
//                        if ($goldAccountInfo['recharge_times'] == 0) {
//                            $bonus = $orderInfo['golds'];
//
//                            $goldLogModel->insert(array(
//                                'user' => $orderInfo['user'],
//                                'number' => $bonus,
//                                'type' => MySQL_Gold_LogModel::LOG_TYPE_BONUS,
//                                'dealt_on' => $timestamp,
//                            ));
//
//                            $orderInfo['golds'] += $bonus;
//                        }

                        $goldAccountModel->recharge($orderInfo['user'], $orderInfo['golds']);

                        $this->streamingDb->commit();

                        $result['code'] = 200;
                    } else if ($resource->getPurchaseState() === 1) {
                        // Update order status
                        $goldRechargeOrderModel->update($orderId, array(
                            'foreign_timestamp' => round($resource->getPurchaseTimeMillis() / 1000),
                            'is_processed' => MySQL_Gold_RechargeOrderModel::PROCESSED_STATUS_CANCELLED,
                            'processed_on' => $timestamp,
                        ));

                        $this->streamingDb->commit();

                        $result['code'] = 207;
                        $result['message'] = 'Cancelled';
                    }
                } catch (Exception $e) {
                    Misc::log($e->getMessage(), Zend_Log::ERR);

                    $this->streamingDb->rollBack();
                }
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}