<?php
class SummonerController extends ApiController
{
    const CACHE_EXPIRATION = 600;

    protected $authActions = array('update', 'play', 'playhistory', 'ac');

    protected $lolDb;

    protected $redisLOL;

    protected $cache;

    protected $lolModeAliasMap = array();

    protected $lolLeagueTierNameMap = array();

    protected $lolLeagueRankNameMap = array();

    protected function getLolDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }

        return $this->lolDb;
    }

    protected function getRedisLOL()
    {
        if (empty($this->redisLOL)) {
            $this->redisLOL = Daemon::getRedis('redis-lol', 'redis-lol');
        }

        return $this->redisLOL;
    }

    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = Daemon::getMemcached('memcached-front', 'memcached-front');
        }

        return $this->cache;
    }

    protected function getLOLModeAliasMap()
    {
        if (!$this->lolModeAliasMap) {
            $model = new MySQL_LOL_ModeModel($this->getLolDb());

            foreach ($model->getModeMap() as $row) {
                if ($row['alias']) {
                    $this->lolModeAliasMap[$row['alias']] = $row;
                }
            }
        }

        return $this->lolModeAliasMap;
    }

    protected function getLOLLeagueTierNameMap()
    {
        if (!$this->lolLeagueTierNameMap) {
            $model = new MySQL_LOL_LeagueTierModel($this->getLolDb());

            foreach ($model->getLeagueTierMap() as $row) {
                $this->lolLeagueTierNameMap[$row['name']] = $row['id'];
            }
        }

        return $this->lolLeagueTierNameMap;
    }

    protected function getLOLLeagueRankNameMap()
    {
        if (!$this->lolLeagueRankNameMap) {
            $model = new MySQL_LOL_LeagueRankModel($this->lolDb);

            foreach ($model->getLeagueRankMap() as $row) {
                $this->lolLeagueRankNameMap[$row['name']] = $row['id'];
            }
        }

        return $this->lolLeagueRankNameMap;
    }

    protected function getPlatforms()
    {
        $lolPlatformModel = new MySQL_LOL_PlatformModel($this->getLolDb());

        return $lolPlatformModel->getAvailablePlatforms();
    }

    public function getprofilesAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 500,
        );

        $platform   = strtoupper($request->get('platform', ''));
        $ids        = array_slice(Misc::parseIds($request->get('ids', '')), 0, 20);
        $columns    = array_unique(array_filter(explode(',', trim($request->get('columns', ''), ', ')))) ?: null;

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            // Model for user
            $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
            $lolUserModel = new $lolUserModelClass($this->getLolDb());

            $rowset = $lolUserModel->getRows($ids, $columns);

            $result['code'] = 200;
            $result['data'] = array();

            foreach ($rowset as $row) {
                if (isset($row['metadata'])) {
                    $row['metadata'] = json_decode($row['metadata'], true);
                }

                $result['data'][] = $row;
            }
        }

        $this->callback($result);

        return false;
    }

    public function updateAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $platform   = strtoupper($request->get('platform', ''));
            $data       = json_decode($request->get('data', ''), true);

            if (in_array(strtolower($platform), $this->getPlatforms()) && isset($data['summonerId']) && isset($data['metadata'])) {
                $this->getLOLModeAliasMap();
                $this->getLOLLeagueTierNameMap();
                $this->getLOLLeagueRankNameMap();

                $row = $info = array();

                $summonerid = (int) $data['summonerId'];

                // Model for user
                $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
                $lolUserModel = new $lolUserModelClass($this->lolDb);

                // Check user exists
                $summoner = $lolUserModel->getRow($summonerid, array('name', 'metadata'));

                $metadata = array();

                foreach ($data['metadata'] as $key => $val) {
                    if (isset($this->lolModeAliasMap[$key])) {
                        $newKey = $this->lolModeAliasMap[$key]['name'];

                        if (isset($val['tier'])) {
                            $val['tier'] = $this->lolLeagueTierNameMap[$val['tier']];
                        }

                        if (isset($val['rank'])) {
                            $val['rank'] = $this->lolLeagueRankNameMap[$val['rank']];
                        }

                        $metadata[$newKey] = $val;
                    }
                }

                $row['metadata']     = json_encode($metadata);
                $row['level']        = (int) $data['level'];
                $row['icon_id']      = (int) $data['profileIconId'];
                $row['tunfwb']       = (int) $data['timeUntilNextFirstWinBonus'];
                $row['last_mk_user'] = $request->getPost('userid', 0);
                $row['updated_on']   = $request->getServer('REQUEST_TIME');

                if ($summoner) {
                    $oldMetadata = json_decode($summoner['metadata'], true) ?: array();

                    foreach ($oldMetadata as $key => $val) {
                        if (!isset($metadata[$key])) {
                            $metadata[$key] = $val;
                        } else if (!$metadata[$key]['losses']) {
                            $metadata[$key]['losses'] = $val['losses'];
                        }
                    }
                    $row['metadata'] = json_encode($metadata);

                    // Do update
                    $lolUserModel->update($summonerid, $row);

                    $row['id']      = $summonerid;
                    $row['name']    = $data['summonerName'];
                } else {
                    // Do insert
                    $row['id']      = $summonerid;
                    $row['name']    = $data['summonerName'];

                    $lolUserModel->insert($row);

                    // Send job to index user name
                    $gearmanClient = Daemon::getGearmanClient();
                    $gearmanClient->doBackground('lol-index-user-name', json_encode(array(
                        'platform'  => $platform,
                        'id'        => $row['id'],
                        'name'      => $row['name'],
                    )));
                }

                $result['code']   = 200;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function playAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $mkuser         = $request->getPost('userid', 0);
            $platform       = strtoupper($request->get('platform', ''));
            $summonerID     = $request->get('summonerID', '');
            $summonerName   = $request->get('summonerName', '');

            if (in_array(strtolower($platform), $this->getPlatforms())) {
                $redisLOLSummonerPlayModel = new Redis_LOL_Summoner_PlayModel($this->getRedisLOL());
                $redisLOLSummonerPlayModel->update($mkuser, $platform, $summonerID, $summonerName);

                $result['code'] = 200;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function playhistoryAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $mkuser     = $request->getPost('userid', 0);

            $redisLOLSummonerPlayModel = new Redis_LOL_Summoner_PlayModel($this->getRedisLOL());
            $result['data'] = $redisLOLSummonerPlayModel->getHistory($mkuser);

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function searchAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform = strtoupper($request->get('platform', ''));
        $q        = $request->get('q', '');

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        if (in_array(strtolower($platform), $this->getPlatforms()) && $q) {
            $opts = array(
                'max_matches'   => 1000,
            );

            $params = array(
                'select'    => '`id`',
                'q'         => trim($this->getLOLDb()->quote(sprintf('^%s*', $q)), "'"),
                'sort'      => '`name` ASC',
                'offset'    => $offset,
                'limit'     => $limit,
            );

            $sphinxqlLOLUserNameModelClass = sprintf('SphinxQL_LOL_User_Name_%sModel', $platform);
            $sphinxqlLOLUserNameModel = new $sphinxqlLOLUserNameModelClass(Daemon::getSphinxQL('sphinxql-lol-rt', 'sphinxql-lol-rt'));

            $rowset = $sphinxqlLOLUserNameModel->search($params, $opts);

            $result['code']         = 200;
            $result['data']         = array();
            $result['total_found']  = (int) $rowset['total'];
            $result['page_count']   = ceil($result['total_found'] / $limit);

            if ($result['total_found']) {
                $ids = array();

                foreach ($rowset['matches'] as $val) {
                    $ids[] = $val['id'];
                }

                $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
                $lolUserModel = new $lolUserModelClass($this->getLolDb());

                $rowset = $lolUserModel->getRows($ids, array(
                    'id',
                    'name',
                    'level',
                    'icon_id',
                ));

                foreach ($rowset as $val) {
                    $result['data'][] = $val;
                }
            }
        }

        $this->callback($result);

//        $params = array(
//            'q'         => $request->get('name', ''),
//            'platform'  => $request->get('platform', ''),
//            'offset'    => $offset,
//            'limit'     => $limit,
//        );
//
//        $response = Misc::curlPost('//search.mkjogo.com/lol/user/simple', $params);
//        $response = json_decode($response, true);
//
//        if ($response) {
//            $result = array(
//                'code'          => 200,
//                'data'          => $response['matches'],
//                'total_found'   => $response['total'],
//                'page_count'    => ceil($response['total'] / $limit),
//            );
//        }
//
//        echo json_encode($result);

        return false;
    }

    public function exactAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform = strtoupper($request->get('platform', ''));
        $q        = $request->get('q', '');

        if (in_array(strtolower($platform), $this->getPlatforms()) && $q) {
            $sphinxqlLOLUserNameModelClass = sprintf('SphinxQL_LOL_User_Name_%sModel', $platform);
            $sphinxqlLOLUserNameModel = new $sphinxqlLOLUserNameModelClass(Daemon::getSphinxQL('sphinxql-lol-rt', 'sphinxql-lol-rt'));

            $result['code'] = 200;
            $result['data'] = array();

            if ($summonerid = $sphinxqlLOLUserNameModel->exact($q)) {
                $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
                $lolUserModel = new $lolUserModelClass($this->getLolDb());

                $data = $lolUserModel->getRow($summonerid, array(
                    'id',
                    'name',
                    'level',
                    'icon_id',
                    'tunfwb',
                    'metadata',
                ));

                if (isset($data['metadata'])) {
                    $data['metadata'] = json_decode($data['metadata'], true);
                }

                $result['data'] = $data;
            }

        }

        $this->callback($result);

        return false;
    }

    public function recentmatchesAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $summoner   = $request->get('summoner', 0);
        $platform   = strtoupper($request->get('platform', ''));

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $redisLOLSummonerRecentMatchModelClass = sprintf('Redis_LOL_Summoner_RecentMatch_%sModel', $platform);
            $redisLOLSummonerRecentMatchModel = new $redisLOLSummonerRecentMatchModelClass($this->getRedisLOL());

            $total = $redisLOLSummonerRecentMatchModel->len($summoner);
            $games = $redisLOLSummonerRecentMatchModel->range($summoner, $offset, $offset + $limit - 1);

            if ($games) {
                $games = array_map('intval', $games);

                $mongoLOLMatchModelClass = sprintf('Mongo_LOL_Match_%sModel', $platform);
                $mongoLOLMatchModel = new $mongoLOLMatchModelClass;

                $data = $mongoLOLMatchModel->getRows($games, null, array(
                    'gamestarttime'   => -1,
                ));

                $result = array(
                    'data'          => $data,
                    'total_found'   => $total,
                    'page_count'    => ceil($total / $limit),
                );
            } else {
                $result = array(
                    'data'          => array(),
                    'total_found'   => 0,
                    'page_count'    => 0,
                );
            }

            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

    public function summarybychampionAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $summoner   = $request->get('summoner', 0);
        $champion   = $request->get('champion', 0);

        if (in_array(strtolower($platform), $this->getPlatforms()) && $summoner && $champion) {
            $lolChapionSummonerRankingWeeklyModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), strtolower($platform), $champion);

            $result['code'] = 200;
            $result['data'] = $lolChapionSummonerRankingWeeklyModel->getRow($summoner, array(
                'total_matches',
                'win',
                'win_rate',
                'k',
                'd',
                'a',
                'rank',
            ));
        }

        $this->callback($result);

        return false;
    }

    public function summarybymultichampionsAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $summoner   = $request->get('summoner', 0);

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $summoner,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms()) && $summoner) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {

                    $lolOriginalChampionsModel = new MySQL_LOL_Original_Champions_enUSModel($this->getLolDb());
                    $champions = $lolOriginalChampionsModel->getIds();

                    $lolChapionSummonerRankingWeeklyModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), strtolower($platform), 0);
                    $data = $lolChapionSummonerRankingWeeklyModel->getMultiByRank($summoner, $champions, array(
                        'total_matches',
                        'win',
                        'win_rate',
                        'k',
                        'd',
                        'a',
                        'rank',
                        'champion',
                    ));

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function recentmatchesbychampionAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $summoner   = $request->get('summoner', 0);
        $champion   = $request->get('champion', 0);

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        if (in_array(strtolower($platform), $this->getPlatforms()) && $summoner && $champion) {
            $lolChapionSummonerRankingWeeklyModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), strtolower($platform), $champion);

            $rowset = $lolChapionSummonerRankingWeeklyModel->getRow($summoner, array(
                'games',
            ));

            if ($rowset) {
                $games = explode(',', $rowset['games']);

                $total = count($games);
                $games = array_slice($games, $offset, $limit);

                if ($games) {
                    $games = array_map('intval', $games);

                    $mongoLOLMatchModelClass = sprintf('Mongo_LOL_Match_%sModel', $platform);
                    $mongoLOLMatchModel = new $mongoLOLMatchModelClass;

                    $data = $mongoLOLMatchModel->getRows($games, null, array(
                        'gamestarttime'   => -1,
                    ));

                    $result = array(
                        'code'          => 200,
                        'data'          => $data,
                        'total_found'   => $total,
                        'page_count'    => ceil($total / $limit),
                    );
                }
            }
        }

        $this->callback($result);

        return false;
    }

    public function popularitemsbychampionAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $summoner   = $request->get('summoner', 0);
        $champion   = $request->get('champion', 0);

        $page = 1;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 12;
        $offset = ($page - 1) * $limit;

        if (in_array(strtolower($platform), $this->getPlatforms()) && $summoner && $champion) {
            $lolChapionSummonerRankingWeeklyModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), strtolower($platform), $champion);

            $rowset = $lolChapionSummonerRankingWeeklyModel->getRow($summoner, array(
                'items',
            ));

            if ($rowset) {
                if (!$rowset['items']) {
                    $workload = array(
                        'platform'  => $platform,
                        'summoner'  => $summoner,
                        'champion'  => $champion,
                    );

                    // Send job
                    $gearmanClient = Daemon::getGearmanClient();
                    $items = $gearmanClient->doNormal('lol-summoner-calculate-popular-items-by-champion', json_encode($workload));

                    if ($items) {
                        $lolChapionSummonerRankingWeeklyModel->update($summoner, array(
                            'items' => $items,
                        ));

                        $items = json_decode($items, true);
                    }
                } else {
                    $items = json_decode($rowset['items'], true);
                }

                $items = array_slice($items, $offset, $limit, true);

                $result = array(
                    'code'  => 200,
                    'data'  => $items,
                );
            }
        }

        $this->callback($result);

        return false;
    }

    public function acAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $redisLOLSummonerAntiCheatingStatusModel = new Redis_LOL_Summoner_AntiCheating_StatusModel();
            if (!($status = $redisLOLSummonerAntiCheatingStatusModel->get($userid))) {
                if ($status = $redisLOLSummonerAntiCheatingStatusModel->randomUpdate($userid)) {
                    $redisLOLSummonerAntiCheatingLogModel = new Redis_LOL_Summoner_AntiCheating_LogModel();
                    $redisLOLSummonerAntiCheatingLogModel->update($userid);
                }
            }

            $result['data'] = $status ? true : false;

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

}