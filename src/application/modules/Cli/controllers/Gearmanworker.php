<?php
use Aws\S3\S3Client;

class GearmanworkerController extends WorkerController
{
    protected $lolDb;

    protected $videoDb;

    protected $passportDb;

    protected $streamingDb;

    protected $sphinxLolRtDb;

    protected $sphinxLolPlainDb;

    protected $redisLOL;

    protected $redisLOLMatchCollect;

    protected $redisVideo;

    protected $redisStreaming;

    protected $s3;

    protected $lolChampionsNameMap = array();

    protected $lolModeNameMap = array();

    protected $platforms = array();

    protected $lolMapMap = array();

    protected function getLolDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }

        return $this->lolDb;
    }

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

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getSphinxLolRtDb()
    {
        if (empty($this->sphinxLolRtDb)) {
            $this->sphinxLolRtDb = Daemon::getSphinxQL('sphinxql-lol-rt', 'sphinxql-lol-rt');
        }

        return $this->sphinxLolRtDb;
    }

    protected function getSphinxLolPlainDb()
    {
        if (empty($this->sphinxLolPlainDb)) {
            $this->sphinxLolPlainDb = Daemon::getSphinxQL('sphinxql-lol-plain', 'sphinxql-lol-plain');
        }

        return $this->sphinxLolPlainDb;
    }

    protected function getRedisLOL()
    {
        if (empty($this->redisLOL)) {
            $this->redisLOL = Daemon::getRedis('redis-lol', 'redis-lol');
        }

        return $this->redisLOL;
    }

    protected function getRedisLOLMatchCollect()
    {
        if (empty($this->redisLOLMatchCollect)) {
            $this->redisLOLMatchCollect = Daemon::getRedis('redis-lol-match-collect', 'redis-lol-match-collect');
        }

        return $this->redisLOLMatchCollect;
    }

    protected function getRedisVideo()
    {
        if (empty($this->redisVideo)) {
            $this->redisVideo = Daemon::getRedis('redis-video', 'redis-video');
        }

        return $this->redisVideo;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
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

    protected function checkLolDb()
    {
        if (!$this->lolDb) {
            $this->getLolDb();
        } else {
            try {
                $this->lolDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('lol-db');
                $this->lolDb = null;

                $this->getLolDb();
            }
        }
    }

    protected function checkVideoDb()
    {
        if (!$this->videoDb) {
            $this->getVideoDb();
        } else {
            try {
                $this->videoDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('video-db');
                $this->videoDb = null;

                $this->getVideoDb();
            }
        }
    }

    protected function checkPassportDb()
    {
        if (!$this->passportDb) {
            $this->getPassportDb();
        } else {
            try {
                $this->passportDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('passport-db');
                $this->passportDb = null;

                $this->getPassportDb();
            }
        }
    }

    protected function checkStreamingDb()
    {
        if (!$this->streamingDb) {
            $this->getStreamingDb();
        } else {
            try {
                $this->streamingDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('streaming-db');
                $this->streamingDb = null;

                $this->getStreamingDb();
            }
        }
    }

    protected function checkSphinxLolRtDb()
    {
        if (!$this->sphinxLolRtDb) {
            $this->getSphinxLolRtDb();
        } else {
            try {
                $this->sphinxLolRtDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('sphinxql-lol-rt');
                $this->sphinxLolRtDb = null;

                $this->getSphinxLolRtDb();
            }
        }
    }

    protected function checkSphinxLolPlainDb()
    {
        if (!$this->sphinxLolPlainDb) {
            $this->getSphinxLolPlainDb();
        } else {
            try {
                $this->sphinxLolPlainDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('sphinxql-lol-plain');
                $this->sphinxLolPlainDb = null;

                $this->getSphinxLolPlainDb();
            }
        }
    }

    protected function getLOLChampionsNameMap()
    {
        if (!$this->lolChampionsNameMap) {

            $model = new MySQL_LOL_Original_Champions_enUSModel($this->getLolDb());

            foreach ($model->getChampionsMap() as $row) {
                $this->lolChampionsNameMap[$row['name']] = (int) $row['id'];
            }
        }

        return $this->lolChampionsNameMap;
    }

    protected function getLOLModeNameMap()
    {
        if (!$this->lolModeNameMap) {

            $model = new MySQL_LOL_ModeModel($this->getLolDb());

            foreach ($model->getModeMap() as $row) {
                $this->lolModeNameMap[$row['name']] = $row;
            }
        }

        return $this->lolModeNameMap;
    }

    protected function getPlatforms()
    {
        if (!$this->platforms) {
            $lolPlatformModel = new MySQL_LOL_PlatformModel($this->getLolDb());

            $this->platforms = $lolPlatformModel->getAvailablePlatforms();
        }

        return $this->platforms;
    }

    protected function getLOLMapMap()
    {
        if (!$this->lolMapMap) {
            $lolMapModel = new MySQL_LOL_MapModel($this->getLolDb());

            $this->lolMapMap = $lolMapModel->getMapMap();
        }

        return $this->lolMapMap;
    }

    protected function getLOLMapAlias($mapid)
    {
        $this->getLOLMapMap();

        return isset($this->lolMapMap[$mapid]) ? $this->lolMapMap[$mapid]['alias'] : 0;
    }

    public function init()
    {
        parent::init();
    }

//    public function indexAction()
//    {
//        $data = array(
//            'platform'    => 'KR',
//            'gameid'      => '1212302235',
//            'data'        => file_get_contents('/tmp/match-demo.js'),
//        );
//
//        $response = Misc::curlPost('//api.lnplay.com/lol/match/collect', $data);
//
//        Debug::dump($response);
//
//        return false;
//    }

    public function lol_match_collectAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('lol-match-collect', function(GearmanJob $job) {
            try {
                $workload = json_decode($job->workload(), true);
                $platform = strtoupper($workload['platform']);

                Misc::log('Start job: ' . $job->workload(), Zend_Log::INFO);

                $this->checkLolDb();

//                $this->checkSphinxLolRtDb();

                $this->getLOLChampionsNameMap();

                $this->getLOLModeNameMap();

                $this->getPlatforms();

                // Get Gearman client
                $gearmanClient = Daemon::getGearmanClient();

                Misc::log('Connect database ok', Zend_Log::DEBUG);

                $redisLOLMatchModelClass = sprintf('Redis_LOL_Match_%sModel', $platform);
                $redisLOLMatchModel = new $redisLOLMatchModelClass($this->getRedisLOLMatchCollect());

                // Check platform
                if (!in_array(strtolower($platform), $this->platforms)) {
                    Misc::log(sprintf('Invalid LOL platform: *%s*', $platform), Zend_Log::WARN);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return false;
                }

                // Retrieve raw data
                $rawdata = $redisLOLMatchModel->getData($workload['gameid']);

                Misc::log('Retrieve raw data ok', Zend_Log::DEBUG);

                if (!$rawdata) {
                    Misc::log(sprintf('Invalid LOL match data (%s): %s', $job->workload(), var_export($rawdata, true)), Zend_Log::WARN);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return false;
                }

                if (!($data = json_decode($rawdata, true))) {
                    Misc::log(sprintf('Invalid LOL match JSON (%s): %s %s', $job->workload(), json_last_error_msg(), var_export($rawdata, true)), Zend_Log::WARN);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return false;
                }

                // Check gametype
                if ($data['gametype'] != Mongo_LOL_Match_BaseModel::GAMETYPE_MATCHED_GAME) {
                    Misc::log(sprintf('Invalid LOL game type (%s): %s', $job->workload(),  $data['gametype']), Zend_Log::INFO);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return false;
                }

                Misc::log('Validate data ok', Zend_Log::DEBUG);

                // Mongo for match document
                $mongoLOLMatchModelClass = sprintf('Mongo_LOL_Match_%sModel', $platform);
                $mongoLOLMatchModel = new $mongoLOLMatchModelClass;

                $timestamp = time();

                // Save raw data to mongodb
                $data['_id']        = (int) $data['gameid'];
                $data['created_on'] = $timestamp;
                $status = $mongoLOLMatchModel->insert($data);

                // Check status
                if (is_array($status) && !$status['ok']) {
                    Misc::log(var_export($status, true), Zend_Log::ERR);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return false;
                }

                Misc::log('Save raw data to MongoDB ok', Zend_Log::DEBUG);

                if (isset($data['queuetype']) && isset($this->lolModeNameMap[$data['queuetype']])) {
                    $mode = $this->lolModeNameMap[$data['queuetype']];
                } else {
                    Misc::log('Invalid LOL queue type: ' . $data['queuetype'], Zend_Log::NOTICE);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return false;
                }

                Misc::log('Check queue type ok', Zend_Log::DEBUG);

                $map = isset($data['mkdata']['mapId']) ? $this->getLOLMapAlias($data['mkdata']['mapId']) : 0;

                $players = array();
                $vsAI = false;
                $selfwin = false;

                // Get self team win status
                foreach ($data['selfteamplayers'] as $val) {
                    $maindata = current($val['maindata']);

                    if (isset($maindata['WIN']) && $maindata['WIN']) {
                        $selfwin = true;
                        break;
                    }
                }

                // Update play win status
                foreach ($data['selfteamplayers'] as $val) {
                    // Exclude bots
                    if (!$val['botPlayer']) {
                        $val['win'] = $selfwin ? 1 : 0;
                        $players[(int) $val['userid']] = $val;
                    } else {
                        $vsAI = true;
                    }
                }

                foreach ($data['otherteamplayers'] as $val) {
                    // Exclude bots
                    if (!$val['botPlayer']) {
                        $val['win'] = $selfwin ? 0 : 1;
                        $players[(int) $val['userid']] = $val;
                    } else {
                        $vsAI = true;
                    }
                }

//                if (isset($data['selfteamplayers']) && isset($data['otherteamplayers'])) {
//                    foreach (array_merge($data['selfteamplayers'], $data['otherteamplayers']) as $val) {
//                        // Exclude bots
//                        if (!$val['botPlayer']) {
//                            $players[(int) $val['userid']] = $val;
//                        } else {
//                            $vsAI = true;
//                        }
//                    }
//                } else {
//                    Misc::log('Broken data, no team players', Zend_Log::WARN);
//                    $redisLOLMatchModel->remove($workload['gameid']);
//
//                    return false;
//                }

                Misc::log('Get team players ok', Zend_Log::DEBUG);

                // Save recent match of each players
                $redisLOLSummonerRecentMatchModelClass = sprintf('Redis_LOL_Summoner_RecentMatch_%sModel', $platform);
                $redisLOLSummonerRecentMatchModel = new $redisLOLSummonerRecentMatchModelClass($this->getRedisLOL());

                foreach ($players as $key => $val) {
                    $redisLOLSummonerRecentMatchModel->update($key, (int) $workload['gameid']);
                }

                Misc::log('Save recent match ok', Zend_Log::DEBUG);

                // Model for user
                $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
                $lolUserModel = new $lolUserModelClass($this->lolDb);

                // Index for user
//                $sphinxqlLOLUserModelClass = sprintf('SphinxQL_LOL_User_%sModel', $platform);
//                $sphinxqlLOLUserModel = new $sphinxqlLOLUserModelClass;

                // Find users already exist
                $users = $lolUserModel->getRows(array_keys($players), array('id', 'metadata'));

                // Update users already exist
                foreach ($users as $user) {
                    $players[$user['id']]['user'] = $user['id'];
                }

                // Insert or update users info
                foreach ($players as $key => $player) {
                    $userid = (int) $key;

                    $user = array(
                        'name'          => $player['summonername'],
                        'level'         => (int) $player['level'],
                        'icon_id'       => (int) $player['profileIconId'],
                        'point'         => 0, //@todo
                        'updated_on'    => $timestamp,
                    );

                    if (!isset($player['user'])) {
                        $user['id'] = $userid;
                        $user['metadata'] = json_encode(array(
                            $mode['name']   => array(
                                'leaves'    => (int) $player['leaves'],
                                'losses'    => (int) $player['losses'],
                                'wins'      => (int) $player['wins'],
                            ),
                        ));

                        // Insert user into mysql
                        $lolUserModel->replace($user);

                        $players[$key]['user'] = $userid;

                        // Insert user into sphinx
                        //@disabled for searchd crash occasionally
//                        $sphinxqlLOLUserModel->replace(array(
//                            'id'        => $userid,
//                            'name'      => $player['summonername'],
//                            'metadata'  => $user['metadata'],
//                        ));

                        // Send job
                        $gearmanClient->doBackground('lol-index-user-name', json_encode(array(
                            'platform'  => $platform,
                            'id'        => $userid,
                            'name'      => $user['name'],
                        )));
                    } else {
                        $metadata = json_decode($users[$userid]['metadata'], true);

                        if (!is_array($metadata)) {
                            $metadata = array();
                        }

                        $metadata[$mode['name']] = array(
                            'leaves'    => (int) $player['leaves'],
                            'losses'    => (int) $player['losses'],
                            'wins'      => (int) $player['wins'],
                        );

                        $user['metadata'] = json_encode($metadata);

                        // Update user in mysql
                        $lolUserModel->update($player['user'], $user);

                        // Update user in sphinx
                    }
                }

                Misc::log('Insert/update users into MySQL and Sphinx ok', Zend_Log::DEBUG);

                //@todo if don't need index or play with bots, then everything is ok now
                if (!$mode['is_index'] || $vsAI) {
                    Misc::log('Do not need index match, good job', Zend_Log::INFO);
                    $redisLOLMatchModel->remove($workload['gameid']);

                    return true;
                }

                // Model for match
                $lolMatchModelClass = sprintf('MySQL_LOL_Match_%sModel', $platform);
                $lolMatchModel = new $lolMatchModelClass($this->lolDb);

                // Index for match
//                $sphinxqlLOLMatchModelClass = sprintf('SphinxQL_LOL_Match_%sModel', $platform);
//                $sphinxqlLOLMatchModel = new $sphinxqlLOLMatchModelClass;

                foreach ($players as $player) {
                    $maindata = current($player['maindata']);

                    $items = array(
                        (int) $maindata['ITEM0'],
                        (int) $maindata['ITEM1'],
                        (int) $maindata['ITEM2'],
                        (int) $maindata['ITEM3'],
                        (int) $maindata['ITEM4'],
                        (int) $maindata['ITEM5'],
                        (int) $maindata['ITEM6'],
                    );

                    $spells = array(
                        (int) $player['spell1Id'],
                        (int) $player['spell2Id'],
                    );

                    $match = array(
                        'user'          => $player['user'],
                        'champion'      => isset($this->lolChampionsNameMap[$player['champion']]) ? $this->lolChampionsNameMap[$player['champion']] : 0,
                        'mode'          => $mode['id'],
                        'win'           => $player['win'],
                        'start'         => (int) $data['gamestarttime'],
                        'game'          => (int) $data['gameid'],
                        'k'             => (int) $maindata['CHAMPIONS_KILLED'],
                        'd'             => (int) $maindata['NUM_DEATHS'],
                        'a'             => (int) $maindata['ASSISTS'],
                        'mddp'          => (int) $maindata['MAGIC_DAMAGE_DEALT_PLAYER'],
                        'pddp'          => (int) $maindata['PHYSICAL_DAMAGE_DEALT_PLAYER'],
                        'tdt'           => (int) $maindata['TOTAL_DAMAGE_TAKEN'],
                        'lmk'           => (int) $maindata['LARGEST_MULTI_KILL'],
                        'mk'            => (int) $maindata['MINIONS_KILLED'],
                        'nmk'           => (int) $maindata['NEUTRAL_MINIONS_KILLED'],
                        'gold'          => (int) $maindata['GOLD_EARNED'],
                        'items'         => json_encode($items),
                        'spells'        => json_encode($spells),
                        'aps'           => json_encode(isset($player['aps']) ? $player['aps'] : array()),
                        'ranked'        => (int) $data['ranked'],
                        'len'           => (int) $data['gamelen'],
                        'map'           => $map,
                        'created_on'    => $timestamp,
                    );

                    // Insert match into mysql
                    $match['id'] = $lolMatchModel->insert($match);

//                    $match['items']  = Helper_Formatter_Sphinx::formatMVA($items);
//                    $match['spells'] = Helper_Formatter_Sphinx::formatMVA($spells);

                    // Insert match into sphinx
                    //@disabled for searchd crash occasionally
//                    $sphinxqlLOLMatchModel->insert($match);
                }

                Misc::log('Insert match into MySQL and Sphinx ok', Zend_Log::DEBUG);

                if ($data['ranked']) {
                    $picks = array();

                    foreach ($players as $player) {
                        $picks[] = $this->lolChampionsNameMap[$player['champion']];
                    }

                    $bans = isset($data['mkdata']['bannedChampions']) ? $data['mkdata']['bannedChampions'] : array();

                    $pick_ban = array(
                        'id'            => (int) $data['gameid'],
                        'pick'          => json_encode($picks),
                        'ban'           => json_encode($bans),
                        'map'           => $map,
                        'mode'          => $mode['id'],
                        'start'         =>(int) $data['gamestarttime'],
                        'created_on'    => $timestamp,
                    );

                    // Model for pick-ban
                    $lolChampionPickBanModelClass = sprintf('MySQL_LOL_Champion_PickBan_%sModel', $platform);
                    $lolChampionPickBanModel = new $lolChampionPickBanModelClass($this->lolDb);

                    $lolChampionPickBanModel->insert($pick_ban);

                    // Index for pick-ban
//                    $sphinxqlLOLChampionPickBanModelClass = sprintf('SphinxQL_LOL_Champion_PickBan_%sModel', $platform);
//                    $sphinxqlLOLChampionPickBanModel = new $sphinxqlLOLChampionPickBanModelClass;

//                    $pick_ban['pick'] = Helper_Formatter_Sphinx::formatMVA($picks);
//                    $pick_ban['ban']  = Helper_Formatter_Sphinx::formatMVA($bans);

                    //@disabled for searchd crash occasionally
//                    $sphinxqlLOLChampionPickBanModel->insert($pick_ban);

                    Misc::log('Insert pick-ban list into MySQL and Sphinx ok', Zend_Log::DEBUG);
                }

                // Clear queue
                $redisLOLMatchModel->remove($workload['gameid']);

                Misc::log('Good job: ' . $workload['gameid'], Zend_Log::INFO);
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
                $redisLOLMatchModel->remove($workload['gameid']);

//                throw new Exception($e);
            }
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function lol_index_user_nameAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('lol-index-user-name', function(GearmanJob $job) {
            try {
                $workload = json_decode($job->workload(), true);
                $platform = strtoupper($workload['platform']);

                $this->checkSphinxLolRtDb();

                // Index for user name
                $sphinxqlLOLUserNameModelClass = sprintf('SphinxQL_LOL_User_Name_%sModel', $platform);
                $sphinxqlLOLUserNameModel = new $sphinxqlLOLUserNameModelClass($this->sphinxLolRtDb);

                // Update index
                $sphinxqlLOLUserNameModel->insert(array(
                    'id'        => $workload['id'],
                    'name'      => $workload['name'],
                ));
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function lol_summoner_calculate_popular_items_by_championAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('lol-summoner-calculate-popular-items-by-champion', function(GearmanJob $job) {
            $result = '';

            try {
                $workload = json_decode($job->workload(), true);
                $platform = strtoupper($workload['platform']);

                $this->checkSphinxLolPlainDb();

                $where = "`user`={$workload['summoner']} AND `champion`={$workload['champion']}";

                $sphinxqlLOLMatchModelClass = sprintf('SphinxQL_LOL_Match_%sModel', $platform);
                $sphinxqlLOLMatchModel = new $sphinxqlLOLMatchModelClass($this->sphinxLolPlainDb);

                $result = $sphinxqlLOLMatchModel->statsItems($where);
                $result = json_encode($result);
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function video_link_update_scoreAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('video-link-update-score', function(GearmanJob $job) {
            $result = '';

            try {
                $workload = json_decode($job->workload(), true);
                $link = $workload['link'];

                $this->checkVideoDb();

                $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);

                $redisVideoLinkVoteHistoryModel = new Redis_Video_Link_Vote_HistoryModel($this->getRedisVideo());
                $ups = $redisVideoLinkVoteHistoryModel->totalUps($link);
                $downs = $redisVideoLinkVoteHistoryModel->totalDowns($link);

                $videoLinkModel->update($link, array(
                    'ups'       => $ups,
                    'downs'     => $downs,
                ));
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function video_link_save_share_historyAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('video-link-save-share-history', function(GearmanJob $job) {
            $result = '';

            try {
                $workload = json_decode($job->workload(), true);

                $this->checkVideoDb();
                $this->getRedisVideo();

                $videoLinkShareHistoryModel = new MySQL_Video_LinkShareHistoryModel($this->videoDb);
                if (!$videoLinkShareHistoryModel->exists($workload['link'], $workload['user'])) {
                    $videoLinkShareHistoryModel->insert(array(
                        'link'          => $workload['link'],
                        'user'          => $workload['user'],
                        'created_on'    => $workload['timestamp'],
                    ));

                    $redisVideoLinkShareHistoryLinkModel = new Redis_Video_Link_ShareHistory_LinkModel($this->redisVideo);
                    $redisVideoLinkShareHistoryLinkModel->update($workload['link'], $workload['user'], $workload['timestamp']);

                    $redisVideoLinkShareHistoryUserModel = new Redis_Video_Link_ShareHistory_UserModel($this->redisVideo);
                    $redisVideoLinkShareHistoryUserModel->update($workload['user'], $workload['link'], $workload['timestamp']);
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function video_link_crawl_twitch_thumbAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('video-link-crawl-twitch-thumb', function(GearmanJob $job) {
            $result = '';

            try {
                $workload = json_decode($job->workload(), true);

                $this->checkVideoDb();

                $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
                $mkjogoVideoLinkTwitch = new Mkjogo_Video_Link_Twitch();

                if ($thumbUrl = $mkjogoVideoLinkTwitch->getFromVideoUrl($workload['url'])) {
                    $videoLinkModel->update($workload['link'], array(
                        'thumbnail_url' => $thumbUrl,
                    ));
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function send_service_emailAction()
    {
        $config = Yaf_Registry::get('config')->toArray();
        $tr = new Zend_Mail_Transport_Smtp($config['mail']['smtp']['host'], array(
            'auth'      => 'login',
            'username'  => $config['mail']['smtp']['user'],
            'password'  => $config['mail']['smtp']['password'],
        ));
        Zend_Mail::setDefaultTransport($tr);

        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('send-service-email', function(GearmanJob $job, &$config) {
            $result = '';

            try {
                $workload = json_decode($job->workload(), true);

                $this->checkPassportDb();

                $mail = new Zend_Mail();
                $mail->setFrom($config['mail']['from']['email'], $config['mail']['from']['name']);
                $mail->addTo($workload['email'], $workload['username']);
                $mail->setSubject($workload['subject']);
                $mail->setBodyHtml($workload['body'], 'utf-8');
                $mail->send();
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        }, $config);

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function streaming_start_recordingAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('streaming-start-recording', function(GearmanJob $job) {
            $result = '';
            $config = Yaf_Registry::get('config')->toArray();
            $master = $config['streaming']['media']['master'];

            try {
                Misc::log('Start recording ' . $job->workload(), Zend_Log::WARN);
                $workload = json_decode($job->workload(), true);

                $this->checkStreamingDb();
                $this->getRedisStreaming();

                $redisStreamingChannelLogModel = new Redis_Streaming_Channel_LogModel($this->redisStreaming);
                $redisStreamingChannelLogModel->log($workload['channel'], $workload['upstream_ip'], $workload['session'], 'Worker started recording');

                $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);

                $id = $streamingBroadcastModel->insert(array(
                    'channel'       => $workload['channel'],
                    'upstream_ip'   => $workload['upstream_ip'],
                    'session'       => $workload['session'],
                    'recording_ip'  => $this->getIp(),
                    'recording_on'  => time(),
                ));

                // Record stream from master
                $localPath = sprintf('%s/b-%s-%s.flv', $config['streaming']['recording']['local-path'], $id, $workload['channel']);
                $rtmpdump = $config['streaming']['recording']['bin']['rtmpdump'];
                $cmd = sprintf('%s -r rtmp://%s/live/%s -v -q -o %s >/dev/null 2>&1 &', $rtmpdump, $master, $workload['stream_key'], $localPath);
                $lastLine = system($cmd);

                // Snapshot
//                $previewPath = sprintf('%s/%s.jpg', $config['streaming']['recording']['local-path'], $id);
//                $cmd = sprintf('/usr/local/ffmpeg/bin/ffmpeg -an -rtmp_live live -i rtmp://media.nikksy.com/play/%d -vframes 1 -f image2 %s -y >/dev/null &', $workload['channel'], $previewPath);
//                system($cmd);
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function streaming_stop_recordingAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('streaming-stop-recording', function(GearmanJob $job) {
            $result = '';

            try {
                Misc::log('Stop recording ' . $job->workload(), Zend_Log::WARN);
                $workload = json_decode($job->workload(), true);

                $this->checkStreamingDb();
                $this->getRedisStreaming();

                $redisStreamingChannelLogModel = new Redis_Streaming_Channel_LogModel($this->redisStreaming);
                $redisStreamingChannelLogModel->log($workload['channel'], $workload['upstream_ip'], $workload['session'], 'Worker stopped recording');

                $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                if ($broadcastInfo = $streamingBroadcastModel->locate($workload['channel'], $workload['upstream_ip'], $workload['session'])) {
                    if (!$broadcastInfo['ending_on']) {
                        $channelInfo = $streamingChannelModel->getRow($workload['channel'], array('id', 'title'));
                        $streamingBroadcastModel->update($broadcastInfo['id'], array(
                            'title'     => $channelInfo['title'],
                            'length'    => $workload['length'],
                            'ending_on' => time(),
                        ));

                        // Sleep to wait for dumping
                        sleep(60);

                        // Update broadcast
                        $client = new Yar_Client(sprintf('http://%s/service/broadcast/upload', $broadcastInfo['recording_ip']));
                        $client->setOpt(YAR_OPT_CONNECT_TIMEOUT, 3000);
                        $client->setOpt(YAR_OPT_TIMEOUT, 0);
                        $result = $client->upload_v2($broadcastInfo['id']);
                    } else {
                        throw new Exception('Ignore processed broadcast ' . $job->workload());
                    }
                } else {
                    throw new Exception('Not found broadcast ' . $job->workload());
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);

                $redisStreamingChannelLogModel->log($workload['channel'], $workload['upstream_ip'], $workload['session'], 'Worker triggered error: ' . $e->getMessage());
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function streaming_highlightAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('streaming-highlight', function(GearmanJob $job) {
            $result = '';
            $data = array();

            try {
                Misc::log('Start highlight ' . $job->workload(), Zend_Log::WARN);
                $workload = $job->workload();

                $this->checkStreamingDb();

                $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
                $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
                $highlightInfo = $streamingBroadcastHighlightModel->getRow($workload);

                if ($highlightInfo && ($highlightInfo['uploaded_on'] == 0)) {
                    $broadcastInfo = $streamingBroadcastModel->getRow($highlightInfo['broadcast'], array('id', 'remote_path'));

                    // Download stream
                    $this->getS3();
                    $config = Yaf_Registry::get('config')->toArray();

                    $tmpPath = sprintf('%s/%s', $config['streaming']['recording']['local-path'], pathinfo($broadcastInfo['remote_path'], PATHINFO_BASENAME));

                    if (!file_exists($tmpPath)) {
                        $this->s3->getObject(array(
                            'Bucket'    => $config['aws']['s3']['bucket']['streaming'],
                            'Key'       => $broadcastInfo['remote_path'],
                            'SaveAs'    => $tmpPath,
                        ));
                    }

                    // Clip stream
                    $ffmpeg = $config['streaming']['recording']['bin']['ffmpeg'];
                    $localPath = sprintf('%s/c-%s-%s.flv', $config['streaming']['recording']['local-path'], $workload, $highlightInfo['channel']);
                    $cmd = sprintf('%s -ss %d -i %s -t %d -c copy %s -y >/dev/null 2>&1', $ffmpeg, $highlightInfo['start'], $tmpPath, $highlightInfo['length'], $localPath);
                    $lastLine = system($cmd);

                    // Fix flv
                    $yamdi = $config['streaming']['recording']['bin']['yamdi'];
                    $fixedLocalPath = sprintf('%s/c-%s-%s-fixed.flv', $config['streaming']['recording']['local-path'], $workload, $highlightInfo['channel']);
                    $cmd = sprintf('%s -i %s -o %s', $yamdi, $localPath, $fixedLocalPath);
                    $lastLine = system($cmd);

                    // Delete raw
                    $cmd = sprintf('rm -f %s', $localPath);
                    $lastLine = system($cmd);

                    $localPath = $fixedLocalPath;

                    // Clear cache before query size
                    clearstatcache(true, $localPath);
                    $size = filesize($localPath);

                    // Snapshot
                    $w = $config['streaming']['recording']['snapshot']['width'];
                    $h = $config['streaming']['recording']['snapshot']['height'];
                    $previewPath = sprintf('%s/c-%s-%s-%dx%d.jpg', $config['streaming']['recording']['local-path'], $workload, $highlightInfo['channel'], $w, $h);
                    $cmd = sprintf('%s -an -i %s -vframes 1 -f image2 -s %dx%d %s -y >/dev/null 2>&1', $ffmpeg, $localPath, $w, $h, $previewPath);
                    $lastLine = system($cmd);

                    $sourcePreviewPath = sprintf('%s/c-%s-%s-source.jpg', $config['streaming']['recording']['local-path'], $workload, $highlightInfo['channel']);
                    $cmd = sprintf('%s -an -i %s -vframes 1 -f image2 %s -y >/dev/null 2>&1', $ffmpeg, $localPath, $sourcePreviewPath);
                    $lastLine = system($cmd);

                    // Upload highlight
                    $y = date('Y', $highlightInfo['submitted_on']);
                    $m = date('m', $highlightInfo['submitted_on']);
                    $d = date('d', $highlightInfo['submitted_on']);
                    $remotePath = sprintf('highlight/flv/%s/%s/%s/c-%s-%s.flv', $y, $m, $d, $workload, $highlightInfo['channel']);
                    $remotePreviewPath = sprintf('highlight/preview/%s/%s/%s/c-%s-%s-%dx%d.jpg', $y, $m, $d, $workload, $highlightInfo['channel'], $w, $h);
                    $remoteSourcePreviewPath = sprintf('highlight/preview/%s/%s/%s/c-%s-%s-source.jpg', $y, $m, $d, $workload, $highlightInfo['channel']);

                    Misc::log(sprintf('Uploading preview %s', $remotePreviewPath), Zend_Log::WARN);
                    if (file_exists($previewPath)) {
                        $return = $this->s3->putObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $remotePreviewPath,
                            'SourceFile'    => $previewPath,
                            'ACL'           => 'public-read',
                            'ContentType'   => 'image/jpeg',
                        ));

                        $return = $this->s3->putObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $remoteSourcePreviewPath,
                            'SourceFile'    => $sourcePreviewPath,
                            'ACL'           => 'public-read',
                            'ContentType'   => 'image/jpeg',
                        ));
                    } else {
                        $return = $this->s3->copyObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $remotePreviewPath,
                            'CopySource'    => sprintf('%s/previews/default.jpg', $config['aws']['s3']['bucket']['streaming']),
                            'ContentType'   => 'image/jpeg',
                            'ACL'           => 'public-read',
                        ));

                        $return = $this->s3->copyObject(array(
                            'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                            'Key'           => $remoteSourcePreviewPath,
                            'CopySource'    => sprintf('%s/previews/default.jpg', $config['aws']['s3']['bucket']['streaming']),
                            'ACL'           => 'public-read',
                            'ContentType'   => 'image/jpeg',
                        ));
                    }

//                    $this->s3->putObject(array(
//                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
//                        'Key'           => $remotePath,
//                        'SourceFile'    => $localPath,
//                        'ACL'           => 'public-read',
//                    ));

                    Misc::log(sprintf('Uploading highlight %s', $remotePath), Zend_Log::WARN);
                    $return = $this->s3->upload(
                        $config['aws']['s3']['bucket']['streaming'],
                        $remotePath,
                        fopen($localPath, 'r+'),
                        'public-read'
                    );

                    $data['uploaded_on'] = time();
                    $data['remote_path'] = $remotePath;
                    $data['preview_path'] = $remotePreviewPath;
                    $data['w'] = $w;
                    $data['h'] = $h;
                    $data['size'] = $size;

                    // Get duration
//                    $mediainfo = $config['streaming']['recording']['bin']['mediainfo'];
//                    $cmd = sprintf("%s --Inform=\"General;%%Duration%%\" %s", $mediainfo, $localPath);
//                    $lastLine = system($cmd);
//
//                    if ($lastLine && is_numeric($lastLine)) {
//                        $data['length'] = round($lastLine / 1000);
//                    }

                    $ffprobe = $config['streaming']['recording']['bin']['ffprobe'];
                    $cmd = sprintf("%s -i %s -show_format -v quiet | sed -n 's/duration=//p'", $ffprobe, $localPath);
                    $lastLine = system($cmd);

                    if ($lastLine && is_numeric($lastLine)) {
                        $data['length'] = round($lastLine);
                    }

                    // Save info
                    $this->checkStreamingDb();
                    $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);

                    $streamingBroadcastHighlightModel->update($workload, $data);

                    Misc::log('Stop highlight ' . $job->workload(), Zend_Log::WARN);
                } else {
                    Misc::log('Ignore highlight ' . $job->workload(), Zend_Log::WARN);
                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function streaming_transcode_videoAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        Misc::log('Gearman worker start: ' . $this->getRequest()->getActionName(), Zend_Log::WARN);

        $gmWorker->addFunction('streaming-transcode-video', function(GearmanJob $job) {
            $result = '';
            $config = Yaf_Registry::get('config')->toArray();

            try {
                Misc::log('Get job data: ' . $job->workload(), Zend_Log::WARN);
                $workload = json_decode($job->workload(), true);

//                $this->checkStreamingDb();

//                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

//                if ($row = $streamingChannelModel->getRow($workload['channel'], array('id', 'hash'))) {
//                    Misc::log(sprintf('Find channel info: %s', var_export($row, true)), Zend_Log::WARN);
                    $params = array();
                    $master = $config['streaming']['media']['master'];
//                    $streamKey = MySQL_Streaming_ChannelModel::makeStreamKey($row['id'], $row['hash']);
                    $streamKey = $workload['stream_key'];

                    $resolutions = Mkjogo_Streaming_Recording::getAvailableResolutions($workload['height']);

                    foreach ($resolutions as $resolution) {
                        $params[] = sprintf("-c:a copy -c:v libx264 -b:v %dK -preset ultrafast -tune zerolatency -vf scale=%d:%d -f flv rtmp://%s/pub/%s_%d", $resolution['br'], $resolution['w'], $resolution['h'], $master, $streamKey, $resolution['h']);
                    }
                    $params = implode(' ', $params);

                    // Transcode
                    $ffmpeg = $config['streaming']['recording']['bin']['ffmpeg'];
                    $cmd = sprintf("%s -rtmp_live live -i rtmp://media.nikksy.com/play/%d %s >/dev/null 2>&1 &", $ffmpeg, $workload['channel'], $params);
//                    Misc::log('Transcode command: ' . $cmd, Zend_Log::WARN);
                    $lastLine = system($cmd);
//                } else {
//                    Misc::log('Missing channel ' . $job->workload(), Zend_Log::WARN);
//                }
            } catch (Exception $e) {
                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        Misc::log('Gearman worker stop: ' . $this->getRequest()->getActionName(), Zend_Log::WARN);

        return false;
    }

    public function exchange_cardAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('exchange-card', function(GearmanJob $job) {
            $result = '';

            try {
                $workload = $job->workload();

                $this->checkStreamingDb();

                $cardRequestModel = new MySQL_Card_RequestModel($this->streamingDb);
                $cardCardModel = new MySQL_Card_CardModel($this->streamingDb);

                $this->streamingDb->beginTransaction();

                if (($requestInfo = $cardRequestModel->getRow($workload)) && !$requestInfo['processed_on']) {
                    $timestamp = time();
                    if ($code = $cardCardModel->consume($requestInfo['type'], $requestInfo['user'], $timestamp)) {
                        $cardRequestModel->update($workload, array(
                            'code'         => $code,
                            'processed_on' => $timestamp,
                        ));

                        $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);
                        $cardTypeModel->incrNumber($requestInfo['type'], -1);
                    }
                }

                $this->streamingDb->commit();
            } catch (Exception $e) {
                $this->streamingDb->rollBack();

                Misc::log($e->getMessage(), Zend_Log::ERR);
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }

    public function user_loginAction()
    {
        $gmWorker = Daemon::getGearmanWorker();

        $gmWorker->addFunction('user-login', function(GearmanJob $job) {
            $result = '';
            $config = Yaf_Registry::get('config')->toArray();
            $active = $config['wallet']['point']['award']['runapp']['active'];
            $amount = $config['wallet']['point']['award']['runapp']['amount'];

            $workload = json_decode($job->workload(), true);

            if ($active && isset($workload['client']) && in_array(strtolower($workload['client']), array('android', 'ios', 'wp'))) {
                $this->getRedisStreaming();

                $redisPointAwardRunAppModel = new Redis_Point_Award_RunAppModel($this->redisStreaming);

                if (!$redisPointAwardRunAppModel->check($workload['id'])) {
                    $this->getStreamingDb();

                    try {
                        $this->streamingDb->beginTransaction();

                        // Award
                        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
                        $pointAccountModel->incr($workload['id'], $amount);

                        // Point log
                        $pointLogModel = new MySQL_Point_LogModel($this->streamingDb);
                        $pointLogModel->insert(array(
                            'user'     => $workload['id'],
                            'number'   => $amount,
                            'type'     => MySQL_Point_LogModel::LOG_TYPE_AWARD,
                            'dealt_on' => time(),
                        ));

                        $redisPointAwardRunAppModel->mark($workload['id']);

                        $this->streamingDb->commit();
                    } catch (Exception $e) {
                        $this->streamingDb->rollBack();

                        Misc::log($e->getMessage(), Zend_Log::ERR);
                    }
                }
            }

            return $result;
        });

        while ($gmWorker->work()) {
            // Check quit
            if (!$this->validateLoggerFlag() || $this->dead()) {
                break;
            }
        };

        return false;
    }
}