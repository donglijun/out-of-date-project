<?php
class PointController extends ApiController
{
    protected $authActions = array(
        'balance',
        'exchange',
        'exchange_history',
        'exchange_info',
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

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);

        $result['data'] = $pointAccountModel->number($userid);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function exchangeAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $cardType = (int) $request->get('card_type');

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $data = $row = array();
        $this->getStreamingDb();

        $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);

        $cardTypeInfo = $cardTypeModel->getRow($cardType);

        if ($cardTypeInfo) {
            $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
            $userPoints = $pointAccountModel->number($userid);

            if ($cardTypeInfo['price'] <= $userPoints) {
                try {
                    $this->streamingDb->beginTransaction();

                    // Freeze points
                    $pointAccountModel->incr($userid, $cardTypeInfo['price'] * -1);

                    $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                    $pointLogModel->insert(array(
                        'user'     => $userid,
                        'number'   => $cardTypeInfo['price'] * -1,
                        'type'     => MySQL_Point_LogModel::LOG_TYPE_EXCHANGE_CARD,
                        'dealt_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    // Create request
                    $cardRequestModel = new MySQL_Card_RequestModel($this->streamingDb);
                    $requestID = $cardRequestModel->insert(array(
                        'user'       => $userid,
                        'type'       => $cardType,
                        'title'      => $cardTypeInfo['title'],
                        'price'      => $cardTypeInfo['price'],
                        'created_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    $this->streamingDb->commit();

                    $result['code'] = 200;
                    $result['data'] = $requestID;

                    // Send job
                    $gearmanClient = Daemon::getGearmanClient();
                    $gearmanClient->doBackground('exchange-card', (string) $requestID);

                    if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                        Misc::log(sprintf("gearman job (exchange-card) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                    }
                } catch (Exception $e) {
                    $this->streamingDb->rollBack();

                    Misc::log($e->getMessage(), Zend_Log::ERR);
                }
            } else {
                $result['code'] = 403;
                $result['error'][] = array(
                    'message' => 'Lack of balance',
                );
            }
        } else {
            $result['code'] = 404;
            $result['error'][] = array(
                'message' => 'Invalid card type',
            );
        }

        $this->callback($result);

        return false;
    }

    public function exchange_historyAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $where = array();

        $where[] = '`user`=' . (int) $userid;

        $where = $where ? implode(' AND ', $where) : '';

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $cardRequestModel = new MySQL_Card_RequestModel($this->streamingDb);
        $result = $cardRequestModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function exchange_infoAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $cardRequestModel = new MySQL_Card_RequestModel($this->streamingDb);

        if (($exchangeID = $request->get('exchange_id', 0)) && ($row = $cardRequestModel->getRow($exchangeID))) {
            if ($row['user'] != $userid) {
                $result['code'] = 403;
            } else {
                $result['data'] = $row;

                $result['code'] = 200;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}