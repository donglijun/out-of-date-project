<?php
class Mkjogo_Video_Link
{
    protected $db;

    protected $redis;

    protected $s3;

    protected $tagMaps;

    public function __construct($db, $redis, $s3)
    {
        $this->db = $db;
        $this->redis = $redis;
        $this->s3 = $s3;
    }

    public function ensureVoteHistory($link)
    {
        $redisVideoLinkVoteHistoryModel = new Redis_Video_Link_Vote_HistoryModel($this->redis);

        if (!$redisVideoLinkVoteHistoryModel->isAvailable($link)) {
            $videoLinkVoteHistoryModel = new MySQL_Video_LinkVoteHistoryModel($this->db);

            if ($rowset = $videoLinkVoteHistoryModel->getHistory($link)) {
                foreach ($rowset as $row) {
                    $redisVideoLinkVoteHistoryModel->update($link, $row['user'], $row['score']);
                }
            }
        }

        return $redisVideoLinkVoteHistoryModel;
    }

    public function getDetailTags($tags)
    {
        $result = array();

        if (empty($this->tagMaps)) {
            $videoTagModel = new MySQL_Video_TagModel($this->db);
            $this->tagMaps = $videoTagModel->getMap();
        }

        foreach ($tags as $tag) {
            if (isset($this->tagMaps[$tag])) {
                $result[] = array(
                    'id'    => $tag,
                    'name'  => $this->tagMaps[$tag],
                );
            }
        }

        return $result;
    }

    public function assembleData($link, $user = null, $updateViewsCount = true)
    {
        $result = array();

        $videoLinkModel = new MySQL_Video_LinkModel($this->db);
        $videoCommentModel = new MySQL_Video_CommentModel($this->db);
        $redisVideoLinkShareHistoryLinkModel = new Redis_Video_Link_ShareHistory_LinkModel($this->redis);
        $config = Yaf_Registry::get('config')->toArray();

        if ($linkInfo = $videoLinkModel->getRow($link)) {
            $result = array(
                'id'                => $linkInfo['id'],
                'url'               => $linkInfo['url'],
                'title'             => $linkInfo['title'],
                'author'            => $linkInfo['author'],
                'author_name'       => $linkInfo['author_name'],
                'tags'              => $this->getDetailTags(json_decode($linkInfo['tags'], true)),
                'views_count'       => $linkInfo['views_count'],
                'comments_count'    => $linkInfo['comments_count'],
                'bullets_count'     => $linkInfo['bullets_count'],
                'ups'               => $linkInfo['ups'],
                'downs'             => $linkInfo['downs'],
                'firstComment'      => $videoCommentModel->getFirst($link),
                'vote_score'        => $user ? $this->ensureVoteHistory($link)->get($link, $user) : 0,
                'share_users'       => $redisVideoLinkShareHistoryLinkModel->users($link),
            );

            if ($linkInfo['custom_image']) {
                $fname = AWS_S3_Bucket_VideoLinkCustomImageModel::getName($linkInfo['id'], $linkInfo['custom_image']);
                $result['custom_image'] = $this->s3->getObjectUrl($config['aws']['s3']['bucket']['video'], $fname, null, array(
                    'Scheme'    => 'http',
                ));
            } else {
                $result['custom_image'] = '';
            }

            if ($updateViewsCount) {
                $videoLinkModel->view($link);
            }
        }

        return $result;
    }
}