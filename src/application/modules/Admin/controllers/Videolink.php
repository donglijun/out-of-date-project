<?php
use Aws\S3\S3Client;

class VideolinkController extends AdminController
{
    protected $authActions = array(
        'delete'        => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'list'          => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'        => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
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
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('videolink/edit.phtml'));
    }

    public function deleteAction()
    {
        $request = $this->getRequest();

        $this->getVideoDb();
        $this->getRedisVideo();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
            $rows = $videoLinkModel->getRows($ids, array('id', 'author', 'tags'));

            // Delete links
            $affectedCount = $videoLinkModel->delete($ids);

            // Delete comments
            $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);
            $videoCommentModel->deleteByLinks($ids);

            // Delete bullets
            $videoBulletModel = new MySQL_Video_BulletModel($this->videoDb);
            $videoBulletModel->deleteByLinks($ids);

            //@todo delete favourite

            // Delete share history
            $videoLinkShareHistoryModel = new MySQL_Video_LinkShareHistoryModel($this->videoDb);
            $videoLinkShareHistoryModel->deleteByLinks($ids);

            $redisVideoLinkShareHistoryLinkModel = new Redis_Video_Link_ShareHistory_LinkModel($this->redisVideo);
            $redisVideoLinkShareHistoryLinkModel->del($ids);

            $redisVideoLinkShareHistoryUserModel = new Redis_Video_Link_ShareHistory_UserModel($this->redisVideo);
            foreach ($rows as $row) {
                $redisVideoLinkShareHistoryUserModel->rem($row['author'], $row['id']);
            }

            // Delete vote history
            $videoLinkVoteHistoryModel = new MySQL_Video_LinkVoteHistoryModel($this->videoDb);
            $videoLinkVoteHistoryModel->deleteByLinks($ids);

            $redisVideoLinkVoteHistoryModel = new Redis_Video_Link_Vote_HistoryModel($this->redisVideo);
            $redisVideoLinkVoteHistoryModel->del($ids);

            // Remove from score list
            $redisVideoLinkScoreNewListModel = new Redis_Video_Link_Score_New_ListModel($this->redisVideo);
            $redisVideoLinkScoreNewListModel->rem($ids);

            $redisVideoLinkScoreHotListModel = new Redis_Video_Link_Score_Hot_ListModel($this->redisVideo);
            $redisVideoLinkScoreHotListModel->rem($ids);

            // Remove from list by tag
            $redisVideoLinkScoreNewListByTagModel = new Redis_Video_Link_Score_New_ListByTagModel($this->redisVideo);
            foreach ($rows as $row) {
                if ($tags = json_decode($row['tags'], true)) {
                    foreach ($tags as $tag) {
                        $redisVideoLinkScoreNewListByTagModel->rem($tag, $row['id']);
                    }
                }
            }
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/videolink/list');

        return false;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

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
        if ($keyword = $request->get('keyword')) {
            $where[] = sprintf("`title` LIKE %s", $this->videoDb->quote('%' . $keyword . '%'));
            $filter['keyword'] = $keyword;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $result = $videoLinkModel->search('*', $where, '`id` DESC', $offset, $limit);

        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        foreach ($result['data'] as $key => $val) {
            if (isset($val['custom_image']) && $val['custom_image']) {
                $fname = AWS_S3_Bucket_VideoLinkCustomImageModel::getName($val['id'], $val['custom_image']);
                $result['data'][$key]['custom_image'] = $this->s3->getObjectUrl($config['aws']['s3']['bucket']['video'], $fname, null, array(
                    'Scheme'    => 'http',
                ));
            }

            if (isset($val['tags']) && $val['tags']) {
                $result['data'][$key]['tags'] = $mkjogoVideoLink->getDetailTags(json_decode($val['tags'], true));
            }
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/videolink/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function updateAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $id = $request->get('id', 0);
        if ($id) {
            $videoLinkModel = new MySQL_Video_LinkModel($this->getVideoDb());

            $this->getS3();
            $config = Yaf_Registry::get('config')->toArray();

            if ($request->isPost()) {
                $data = array(
                    'title'  => $request->get('title', ''),
                );

                if ($_FILES && isset($_FILES['custom_image_file']) && $_FILES['custom_image_file']['tmp_name']) {
                    $finfo = $_FILES['custom_image_file'];

                    $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                    //@todo validate extension
                    $fname = AWS_S3_Bucket_VideoLinkCustomImageModel::getName($id, $fext);

                    $return = $this->s3->putObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['video'],
                        'Key'           => $fname,
                        'SourceFile'    => $finfo['tmp_name'],
                        'ContentType'   => $finfo['type'],
                        'ACL'           => 'public-read',
                    ));

                    $data['custom_image'] = $fext;
                }

                $affectedCount = $videoLinkModel->update($id, $data);

                $this->redirect('/admin/videolink/list');

                return false;
            } else {
                $data = $videoLinkModel->getRow($id);

                if (isset($data['custom_image']) && $data['custom_image']) {
                    $fname = AWS_S3_Bucket_VideoLinkCustomImageModel::getName($data['id'], $data['custom_image']);
                    $data['custom_image'] = $this->s3->getObjectUrl($config['aws']['s3']['bucket']['video'], $fname, null, array(
                        'Scheme'    => 'http',
                    ));
                }

                $this->_view->assign('id', $id);
            }

            $this->gotoEdit($request->getActionName(), $data);
        }

        return false;
    }
}