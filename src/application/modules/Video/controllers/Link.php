<?php
use Aws\S3\S3Client;

class LinkController extends ApiController
{
    const LINKS_LIMIT_PER_DAY = 1500;

    protected $authActions = array(
        'submit',
        'vote',
        'my',
    );

    protected $videoDb;

    protected $passportDb;

    protected $redisVideo;

    protected $s3;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
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

    protected function saveShareHistory($link, $user, $timestamp)
    {
        $this->getVideoDb();
        $this->getRedisVideo();

        $videoLinkShareHistoryModel = new MySQL_Video_LinkShareHistoryModel($this->videoDb);
        if (!$videoLinkShareHistoryModel->exists($link, $user)) {
            $videoLinkShareHistoryModel->insert(array(
                'link'          => $link,
                'user'          => $user,
                'created_on'    => $timestamp,
            ));

            $redisVideoLinkShareHistoryLinkModel = new Redis_Video_Link_ShareHistory_LinkModel($this->redisVideo);
            $redisVideoLinkShareHistoryLinkModel->update($link, $user, $timestamp);

            $redisVideoLinkShareHistoryUserModel = new Redis_Video_Link_ShareHistory_UserModel($this->redisVideo);
            $redisVideoLinkShareHistoryUserModel->update($user, $link, $timestamp);
        }
    }

    public function getAvailableTags($ids)
    {
        $result = array();

        $videoTagModel = new MySQL_Video_TagModel($this->getVideoDb());
        $all = $videoTagModel->getIds();

        $result = array_intersect($ids, $all);
        sort($result);

        return $result;
    }

    public function checkLinksLimit($user)
    {
        $this->getRedisVideo();
        $redisVideoLinkShareHistoryUserModel = new Redis_Video_Link_ShareHistory_UserModel($this->redisVideo);

        $from = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $to = $from + 86400;

        $links = $redisVideoLinkShareHistoryUserModel->linksByScore($user, $from, $to);

        return count($links) < self::LINKS_LIMIT_PER_DAY;
    }

    public function submitAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost() && $this->checkLinksLimit($userid)) {
            if (($url = $request->get('url')) && ($url = Misc::normalizeUrl($url))) {
                $this->getVideoDb();
                $this->getRedisVideo();
                $this->getPassportDb();

                $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
                $videoLinkVoteHistoryModel = new MySQL_Video_LinkVoteHistoryModel($this->videoDb);
                $mkjogoUser = new Mkjogo_User($this->passportDb);
                $redisVideoLinkVoteQueueModel = new Redis_Video_Link_Vote_QueueModel($this->redisVideo);
                $redisVideoLinkScoreNewListByTagModel = new Redis_Video_Link_Score_New_ListByTagModel($this->redisVideo);

                $tags = $this->getAvailableTags(Misc::parseIds($request->get('tags', '')));
                sort($tags);

                if ($row = $videoLinkModel->getRowByUrl($url, array('id', 'tags', 'created_on'))) {
                    $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);

                    $data = array(
                        'link'          => $row['id'],
                        'body'          => $request->get('title', ''),
                        'author'        => $userid,
                        'author_name'   => $mkjogoUser->getDetail($userid),
                        'ip'            => Misc::getClientIp(),
                        'ups'           => 1,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );

                    $commentId = $videoCommentModel->insert($data);

                    $videoLinkModel->comment($row['id']);

//                    $videoLinkVoteHistoryModel->replace(array(
//                        'link'          => $row['id'],
//                        'user'          => $userid,
//                        'score'         => 1,
//                        'updated_on'    => time(),
//                    ));

                    // Vote up
                    $redisVideoLinkVoteQueueModel->push(array(
                        'link'  => $row['id'],
                        'user'  => $userid,
                        'score' => 1,
                    ));

                    // Save share history
                    $this->saveShareHistory($row['id'], $userid, $request->getServer('REQUEST_TIME'));

                    // Update tags
                    $linkTags = json_decode($row['tags'], true);
                    $videoLinkModel->update($row['id'], array(
                        'tags'  => json_encode(Misc::arrayUnite($tags, $linkTags)),
                    ));

                    // Update new list by tag
                    foreach ($tags as $tag) {
                        $redisVideoLinkScoreNewListByTagModel->update($tag, $row['id'], $row['created_on']);
                    }

                    $result['data'] = array(
                        'link'          => $row['id'],
                        'comment'       => $commentId,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );
                    $result['code'] = 200;
                } else {
                    $data = array(
                        'url'           => $url,
                        'title'         => $request->get('title', ''),
                        'thumbnail_url' => $request->get('thumbnail_url', ''),
                        'author'        => $userid,
                        'author_name'   => $mkjogoUser->getDetail($userid),
                        'tags'          => json_encode($tags),
//                    'lang'          => $this->session->lang,
//                        'ups'           => 1,
                        'ip'            => Misc::getClientIp(),
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );
                    $linkId = $videoLinkModel->insert($data);

                    // Get thumbnail if needed
                    if (!$data['thumbnail_url']) {
                        $workload = array(
                            'link'  => $linkId,
                            'url'   => $data['url'],
                        );
                        $gearmanClient = Daemon::getGearmanClient();
                        $gearmanClient->doBackground('video-link-crawl-twitch-thumb', json_encode($workload));
                    }

//                    $videoLinkVoteHistoryModel->replace(array(
//                        'link'          => $linkId,
//                        'user'          => $userid,
//                        'score'         => 1,
//                        'updated_on'    => time(),
//                    ));

                    // Vote up
                    $redisVideoLinkVoteQueueModel->push(array(
                        'link'  => $linkId,
                        'user'  => $userid,
                        'score' => 1,
                    ));

                    // Save share history
                    $this->saveShareHistory($linkId, $userid, $request->getServer('REQUEST_TIME'));

                    // Save new list
                    $redisVideoLinkScoreNewListModel = new Redis_Video_Link_Score_New_ListModel($this->redisVideo);
                    $redisVideoLinkScoreNewListModel->update($linkId, $request->getServer('REQUEST_TIME'));

                    // Save new list by tag
                    foreach ($tags as $tag) {
                        $redisVideoLinkScoreNewListByTagModel->update($tag, $linkId, $request->getServer('REQUEST_TIME'));
                    }

                    $result['data'] = array(
                        'link'          => $linkId,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );

                    $result['code'] = 200;
                }
            } else {
                $result['code'] = 400;
            }
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function voteAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
//            $mkuser = Yaf_Registry::get('mkuser');
//            $userid = $mkuser['userid'];
            $currentUser = Yaf_Registry::get('user');
            $userid = $currentUser['id'];

            $link = $request->get('link', 0);
            $dir = $request->get('dir', 1);
            $score = ($dir == 1) ? 1 : -1;

            if ($link) {
                $redisVideoLinkVoteQueueModel = new Redis_Video_Link_Vote_QueueModel($this->getRedisVideo());
                $redisVideoLinkVoteQueueModel->push(array(
                    'link'  => $link,
                    'user'  => $userid,
                    'score' => $score,
                ));

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
//            $up = ($dir == 1);
//
//            $videoLinkModel = new MySQL_Video_LinkModel($this->getVideoDb());
//
//            if (($link = $request->get('link')) && ($linkInfo = $videoLinkModel->getRow($link, array('id')))) {
//                $videoLinkModel->vote($link, $up);
//
//                $result['code'] = 200;
//            } else {
//                $result['code'] = 404;
//            }
        }

        $this->callback($result);

        return false;
    }

    public function viewAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = isset($mkuser['userid']) ? $mkuser['userid'] : 0;
        $currentUser = Yaf_Registry::get('user');
        $userid = isset($currentUser['id']) ? $currentUser['id'] : 0;

        if ($link = $request->get('link', 0)) {
            $mkjogoVideoLink = new Mkjogo_Video_Link($this->getVideoDb(), $this->getRedisVideo(), $this->getS3());
            $result['data'] = $mkjogoVideoLink->assembleData($link, $userid);

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function myAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getVideoDb();
        $this->getRedisVideo();

//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkShareHistoryUserModel = new Redis_Video_Link_ShareHistory_UserModel($this->redisVideo);

        $ids = $redisVideoLinkShareHistoryUserModel->revlinks($userid, $offset, $offset + $limit - 1);
        $result['data'] = $videoLinkModel->getRows($ids);
        $result['page'] = $page;
        $result['total_found'] = $redisVideoLinkShareHistoryUserModel->len($userid);
        $result['page_count'] = ceil($result['total_found'] / $limit);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function listnewAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getRedisVideo();
        $this->getVideoDb();
        $this->getS3();
        $config = Yaf_Registry::get('config')->toArray();

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = intval($request->get('offset', 0));
        $offset = $offset ?: 0;

        $mkjogoVideoLink = new Mkjogo_Video_Link($this->videoDb, $this->redisVideo, $this->s3);

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $result = $videoLinkModel->listByNew($offset, $limit);
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

        $this->callback($result);

        return false;
    }

    public function listhotAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

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

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkScoreHotListModel = new Redis_Video_Link_Score_Hot_ListModel($this->redisVideo);

        $ids = $redisVideoLinkScoreHotListModel->range($offset, $offset + $limit - 1);
        $result['data'] = $videoLinkModel->getRows($ids);
        $result['page'] = $page;
        $result['total_found'] = $redisVideoLinkScoreHotListModel->len();
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

        $this->callback($result);

        return false;
    }

    public function randomhotAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = isset($mkuser['userid']) ? $mkuser['userid'] : 0;
        $currentUser = Yaf_Registry::get('user');
        $userid = isset($currentUser['id']) ? $currentUser['id'] : 0;

        // Get random link id
        $redisVideoLinkScoreHotListModel = new Redis_Video_Link_Score_Hot_ListModel($this->getRedisVideo());
        $link = $redisVideoLinkScoreHotListModel->random();

        // Get link detail
        $mkjogoVideoLink = new Mkjogo_Video_Link($this->getVideoDb(), $this->getRedisVideo(), $this->getS3());
        $result['data'] = $mkjogoVideoLink->assembleData($link, $userid);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function randomnewAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = isset($mkuser['userid']) ? $mkuser['userid'] : 0;
        $currentUser = Yaf_Registry::get('user');
        $userid = isset($currentUser['id']) ? $currentUser['id'] : 0;

        // Get random link id
        $redisVideoLinkScoreNewListModel = new Redis_Video_Link_Score_New_ListModel($this->getRedisVideo());
        $link = $redisVideoLinkScoreNewListModel->random();

        // Get link detail
        $mkjogoVideoLink = new Mkjogo_Video_Link($this->getVideoDb(), $this->getRedisVideo(), $this->getS3());
        $result['data'] = $mkjogoVideoLink->assembleData($link, $userid);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function listbycolumnAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($column = $request->get('column')) {
            $where = $links = array();
            $where[] = '`column`=' . (int) $column;
            $where = $where ? implode(' AND ', $where) : '';

            $this->getRedisVideo();
            $this->getVideoDb();
            $this->getS3();
            $config = Yaf_Registry::get('config')->toArray();

            $page = intval($request->get('page', 0));
            $page = $page < 1 ? 1 : $page;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 20;

            $offset = ($page - 1) * $limit;

            $mkjogoVideoLink = new Mkjogo_Video_Link($this->videoDb, $this->redisVideo, $this->s3);

            $videoLinkColumnModel = new MySQL_Video_LinkColumnModel($this->videoDb);
            $result = $videoLinkColumnModel->search('link', $where, '`display_order` DESC', $offset, $limit);
            if ($result['data']) {
                foreach ($result['data'] as $val) {
                    $links[] = $val['link'];
                }

                $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
                $data = $videoLinkModel->getRows($links);

                foreach ($data as $key => $val) {
                    if (isset($val['custom_image']) && $val['custom_image']) {
                        $fname = AWS_S3_Bucket_VideoLinkCustomImageModel::getName($val['id'], $val['custom_image']);
                        $data[$key]['custom_image'] = $this->s3->getObjectUrl($config['aws']['s3']['bucket']['video'], $fname, null, array(
                            'Scheme' => 'http',
                        ));
                    }

                    if (isset($val['tags']) && $val['tags']) {
                        $result['data'][$key]['tags'] = $mkjogoVideoLink->getDetailTags(json_decode($val['tags'], true));
                    }
                }

                $result['data'] = $data;
                $result['page'] = $page;
                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function listbytagAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

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

        if ($tag = $request->get('tag')) {
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
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}