<?php
class VideolinkController extends CliController
{
    const MAX_LOOP = 3600;

    protected $videoDb;

    protected $redisVideo;

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

    protected function ensureVoteHistory($link)
    {
        $redisVideoLinkVoteHistoryModel = new Redis_Video_Link_Vote_HistoryModel($this->getRedisVideo());

        if (!$redisVideoLinkVoteHistoryModel->isAvailable($link)) {
            $videoLinkVoteHistoryModel = new MySQL_Video_LinkVoteHistoryModel($this->getVideoDb());

            if ($rowset = $videoLinkVoteHistoryModel->getHistory($link)) {
                foreach ($rowset as $row) {
                    $redisVideoLinkVoteHistoryModel->update($link, $row['user'], $row['score']);
                }
            }
        }

        return $redisVideoLinkVoteHistoryModel;
    }

    public function process_voteAction()
    {
        $this->getRedisVideo();
        $this->getVideoDb();

        $counter = 0;

        $redisVideoLinkVoteQueueModel = new Redis_Video_Link_Vote_QueueModel($this->redisVideo);
        $mkjogoVideoLink = new Mkjogo_Video_Link($this->getVideoDb(), $this->getRedisVideo(), null);

        while ($counter < self::MAX_LOOP) {
            if ($len = $redisVideoLinkVoteQueueModel->len()) {
                $len = min($len, 1000);
                $list = $redisVideoLinkVoteQueueModel->range(0, $len - 1);
                $pool = array();

                foreach ($list as $val) {
                    list($link, $user, $score, ) = explode(Redis_Video_Link_Vote_QueueModel::DELIMITER, $val);
                    $pool[$link][$user] = $score;
                }

                $redisVideoLinkVoteHistoryModel = new Redis_Video_Link_Vote_HistoryModel($this->redisVideo);
                $videoLinkVoteHistoryModel = new MySQL_Video_LinkVoteHistoryModel($this->videoDb);
                $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);

                foreach ($pool as $link => $val) {
                    if (!$videoLinkModel->getRow($link, array('id'))) {
                        continue;
                    }

                    $mkjogoVideoLink->ensureVoteHistory($link);

                    foreach ($val as $user => $score) {
                        $oldScore = $redisVideoLinkVoteHistoryModel->get($link, $user);

                        if ($oldScore == $score) {
                            continue;
                        }

                        $redisVideoLinkVoteHistoryModel->update($link, $user, $score);

                        $videoLinkVoteHistoryModel->batchReplace(array(
                            'link'          => $link,
                            'user'          => $user,
                            'score'         => $score,
                            'updated_on'    => time(),
                        ));

                        //Send job to update score
                        $workload = array(
                            'link'  => $link,
                        );
                        $gearmanClient = Daemon::getGearmanClient();
                        $gearmanClient->doBackground('video-link-update-score', json_encode($workload));
                    }
                }
                $videoLinkVoteHistoryModel->completeBatchReplace();

                // Pop queue
                $redisVideoLinkVoteQueueModel->trim($len, -1);
            } else {
                $counter++;
                sleep(2);
            }
        }

        return false;
    }

    public function calculate_hot_pointAction()
    {
        $this->getRedisVideo();
        $this->getVideoDb();

        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $redisVideoLinkScoreHotListModel = new Redis_Video_Link_Score_Hot_ListModel($this->redisVideo);

        $step = 1000;
        $range = $videoLinkModel->getRange('id');
        $start = (int) $range['min'] ?: 1;
        $end   = (int) $range['max'];

        while ($rowset = $videoLinkModel->getRowsByStep('id', $start, $end, $step)) {
            foreach ($rowset as $row) {
                $hotPoint = Reddit_Sort::hot($row['ups'], $row['downs'], $row['created_on']);
                $redisVideoLinkScoreHotListModel->update($row['id'], $hotPoint);
            }

            $start = $row['id'] + 1;
        }

        return false;
    }
}