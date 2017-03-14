<?php
class LolController extends CliController
{
    protected $authActions = array(
        'convert_sqlite_to_mysql'                   => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'restart_match_collect_worker'              => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'check_zombie_match_data'                   => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'create_champion_summoner_ranking_tables'   => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update_champion_summoner_ranking_tables'   => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $mkjogoDb;

    protected $sqliteDb;

    protected $redisLOL;

    protected $lolDb;

    protected $sphinxLolRtDb;

    protected $sphinxLolPlainDb;

    protected $redisLOLMatchCollect;

    protected $lolChampionsNameMap = array();

    protected $lolModeNameMap = array();

    protected $platforms = array();

    protected $lolMapMap = array();

    protected $langs = array('en_US', 'ko_KR', 'pt_BR');

    protected function getLolDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }

        return $this->lolDb;
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
                $this->lolChampionsNameMap[$row['name']] = $row['id'];
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

    protected function getSqliteDb($lang)
    {
        $result = null;

        $key = 'sqlite_' . $lang;

        if (!($result = Yaf_Registry::get($key))) {
            $result = new PDO("sqlite:/tmp/gameStats_{$lang}.sqlite");
            $result->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $result;
    }

    protected function sqliteToMysql($lang, $table)
    {
        $result = 0;

        $mysqlTable = $table . '_' . strtr($lang, array('_' => ''));

        $dbSqlite = $this->getSqliteDb($lang);
        $dbMysql = $this->getLOLDb();

        $sqlTruncate = "TRUNCATE TABLE {$mysqlTable}";
        $sqlSelect = "SELECT * FROM {$table}";
        $sqlInsert = "INSERT INTO %s (%s) VALUES(%s)";

        $dbMysql->exec($sqlTruncate);

        $stmt = $dbSqlite->query($sqlSelect);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $fields = $values = array();

            foreach ($row as $key => $val) {
                $fields[] = MySQL_BaseModel::quoteIdentifier($key);
                $values[] = $dbMysql->quote($val);
            }

            $fields = implode(',', $fields);
            $values = implode(',', $values);

            $dbMysql->exec(sprintf($sqlInsert, $mysqlTable, $fields, $values));

            $result += 1;
        }

        return $result;
    }

    public function init()
    {
        parent::init();

        set_time_limit(0);
    }

    public function convert_sqlite_to_mysqlAction()
    {
        try {
            foreach ($this->langs as $lang) {
                printf("Update %s.%s %d\n", $lang, 'championAbilities', $this->sqliteToMysql($lang, 'championAbilities'));
                printf("Update %s.%s %d\n", $lang, 'championItems', $this->sqliteToMysql($lang, 'championItems'));
                printf("Update %s.%s %d\n", $lang, 'championSearchTags', $this->sqliteToMysql($lang, 'championSearchTags'));
                printf("Update %s.%s %d\n", $lang, 'championSkins', $this->sqliteToMysql($lang, 'championSkins'));
                printf("Update %s.%s %d\n", $lang, 'champions', $this->sqliteToMysql($lang, 'champions'));
                printf("Update %s.%s %d\n", $lang, 'itemCategories', $this->sqliteToMysql($lang, 'itemCategories'));
                printf("Update %s.%s %d\n", $lang, 'itemItemCategories', $this->sqliteToMysql($lang, 'itemItemCategories'));
                printf("Update %s.%s %d\n", $lang, 'itemRecipes', $this->sqliteToMysql($lang, 'itemRecipes'));
                printf("Update %s.%s %d\n", $lang, 'items', $this->sqliteToMysql($lang, 'items'));
                printf("Update %s.%s %d\n", $lang, 'keybindingCategories', $this->sqliteToMysql($lang, 'keybindingCategories'));
                printf("Update %s.%s %d\n", $lang, 'keybindingEvents', $this->sqliteToMysql($lang, 'keybindingEvents'));
                printf("Update %s.%s %d\n", $lang, 'searchTags', $this->sqliteToMysql($lang, 'searchTags'));

                echo "========\n";
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }

    public function restart_match_collect_workerAction()
    {
        $platform = $this->getRequest()->get('platform', '');

        $redisModelClass = sprintf('Redis_LOL_Match_%sModel', strtoupper($platform));
        $redisModel = new $redisModelClass($this->getRedisLOLMatchCollect());

        $matches = $redisModel->getQueue($platform);

        foreach ($matches as $val) {
            $data = explode(':', $val);
            $gameid = array_pop($data);

            $workload = array(
                'platform'  => $platform,
                'gameid'    => $gameid,
            );

            // Send job
            $gearmanClient = Daemon::getGearmanClient();
            $gearmanClient->doBackground('lol-match-collect', json_encode($workload));
        }

        return false;
    }

    public function check_zombie_match_dataAction()
    {
        $platform = $this->getRequest()->get('platform', '');

        $redisModelClass = sprintf('Redis_LOL_Match_%sModel', strtoupper($platform));
        $redisModel = new $redisModelClass($this->getRedisLOLMatchCollect());

        $redis = $this->getRedisLOLMatchCollect();
        $keys = $redis->keys(sprintf('lol:match:%s:*', strtolower($platform)));

        foreach ($keys as $val) {
            $data = explode(':', $val);
            $gameid = array_pop($data);

            $workload = array(
                'platform'  => $platform,
                'gameid'    => $gameid,
            );

            // Send job
            $gearmanClient = Daemon::getGearmanClient();
            $gearmanClient->doBackground('lol-match-collect', json_encode($workload));
        }

        return false;
    }

    public function create_champion_summoner_ranking_tablesAction()
    {
        $this->getLOLDb();

        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `%s` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `league_tier` tinyint(4) NOT NULL DEFAULT '0',
  `league_rank` tinyint(4) NOT NULL DEFAULT '0',
  `total_matches` int(11) NOT NULL DEFAULT '0',
  `win` int(11) NOT NULL DEFAULT '0',
  `win_rate` float NOT NULL DEFAULT '0',
  `k` int(11) NOT NULL DEFAULT '0',
  `d` int(11) NOT NULL DEFAULT '0',
  `a` int(11) NOT NULL DEFAULT '0',
  `items` varchar(255) NOT NULL DEFAULT '',
  `games` TEXT NOT NULL,
  `rank` int(11) NOT NULL DEFAULT '0',
  `date` int(11) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT;

        $platforms = array('br1', 'eun1', 'euw1', 'kr', 'la1', 'la2', 'na1', 'oc1', 'ru', 'tr1', 'wt1');

        $championsModel = new MySQL_LOL_Original_Champions_enUSModel($this->getLOLDb());
        $champions = $championsModel->getChampionsMap();

        foreach ($platforms as $platform) {
            foreach ($champions as $champion) {
                try {
                    $rankingModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLOLDb(), $platform, $champion['id']);
                    $this->getLOLDb()->exec(sprintf($sql, $rankingModel->getTableName()));

                    printf("%s\t%s\n", $platform, $rankingModel->getTableName());
                } catch (Exception $e) {
                    printf("%s\t%s\n", $platform, $e->getMessage());
                }
            }
        }

        return false;
    }

    public function update_champion_summoner_ranking_tablesAction()
    {
        $this->getLOLDb();

        $sql    = "ALTER TABLE `%s`.`%s` ADD  `last_rank` INT NOT NULL DEFAULT '0' AFTER `rank` ";

        $platforms = array('wt1', 'oc1', 'ru', 'la1', 'la2', 'na1', 'br1', 'tr1', 'eun1', 'euw1', 'kr');

        $champions = $this->getLOLChampionsNameMap();

        foreach ($platforms as $platform) {
            foreach ($champions as $champion) {
                try {
                    $rankingModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLOLDb(), $platform, $champion);

                    $this->getLOLDb()->exec(sprintf($sql, $rankingModel->getSchema(), $rankingModel->getTableName()));

                    printf("%s\t%s\n", $platform, $rankingModel->getTableName());
                } catch (Exception $e) {
                    printf("%s\t%s\n", $platform, $e->getMessage());
                }
            }
        }

        return false;
    }

    public function clear_match_manuallyAction()
    {
        $platform = $this->getRequest()->get('platform', '');

        try {
            $redis = $this->getRedisLOLMatchCollect();

            $redisModelClass = sprintf('Redis_LOL_Match_%sModel', strtoupper($platform));
            $redisModel = new $redisModelClass($redis);

            $matches = $redisModel->getQueue();

            $counter = 0;

            foreach ($matches as $val) {
                $data = explode(':', $val);
                $gameid = array_pop($data);

                $redisModel->remove($gameid);
                $counter++;
            }

            printf("Remove from list %d\n", $counter);

            $counter = 0;

            $keys = $redis->keys(sprintf('lol:match:%s:*', strtolower($platform)));

            foreach ($keys as $key) {
                $redis->del($key);
                $counter++;
            }

            printf("Delete zombie %d\n", $counter);
        } catch (Exception $e) {
            Debug::dump($e->getMessage());
        }

        return false;
    }

    public function do_match_collect_manuallyAction()
    {
        $platform = $this->getRequest()->get('platform', '');

        $redisModelClass = sprintf('Redis_LOL_Match_%sModel', strtoupper($platform));
        $redisModel = new $redisModelClass($this->getRedisLOLMatchCollect());

        $matches = $redisModel->getQueue();

        $counter = 0;

        foreach ($matches as $val) {
            $data = explode(':', $val);
            $gameid = array_pop($data);

            $this->process_match($platform, $gameid);

            $counter += 1;

            if ($counter > 20) {
                break;
            }
        }

        return false;
    }

    protected function process_match($platform, $gameid)
    {
        try {
            $platform = strtoupper($platform);

            $this->checkLolDb();

            $this->getLOLChampionsNameMap();

            $this->getLOLModeNameMap();

            $this->getPlatforms();

            // Get Gearman client
            $gearmanClient = Daemon::getGearmanClient();

            $redisLOLMatchModelClass = sprintf('Redis_LOL_Match_%sModel', $platform);
            $redisLOLMatchModel = new $redisLOLMatchModelClass($this->getRedisLOLMatchCollect());

            // Check platform
            if (!in_array(strtolower($platform), $this->platforms)) {
                Misc::log(sprintf('Invalid LOL platform: *%s*', $platform), Zend_Log::WARN);
                $redisLOLMatchModel->remove($gameid);

                return false;
            }

            // Retrieve raw data
            $rawdata = $redisLOLMatchModel->getData($gameid);

            Misc::log('Retrieve raw data ok', Zend_Log::DEBUG);

            if (!$rawdata) {
                Misc::log(sprintf('Invalid LOL match data (%s): %s', $platform . ':' . $gameid, var_export($rawdata, true)), Zend_Log::WARN);
                $redisLOLMatchModel->remove($gameid);

                return false;
            }

            if (!($data = json_decode($rawdata, true))) {
                Misc::log(sprintf('Invalid LOL match JSON (%s): %s %s', $platform . ':' . $gameid, json_last_error_msg(), var_export($rawdata, true)), Zend_Log::WARN);
                $redisLOLMatchModel->remove($gameid);

                return false;
            }

            // Check gametype
            if ($data['gametype'] != Mongo_LOL_Match_BaseModel::GAMETYPE_MATCHED_GAME) {
                Misc::log(sprintf('Invalid LOL game type (%s): %s', $platform . ':' . $gameid,  $data['gametype']), Zend_Log::INFO);
                $redisLOLMatchModel->remove($gameid);

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
                $redisLOLMatchModel->remove($gameid);

                return false;
            }

            Misc::log('Save raw data to MongoDB ok', Zend_Log::DEBUG);

            if (isset($data['queuetype']) && isset($this->lolModeNameMap[$data['queuetype']])) {
                $mode = $this->lolModeNameMap[$data['queuetype']];
            } else {
                Misc::log('Invalid LOL queue type: ' . $data['queuetype'], Zend_Log::NOTICE);
                $redisLOLMatchModel->remove($gameid);

                return false;
            }

            Misc::log('Check queue type ok', Zend_Log::DEBUG);

            $map = isset($data['mkdata']['mapId']) ? $this->getLOLMapAlias($data['mkdata']['mapId']) : 0;

            $players = array();
            $vsAI = false;

            if (isset($data['selfteamplayers']) && isset($data['otherteamplayers'])) {
                foreach (array_merge($data['selfteamplayers'], $data['otherteamplayers']) as $val) {
                    // Exclude bots
                    if (!$val['botPlayer']) {
                        $players[(int) $val['userid']] = $val;
                    } else {
                        $vsAI = true;
                    }
                }
            } else {
                Misc::log('Broken data, no team players', Zend_Log::WARN);
                $redisLOLMatchModel->remove($gameid);

                return false;
            }

            Misc::log('Get team players ok', Zend_Log::DEBUG);

            // Save recent match of each players
            $redisLOLSummonerRecentMatchModelClass = sprintf('Redis_LOL_Summoner_RecentMatch_%sModel', $platform);
            $redisLOLSummonerRecentMatchModel = new $redisLOLSummonerRecentMatchModelClass($this->getRedisLOL());

            foreach ($players as $key => $val) {
                $redisLOLSummonerRecentMatchModel->update($key, (int) $gameid);
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
                $userid = (int) $player['userid'];

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
                $redisLOLMatchModel->remove($gameid);

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
                    'win'           => isset($maindata['WIN']) && $maindata['WIN'] ? 1 : 0,
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
            $redisLOLMatchModel->remove($gameid);

            Misc::log('Good job: ' . $gameid, Zend_Log::INFO);
        } catch (Exception $e) {
            Misc::log($e->getMessage(), Zend_Log::ERR);
            $redisLOLMatchModel->remove($gameid);

//                throw new Exception($e);
        }

        return false;
    }

    public function recalculate_rankingAction()
    {
        $platforms = array('wt1', 'oc1', 'ru', 'la1', 'la2', 'na1', 'br1', 'tr1', 'eun1', 'euw1', 'kr');
        $champions = $this->getLOLChampionsNameMap();

        foreach ($platforms as $platform) {
            $platform = strtoupper($platform);
            $baseline = strcmp($platform, 'KR') === 0 ? 15 : 5;

            printf("%s ... ", $platform);

            foreach ($champions as $champion) {
                $model = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->lolDb, strtolower($platform), $champion);
                $model->calculateRank($baseline);
            }

            printf("ok\n");
        }

        return false;
    }

    public function validate_tablesAction()
    {
        $platforms = array('wt1', 'oc1', 'ru', 'la1', 'la2', 'na1', 'br1', 'tr1', 'eun1', 'euw1', 'kr');

        $this->getLolDb();

        foreach ($platforms as $platform) {
            $platform = strtoupper($platform);

            $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
            $lolUserModel = new $lolUserModelClass($this->lolDb);
            printf("%s\n", $lolUserModel->ping());

            $lolMatchModelClass = sprintf('MySQL_LOL_Match_%sModel', $platform);
            $lolMatchModel = new $lolMatchModelClass($this->lolDb);
            printf("%s\n", $lolMatchModel->ping());

            $lolChampionPickBanModelClass = sprintf('MySQL_LOL_Champion_PickBan_%sModel', $platform);
            $lolChampionPickBanModel = new $lolChampionPickBanModelClass($this->lolDb);
            printf("%s\n", $lolChampionPickBanModel->ping());
        }

        return false;
    }

    public function clear_cacheAction()
    {
        $platforms = array('wt1', 'oc1', 'ru', 'la1', 'la2', 'na1', 'br1', 'tr1', 'eun1', 'euw1', 'kr');

        $cache = Daemon::getMemcached('memcached-front', 'memcached-front');

        foreach ($platforms as $platform) {
            ;
        }

        return false;
    }

    public function get_active_user_by_langAction()
    {
        $data = array();
        $redis = Daemon::getRedis();

        $keys = $redis->keys('dau:*:*');
        foreach ($keys as $key) {
            list($prefix, $lang, $date, ) = explode(':', $key);
            $data[$date][$lang] = $redis->bitCount($key);
        }

        foreach ($data as $date => $val) {
            foreach ($val as $lang => $count) {
                printf("%s,%s,%d\n", $date, $lang, $count);
            }
        }

        return false;
    }

    public function import_match_from_mysql_to_redisAction()
    {
        $request = $this->getRequest();
        $platforms = array('br1', 'eun1', 'euw1', 'kr', 'la1', 'la2', 'na1', 'oc1', 'ru', 'tr1', 'wt1');
        $platform = $request->get('platform');
        $platform = strtoupper($platform);

        $this->getLolDb();
        $this->getRedisLOL();
/*
        $keys = $this->redisLOL->keys('lol:summoner:recentmatch:br1:*');
        foreach ($keys as $key) {
            $this->redisLOL->del($key);
        }
*/

//        foreach ($platforms as $platform) {
            printf("==== Begin %s ====\n", $platform);

            $redisLOLSummonerRecentMatchModelClass = sprintf('Redis_LOL_Summoner_RecentMatch_%sModel', $platform);
            $redisLOLSummonerRecentMatchModel = new $redisLOLSummonerRecentMatchModelClass($this->redisLOL);

            $lolMatchModelClass = sprintf('MySQL_LOL_Match_%sModel', $platform);
            $lolMatchModel = new $lolMatchModelClass($this->lolDb);

            $limit = 1000;
            $range = $lolMatchModel->getRange('id');
            $start = $range['min'];
            $end =  $range['max'];

            printf("From %d to %d\n", $start, $end);

            while ($rows = $lolMatchModel->getRowsByStep('id', $start, $end, $limit)) {
                foreach ($rows as $row) {
                    $redisLOLSummonerRecentMatchModel->update($row['user'], $row['game']);
                }

                $start = $row['id'] + 1;
            }

            printf("==== End %s ====\n", $platform);
//        }

        return false;
    }
}