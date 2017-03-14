<?php
class AnnouncementController extends ApiController
{
    const CACHE_EXPIRATION = 3600;

    protected $authActions = array();

    protected $mkjogoDb;

    protected $cache;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = Daemon::getMemcached('memcached-front', 'memcached-front');
        }

        return $this->cache;
    }

    protected function getLatestAnnouncement()
    {
        $data = array();
        $request = $this->getRequest();
        $client = $request->getActionName();

        if ($lang = $request->get('lang')) {
            $cacheKey = Misc::cacheKey(array(
                $request->getControllerName(),
                $client,
                $lang,
            ));

            if ($this->getCache() && !($data = $this->cache->get($cacheKey))) {
                if ($this->cache->getResultCode() == Memcached::RES_NOTFOUND) {
                    $announcementModel = new MySQL_Mkjogo_AnnouncementModel($this->getMkjogoDb());
                    $row = $announcementModel->latest($client, $lang);

                    if ($row) {
                        $data = array(
                            'id'            => $row['id'],
                            'url'           => $row['url'],
                            'published_on'  => $row['published_on'],
                        );

                        $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
                    }
                }
            }
        }

        return $data;
    }

    public function lolAction()
    {
        $result = array(
            'code'  => 500,
        );

        if ($data = $this->getLatestAnnouncement()) {
            $result['data'] = $data;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function hsAction()
    {
        $result = array(
            'code'  => 500,
        );

        if ($data = $this->getLatestAnnouncement()) {
            $result['data'] = $data;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}