<?php
class ChartController extends ApiController
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

    public function mostpopularAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $mode       = $request->get('mode', 0);
        $limit      = $request->get('limit', 0) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $mode,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
                    $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

                    $data = $reportLOLChampionWeeklyModel->getMostPopular($mode, $limit);

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function leastpopularAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $mode       = $request->get('mode', 0);
        $limit      = $request->get('limit', 0) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $mode,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
                    $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

                    $data = $reportLOLChampionWeeklyModel->getLeastPopular($mode, $limit);

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function highestwinrateAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $mode       = $request->get('mode', 0);
        $limit      = $request->get('limit', 0) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $mode,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
                    $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

                    $data = $reportLOLChampionWeeklyModel->getHighestWinRate($mode, $limit);

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function lowestwinrateAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $mode       = $request->get('mode', 0);
        $limit      = $request->get('limit', 0) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $mode,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
                    $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

                    $data = $reportLOLChampionWeeklyModel->getLowestWinRate($mode, $limit);

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function mostpickedAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $mode       = $request->get('mode', 0);
        $limit      = $request->get('limit', 0) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $mode,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
                    $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

                    $data = $reportLOLChampionWeeklyModel->getMostPicked($mode, $limit);

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function mostbannedAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $mode       = $request->get('mode', 0);
        $limit      = $request->get('limit', 0) ?: 10;

        $cacheKey   = Misc::cacheKey(array(
            $request->getActionName(),
            $platform,
            $mode,
            $limit,
        ));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $data = array();

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
                    $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->getMkjogoDb());

                    $data = $reportLOLChampionWeeklyModel->getMostBanned($mode, $limit);

                    $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                }
            }

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }
}