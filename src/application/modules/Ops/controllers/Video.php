<?php
class VideoController extends CliController
{
    const YOUTUBE_IMAGE_PATTERN = 'https://i.ytimg.com/vi/%s/mqdefault.jpg';

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

    protected function importTagsFromFile($file)
    {
        $this->getVideoDb();

        $tagModel = new MySQL_Video_TagModel($this->videoDb);
        $tagGroupModel = new MySQL_Video_TagGroupModel($this->videoDb);
        $groupName = pathinfo($file, PATHINFO_FILENAME);
        $groupId = $tagGroupModel->insert(array(
            'name'  => $groupName,
        ));

        $counter = 0;

        $fp = fopen($file, 'rb');
        if ($fp) {
            while (($line = fgets($fp, 4096)) !== false) {
                if ($line = trim($line)) {
                    $tagModel->insert(array(
                        'name'  => $line,
                        'group' => $groupId,
                    ));
                }
                $counter++;
            }

            fclose($fp);
        }

        printf("Import %s ... %d\n", $file, $counter);
    }

    public function buildtagsAction()
    {
        $this->importTagsFromFile('/home/ec2-user/download/tag/Şampiyon.data');
        $this->importTagsFromFile('/home/ec2-user/download/tag/Mücadeleler.data');
        $this->importTagsFromFile('/home/ec2-user/download/tag/Takımlar.data');
        $this->importTagsFromFile('/home/ec2-user/download/tag/Yıldız Oyuncular.data');

        return false;
    }

    public function init_video_tag_listAction()
    {
        $this->getRedisVideo();
        $this->getVideoDb();

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkScoreNewListByTagModel = new Redis_Video_Link_Score_New_ListByTagModel($this->redisVideo);

        $count = 0;
        $result = $videoLinkModel->search('id,tags,created_on', null, null, 0, 9999);
        foreach ($result['data'] as $row) {
            if (isset($row['tags']) && $row['tags']) {
                $tags = json_decode($row['tags'], true);
                foreach ($tags as $tag) {
                    $redisVideoLinkScoreNewListByTagModel->update($tag, $row['id'], $row['created_on']);
                }
            }

            $count++;
        }

        printf("%d ok.\n", $count);

        return false;
    }

    public function update_first_voteAction()
    {
        $this->getRedisVideo();
        $this->getVideoDb();

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkVoteQueueModel = new Redis_Video_Link_Vote_QueueModel($this->redisVideo);

        $count = 0;
        $where = '`ups`=0 AND `downs`=0';
        $result = $videoLinkModel->search('id, author', $where, null, 0, 9999);
        foreach ($result['data'] as $row) {
            // Vote up
            $redisVideoLinkVoteQueueModel->push(array(
                'link'  => $row['id'],
                'user'  => $row['author'],
                'score' => 1,
            ));

            $count++;
        }

        printf("%d ok.\n", $count);

        return false;
    }

    protected function get_thumbnail_from_youtube_link($url)
    {
        $result = '';

        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $output);

        if (isset($output['v'])) {
            $result = sprintf(self::YOUTUBE_IMAGE_PATTERN, $output['v']);
        }

        return $result;
    }

    public function import_data_for_nikksyAction()
    {
        $this->getRedisVideo();
        $this->getVideoDb();

        $author = 5218562;
        $author_name = 'Vubmk';
        $timestamp = time();
        $dup = 1;

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkVoteQueueModel = new Redis_Video_Link_Vote_QueueModel($this->redisVideo);
        $redisVideoLinkScoreNewListByTagModel = new Redis_Video_Link_Score_New_ListByTagModel($this->redisVideo);

        $videoTagModel = new MySQL_Video_TagModel($this->videoDb);
        $tagMaps = $videoTagModel->getMap();
        $tagMaps = array_flip($tagMaps);

        $file = '/tmp/nikksy-lol-video-link2.csv';
        try {
            if (($fp = fopen($file, 'rb')) !== false) {
                while (($row = fgetcsv($fp)) !== false) {
                    $champion = $row[0];
                    $title = $row[1];
                    $url = $row[2];

                    if ($champion && $title && $url) {
                        if (isset($tagMaps[$champion])) {
                            $tags = array($tagMaps[$champion]);

                            if ($row = $videoLinkModel->getRowByUrl($url, array('id'))) {
                                printf("Duplicate url %d: %s\n", $dup++, $url);
                            } else {
                                $data = array(
                                    'url' => $url,
                                    'title' => $title,
                                    'thumbnail_url' => $this->get_thumbnail_from_youtube_link($url),
                                    'author' => $author,
                                    'author_name' => $author_name,
                                    'tags' => json_encode($tags),
                                    'created_on' => $timestamp,
                                );

                                $linkId = $videoLinkModel->insert($data);

                                // Vote up
                                $redisVideoLinkVoteQueueModel->push(array(
                                    'link' => $linkId,
                                    'user' => $author,
                                    'score' => 1,
                                ));

                                // Save share history
                                $this->saveShareHistory($linkId, $author, $timestamp);

                                // Save new list
                                $redisVideoLinkScoreNewListModel = new Redis_Video_Link_Score_New_ListModel($this->redisVideo);
                                $redisVideoLinkScoreNewListModel->update($linkId, $timestamp);

                                // Save new list by tag
                                foreach ($tags as $tag) {
                                    $redisVideoLinkScoreNewListByTagModel->update($tag, $linkId, $timestamp);
                                }
                            }
                        } else {
                            printf("Invalid tag: %s\n", $champion);
                        }
                    }
                }

                fclose($fp);
            }
        } catch (Exception $e) {
            var_dump($e);
        }

        return false;
    }

    public function repair_share_history_userAction()
    {
        $this->getVideoDb();
        $this->getRedisVideo();

        $user = 100005;
        $count = 0;

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkShareHistoryUserModel = new Redis_Video_Link_ShareHistory_UserModel($this->redisVideo);
        $links = $redisVideoLinkShareHistoryUserModel->links($user);

        foreach ($links as $link) {
            if (!($row = $videoLinkModel->getRow($link, array('id')))) {
                $redisVideoLinkShareHistoryUserModel->rem($user, $link);

                $count += 1;
            }
        }

        printf("Repair %d.\n", $count);

        return false;
    }
}