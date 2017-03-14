<?php
use Aws\S3\S3Client;

class VideolinktagController extends AdminController
{
    protected $authActions = array(
        'list' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
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

    public function listAction()
    {
        $request = $this->getRequest();

        $this->getRedisVideo();
        $this->getVideoDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page ?: 1;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = $limit * ($page - 1);

        $mkjogoVideoLink = new Mkjogo_Video_Link($this->videoDb, $this->redisVideo, $this->s3);

        $filter['page'] = '0page0';
        if ($tag = $request->get('tag')) {
            $filter['tag'] = $tag;

            $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
            $redisVideoLinkScoreNewListByTagModel = new Redis_Video_Link_Score_New_ListByTagModel($this->redisVideo);

            $ids = $redisVideoLinkScoreNewListByTagModel->range($tag, $offset, $offset + $limit - 1);
            $result['data'] = $videoLinkModel->getRows($ids);
            $result['page'] = $page;
            $result['total_found'] = $redisVideoLinkScoreNewListByTagModel->len($tag);
            $result['page_count'] = ceil($result['total_found'] / $limit);
            $result['code'] = 200;

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
            $result['pageUrlPattern'] = '/admin/videolinktag/list?' . http_build_query($filter);

            $paginator = Zend_Paginator::factory($result['total_found']);
            $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($limit)
                ->setPageRange(10);
            $result['paginator'] = $paginator;
        } else {
            $result['code'] = 404;
        }

        $this->getView()->assign($result);
    }
}