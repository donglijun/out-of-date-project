<?php
class ChampionController extends ApiController
{
    const CACHE_EXPIRATION = 600;

    protected $authActions = array();

    protected $mkjogoDb;

    protected $lolDb;

    protected $cache;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getLolDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }

        return $this->lolDb;
    }

    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = Daemon::getMemcached('memcached-front', 'memcached-front');
        }

        return $this->cache;
    }

    protected function getPlatforms()
    {
        $lolPlatformModel = new MySQL_LOL_PlatformModel($this->getLolDb());

        return $lolPlatformModel->getAvailablePlatforms();
    }

    public function summaryAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $champion   = $request->get('champion', 0);

        if (in_array(strtolower($platform), $this->getPlatforms()) && $champion) {
            $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
            $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

            $result['code'] = 200;
            $result['data'] = $reportLOLChampionWeeklyModel->getLatestSummary($champion);
        }

        $this->callback($result);

        return false;
    }

    public function topsummonersAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $champion   = $request->get('champion', 0);

        $page   = intval($request->get('page'));
        $page   = $page < 1 ? 1 : $page;
        $limit  = intval($request->get('limit', 0)) ?: 10;
        $offset = ($page - 1) * $limit;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $champion,
            $limit,
            $page,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms()) && $champion) {
            $data = $rowset = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $lolChampionSummonerRankingWeeklyModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), strtolower($platform), $champion);

                    $rowset = $lolChampionSummonerRankingWeeklyModel->search('*', '`rank`>0', '`rank` ASC', $offset, $limit);

                    $data['total_found'] = $rowset['total_found'];
                    $data['page_count']  = $rowset['page_count'];
                    $data['data']        = array();

                    foreach ($rowset['data'] as $row) {
                        $data['data'][] = $row;
                    }

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code']         = 200;
            $result['data']         = $data['data'];
            $result['total_found']  = $data['total_found'];
            $result['page_count']   = $data['page_count'];
        }

        $this->callback($result);

        return false;
    }

    public function summonerinrankAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $champion   = $request->get('champion', 0);
        $summoner   = $request->get('summoner', 0);

        $limit  = intval($request->get('limit', 0)) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $champion,
            $summoner,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms()) && $champion && $summoner) {
            $rowset = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $lolChapionSummonerRankingWeeklyModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), strtolower($platform), $champion);
                    $rowset = $lolChapionSummonerRankingWeeklyModel->getRow($summoner, array('rank'));

                    if ($rowset && ($rowset['rank'] > 0)) {
                        $page   = ceil($rowset['rank'] / $limit);
                        $offset = ($page - 1) * $limit;

                        $rowset = $lolChapionSummonerRankingWeeklyModel->search('*', '`rank`>0', '`rank` ASC', $offset, $limit);

                        $data['page']        = $page;
                        $data['total_found'] = $rowset['total_found'];
                        $data['page_count']  = $rowset['page_count'];
                        $data['data']        = array();

                        foreach ($rowset['data'] as $row) {
                            $data['data'][] = $row;
                        }
                    } else {
                        $data['data'] = array();
                    }

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code']         = 200;
            $result['data']         = $data['data'];
            $result['total_found']  = $data['total_found'];
            $result['page_count']   = $data['page_count'];
        }

        $this->callback($result);

        return false;
    }
}