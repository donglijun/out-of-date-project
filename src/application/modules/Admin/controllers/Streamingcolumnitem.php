<?php
use Aws\S3\S3Client;

class StreamingcolumnitemController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'create'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'update'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'delete'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'top'       => MySQL_AdminAccountModel::GROUP_DIRECTOR,
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
            'action' => $action,
            'data' => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingcolumnitem/edit.phtml'));
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $links = array();

        $this->getStreamingDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $column = $request->get('column');
        if ($column) {
            $where[] = '`column`=' . (int) $column;
            $filter['column'] = $column;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
        $result = $streamingColumnItemModel->search('*', $where, '`display_order` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingcolumnitem/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);
        $result['columns'] = $streamingColumnModel->getAllRows();

        $this->getView()->assign($result);
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
            $affectedCount = $streamingColumnItemModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingcolumnitem/list');

        return false;
    }

    public function topAction()
    {
        $request = $this->getRequest();

        $this->getStreamingDb();

        if ($id = $request->get('id', 0)) {
            $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
            $streamingColumnItemModel->top($id);

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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingcolumnitem/list');

        return false;
    }

    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);
        $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);

        if ($request->isPost()) {
            $data = array(
                'display_order' => $request->get('display_order') ?: $request->getServer('REQUEST_TIME'),
                'created_on'    => $request->getServer('REQUEST_TIME'),
            );

            if ($column = $request->get('column')) {
                $data['column'] = $column;
            }
            if ($mediaType = $request->get('media_type')) {
                $data['media_type'] = $mediaType;
            }
            if ($source = $request->get('source')) {
                $data['source'] = $source;
            }
            if ($title = $request->get('title')) {
                $data['title'] = $title;
            }
            if ($liveScheduleTime = $request->get('live_schedule_time')) {
                $data['live_schedule_time'] = $liveScheduleTime;
            }

            $id = $streamingColumnItemModel->insert($data);

            if ($_FILES && ($_FILES['small_image_file']['name'] || $_FILES['large_image_file']['name'])) {
                $data = array();

                if (isset($_FILES['small_image_file']) && (exif_imagetype($_FILES['small_image_file']['tmp_name']) !== false)) {
                    $finfo = $_FILES['small_image_file'];

                    $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                    $fname = AWS_S3_Bucket_Streaming_ColumnItemImageModel::getSmallName($id, $fext);

                    $return = $this->s3->putObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'Key'           => $fname,
                        'SourceFile'    => $finfo['tmp_name'],
                        'ContentType'   => $finfo['type'],
                        'ACL'           => 'public-read',
                    ));

                    $data['small_image'] = '//s3.nikksy.com/' . $fname;
                }

                if (isset($_FILES['large_image_file']) && (exif_imagetype($_FILES['large_image_file']['tmp_name']) !== false)) {
                    $finfo = $_FILES['large_image_file'];

                    $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                    $fname = AWS_S3_Bucket_Streaming_ColumnItemImageModel::getLargeName($id, $fext);

                    $return = $this->s3->putObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'Key'           => $fname,
                        'SourceFile'    => $finfo['tmp_name'],
                        'ContentType'   => $finfo['type'],
                        'ACL'           => 'public-read',
                    ));

                    $data['large_image'] = '//s3.nikksy.com/' . $fname;
                }

                if ($data) {
                    $streamingColumnItemModel->update($id, $data);
                }
            } else if ($mediaType == MySQL_Streaming_ColumnItemModel::MEDIA_TYPE_STREAMING) {
                $data = array();
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

                if ($channelInfo = $streamingChannelModel->getRow($source, array('small_show_image', 'large_show_image'))) {
                    $data['small_image'] = '//s3.nikksy.com/' . $channelInfo['small_show_image'];
                    $data['large_image'] = '//s3.nikksy.com/' . $channelInfo['large_show_image'];
                }

                if ($data) {
                    $streamingColumnItemModel->update($id, $data);
                }
            } else if ($mediaType == MySQL_Streaming_ColumnItemModel::MEDIA_TYPE_YOUTUBE) {
                $data = array();

                $data['small_image'] = Mkjogo_Video_Link_Youtube::getFromVideoUrl($source);

                if ($data) {
                    $streamingColumnItemModel->update($id, $data);
                }
            }

            $this->redirect('/admin/streamingcolumnitem/list');

            return false;
        }

        $this->_view->assign(array(
            'types'     => $streamingColumnItemModel->getMediaTypes(),
            'columns'   => $streamingColumnModel->getColumnMap(),
        ));

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $this->getStreamingDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        if ($id = $request->get('id', 0)) {
            $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);

            if ($request->isPost()) {
                if ($column = $request->get('column')) {
                    $data['column'] = $column;
                }
                if ($mediaType = $request->get('media_type')) {
                    $data['media_type'] = $mediaType;
                }
                if ($source = $request->get('source')) {
                    $data['source'] = $source;
                }
                if ($title = $request->get('title')) {
                    $data['title'] = $title;
                }
                if ($displayOrder = $request->get('display_order')) {
                    $data['display_order'] = $displayOrder;
                }
                if ($liveScheduleTime = $request->get('live_schedule_time')) {
                    $data['live_schedule_time'] = $liveScheduleTime;
                }

                if ($_FILES && ($_FILES['small_image_file']['name'] || $_FILES['large_image_file']['name'])) {
                    if (isset($_FILES['small_image_file']) && (exif_imagetype($_FILES['small_image_file']['tmp_name']) !== false)) {
                        $finfo = $_FILES['small_image_file'];

                        $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                        $fname = AWS_S3_Bucket_Streaming_ColumnItemImageModel::getSmallName($id, $fext);

                        $return = $this->s3->putObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $fname,
                            'SourceFile'    => $finfo['tmp_name'],
                            'ContentType'   => $finfo['type'],
                            'ACL'           => 'public-read',
                        ));

                        $data['small_image'] = '//s3.nikksy.com/' . $fname;
                    }

                    if (isset($_FILES['large_image_file']) && (exif_imagetype($_FILES['large_image_file']['tmp_name']) !== false)) {
                        $finfo = $_FILES['large_image_file'];

                        $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                        $fname = AWS_S3_Bucket_Streaming_ColumnItemImageModel::getLargeName($id, $fext);

                        $return = $this->s3->putObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $fname,
                            'SourceFile'    => $finfo['tmp_name'],
                            'ContentType'   => $finfo['type'],
                            'ACL'           => 'public-read',
                        ));

                        $data['large_image'] = '//s3.nikksy.com/' . $fname;
                    }
                } else if ($mediaType == MySQL_Streaming_ColumnItemModel::MEDIA_TYPE_YOUTUBE) {
                    $data = array();

                    $data['small_image'] = Mkjogo_Video_Link_Youtube::getFromVideoUrl($source);

                    if ($data) {
                        $streamingColumnItemModel->update($id, $data);
                    }
                }

                $affectedCount = $streamingColumnItemModel->update($id, $data);

                $this->redirect('/admin/streamingcolumnitem/list');

                return false;
            } else {
                $data = $streamingColumnItemModel->getRow($id, $streamingColumnItemModel->getFields());
                $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->streamingDb);

                $this->_view->assign(array(
                    'id'        => $id,
                    'types'     => $streamingColumnItemModel->getMediaTypes(),
                    'columns'   => $streamingColumnModel->getColumnMap(),
                ));
            }

            $this->gotoEdit($request->getActionName(), $data);
        }

        return false;
    }
}