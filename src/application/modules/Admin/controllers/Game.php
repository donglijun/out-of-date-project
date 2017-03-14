<?php
use Aws\S3\S3Client;

class GameController extends AdminController
{
    protected $authActions = array(
        'create'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $streamingDb;

    protected $s3;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
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
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('game/edit.phtml'));
    }

    public function createAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $this->getStreamingDb();

        if ($request->isPost()) {
            $logContent = array(
                'id'    => '',
            );

            $data = array(
                'name'          => $request->get('name', ''),
                'abbr'          => $request->get('abbr', ''),
                'created_on'    => $timestamp,
            );

            $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);

            $gameID   = $streamingGameModel->insert($data);
            $logContent['id']   = $gameID;
            $logContent['name'] = $data['name'];
            $logContent['abbr'] = $data['abbr'];

            Misc::adminLog(MySQL_AdminLogModel::OP_ADD_GAME, $logContent);

            if ($_FILES) {
                $data = array();

                $this->getS3();
                $config = Yaf_Registry::get('config')->toArray();

                if (isset($_FILES['icon_file']) && (exif_imagetype($_FILES['icon_file']['tmp_name']) !== false)) {
                    $finfo = $_FILES['icon_file'];

                    $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                    //@todo validate extension
                    $fname = AWS_S3_Bucket_Streaming_Game_IconModel::getName($gameID, $fext);

                    $return = $this->s3->putObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'Key'           => $fname,
                        'SourceFile'    => $finfo['tmp_name'],
                        'ContentType'   => $finfo['type'],
                        'ACL'           => 'public-read',
                    ));

                    $data['icon'] = $fname;
                }

                if (isset($_FILES['logo_file']) && (exif_imagetype($_FILES['logo_file']['tmp_name']) !== false)) {
                    $finfo = $_FILES['logo_file'];

                    $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                    //@todo validate extension
                    $fname = AWS_S3_Bucket_Streaming_Game_LogoModel::getName($gameID, $fext);

                    $return = $this->s3->putObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'Key'           => $fname,
                        'SourceFile'    => $finfo['tmp_name'],
                        'ContentType'   => $finfo['type'],
                        'ACL'           => 'public-read',
                    ));

                    $data['logo'] = $fname;
                }

                if ($data) {
                    $streamingGameModel->update($gameID, $data);
                }
            }

            $this->redirect('/admin/game/list');

            return false;
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $id = $request->get('id', 0);
        if ($id) {
            $this->getStreamingDb();

            $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);

            if ($request->isPost()) {
                $logContent = array(
                    'id'    => '',
                );

                $data = array(
                    'name'  => $request->get('name', ''),
                    'abbr'  => $request->get('abbr', ''),
                );

                $affectedCount = $streamingGameModel->update($id, $data);

                $logContent['id']   = $id;
                $logContent['name'] = $data['name'];
                $logContent['abbr'] = $data['abbr'];

                Misc::adminLog(MySQL_AdminLogModel::OP_MODIFY_GAME, $logContent);

                if ($_FILES) {
                    $data = array();

                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    if (isset($_FILES['icon_file']) && (exif_imagetype($_FILES['icon_file']['tmp_name']) !== false)) {
                        $finfo = $_FILES['icon_file'];

                        $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                        //@todo validate extension
                        $fname = AWS_S3_Bucket_Streaming_Game_IconModel::getName($id, $fext);

                        $return = $this->s3->putObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $fname,
                            'SourceFile'    => $finfo['tmp_name'],
                            'ContentType'   => $finfo['type'],
                            'ACL'           => 'public-read',
                        ));

                        $data['icon'] = $fname;
                    }

                    if (isset($_FILES['logo_file']) && (exif_imagetype($_FILES['logo_file']['tmp_name']) !== false)) {
                        $finfo = $_FILES['logo_file'];

                        $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                        //@todo validate extension
                        $fname = AWS_S3_Bucket_Streaming_Game_LogoModel::getName($id, $fext);

                        $return = $this->s3->putObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $fname,
                            'SourceFile'    => $finfo['tmp_name'],
                            'ContentType'   => $finfo['type'],
                            'ACL'           => 'public-read',
                        ));

                        $data['logo'] = $fname;
                    }

                    if ($data) {
                        $streamingGameModel->update($id, $data);
                    }
                }

                $this->redirect('/admin/game/list');

                return false;
            } else {
                $data = $streamingGameModel->getRow($id);
                $this->_view->assign('id', $id);
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

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $this->getStreamingDb();

            $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);

            $affectedCount = $streamingGameModel->delete($ids);

            $logContent = array(
                'ids'    => $ids,
            );

            Misc::adminLog(MySQL_AdminLogModel::OP_DELETE_GAME, $logContent);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/game/list');

        return false;
    }

    public function listAction()
    {
        $result = $where = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $this->getStreamingDb();

        $filter['page'] = '0page0';

        $where = $where ? implode(' AND ', $where) : '';

        $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);
        $result = $streamingGameModel->search('*', $where, 'id DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/game/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}