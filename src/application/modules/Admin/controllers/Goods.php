<?php
class GoodsController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ADMIN,
//        'delete'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'log'       => MySQL_AdminAccountModel::GROUP_ADMIN,
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

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('goods/edit.phtml'));
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

        $goodsGoodsModel = new MySQL_Goods_GoodsModel($this->streamingDb);
        $result = $goodsGoodsModel->search('*', null, null, $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/goods/list?' . http_build_query($filter);

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
                'title'          => $request->get('title', ''),
                'price'          => $request->get('price', 0),
                'description'    => $request->get('description', ''),
                'slogan'         => $request->get('slogan', ''),
                'effect_trigger' => $request->get('effect_trigger', 0),
                'rarity'         => $request->get('rarity', 0),
                'is_active'      => $request->get('is_active') ? 1 : 0,
                'created_on'     => time(),
            );

            $goodsGoodsModel = new MySQL_Goods_GoodsModel($this->streamingDb);
            $goodsGoodsModel->insert($data);

            $this->redirect('/admin/goods/list');

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
            $goodsGoodsModel = new MySQL_Goods_GoodsModel($this->streamingDb);

            if ($request->isPost()) {
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }

                // Cannot modify price
//                if ($price = $request->get('price')) {
//                    $data['price'] = $price;
//                }

                $data['description'] = $request->get('description', '');
                $data['slogan'] = $request->get('slogan', '');
                $data['effect_trigger'] = $request->get('effect_trigger', 0);
                $data['rarity'] = $request->get('rarity', 0);
                $data['is_active'] = $request->get('is_active') ? 1 : 0;

                if ($data) {
                    $affectedCount = $goodsGoodsModel->update($id, $data);
                }

                $this->redirect('/admin/goods/list');

                return false;
            } else {
                $data = $goodsGoodsModel->getRow($id);

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

//    public function deleteAction()
//    {
//        $request = $this->getRequest();
//        $this->getStreamingDb();
//
//        $ids = Misc::parseIds($request->get('ids'));
//        if ($ids) {
//            $goodsGoodsModel = new MySQL_Goods_GoodsModel($this->streamingDb);
//            $affectedCount = $goodsGoodsModel->delete($ids);
//        }
//
//        if ($request->isXmlHttpRequest()) {
//            header('Content-Type: application/json; charset=utf-8');
//
//            $result = array(
//                'code'  => 200,
//                'data'  => array(
//                    'affected'  => $affectedCount,
//                ),
//            );
//
//            echo json_encode($result);
//
//            return false;
//        }
//
//        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/goods/list');
//
//        return false;
//    }

    public function logAction()
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
        if ($sender = $request->get('sender')) {
            $where[] = "`sender`=" . (int) $sender;
            $filter['sender'] = $sender;
        }
        if ($receiver = $request->get('receiver')) {
            $where[] = "`receiver`=" . (int) $receiver;
            $filter['receiver'] = $receiver;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $goodsLogModel = new MySQL_Goods_LogModel($this->streamingDb);
        $result = $goodsLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/goods/log?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}