<?php
use Aws\S3\S3Client;

class VideolinkcolumnController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'publish'   => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'top'       => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $videoDb;

    protected $redisVideo;

    protected $s3;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function getRedisVideo()
    {
        if (empty($this->redisVideo)) {
            $this->redisVideo = Daemon::getRedis('redis-video', 'redis-video');
        }

        return $this->redisVideo;
    }

    protected function getS3()
    {
        if (empty($this->s3)) {
            $config = Yaf_Registry::get('config')->toArray();

            $this->s3 = S3Client::factory(array(
                'key'       => $config['aws']['s3']['key'],
                'secret'    => $config['aws']['s3']['secret'],
                'region'    => $config['aws']['s3']['region'],
            ));
        }

        return $this->s3;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action' => $action,
            'data' => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('videolinkcolumn/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $links = array();

        $this->getRedisVideo();
        $this->getVideoDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $mkjogoVideoLink = new Mkjogo_Video_Link($this->videoDb, $this->redisVideo, $this->s3);

        $filter['page'] = '0page0';
        $column = $request->get('column');
        if ($column) {
            $where[] = '`column`=' . (int) $column;
            $filter['column'] = $column;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $videoLinkColumnModel = new MySQL_Video_LinkColumnModel($this->videoDb);
        $result = $videoLinkColumnModel->search('*', $where, '`display_order` DESC', $offset, $limit);

        foreach ($result['data'] as $val) {
            $links[] = $val['link'];
        }

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        foreach ($videoLinkModel->getRows($links) as $row) {
            $result['links'][$row['id']] = $row;
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/videolinkcolumn/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $videoColumnModel = new MySQL_Video_ColumnModel($this->videoDb);
        $result['columns'] = $videoColumnModel->getAllRows();

        $this->getView()->assign($result);
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getVideoDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $videoLinkColumnModel = new MySQL_Video_LinkColumnModel($this->videoDb);
            $affectedCount = $videoLinkColumnModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/videolinkcolumn/list');

        return false;
    }

    public function publishAction()
    {
        $request = $this->getRequest();

        $this->getVideoDb();

        if ($links = $request->get('links')) {
            $videoLinkColumnModel = new MySQL_Video_LinkColumnModel($this->videoDb);

            if ($columns = $request->get('columns')) {
                $links = Misc::parseIds($links);
                $timestamp = time();

                foreach ($links as $link) {
                    foreach ($columns as $column) {
//                        $videoLinkColumnModel->batchInsert(array(
//                            'link'          => $link,
//                            'column'        => $column,
//                            'display_order' => $timestamp,
//                            'created_on'    => $timestamp,
//                        ));
                        try {
                            $videoLinkColumnModel->insert(array(
                                'link'          => $link,
                                'column'        => $column,
                                'display_order' => $timestamp,
                                'created_on'    => $timestamp,
                            ));
                        } catch (Exception $e) {
                            ;
                        }
                    }
                }

//                $videoLinkColumnModel->completeBatchInsert();

                $this->getView()->assign(array(
                    'ok'    => true,
                ));
            } else {
                $videoColumnModel = new MySQL_Video_ColumnModel($this->videoDb);
                $this->getView()->assign(array(
                    'links'     => $links,
                    'columns'   => $videoColumnModel->getAllRows(),
                    'ok'        => false,
                ));
            }
        } else {
            throw new Exception('Need more parameters', 404);
        }
    }

    public function topAction()
    {
        $request = $this->getRequest();

        $this->getVideoDb();

        if ($id = $request->get('id', 0)) {
            $videoLinkColumnModel = new MySQL_Video_LinkColumnModel($this->videoDb);
            $videoLinkColumnModel->top($id);

            if ($request->isXmlHttpRequest()) {
                header('Content-Type: application/json; charset=utf-8');

                $result = array(
                    'code'  => 200,
                );

                echo json_encode($result);

                return false;
            }
        } else {
            throw new Exception('Not Found', 404);
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/videolinkcolumn/list');

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();
        $this->getVideoDb();

        $id = $request->get('id', 0);
        if ($id) {
            $videoLinkColumnModel = new MySQL_Video_LinkColumnModel($this->videoDb);

            if ($request->isPost()) {
                if ($displayOrder = $request->get('display_order')) {
                    $data['display_order'] = $displayOrder;
                    $affectedCount = $videoLinkColumnModel->update($id, $data);
                }

                $this->redirect('/admin/videolinkcolumn/list');

                return false;
            } else {
                $data = $videoLinkColumnModel->getRow($id, $videoLinkColumnModel->getFields());

                $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);

                $this->_view->assign(array(
                    'id'        => $id,
                    'linkInfo'  => $videoLinkModel->getRow($data['link']),
                ));
            }

            $this->gotoEdit($request->getActionName(), $data);
        }

        return false;
    }
}