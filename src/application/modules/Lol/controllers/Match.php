<?php
class MatchController extends ApiController
{
    protected $authActions = array('check', 'collect');

    protected $lolDb;

    protected $redisLOL;

    protected $redisLOLMatchCollect;

    protected $regionToPlatformMap = array(
        'kr'    => 'kr',
        'euw'   => 'euw1',
        'eune'  => 'eun1',
        'tr'    => 'tr1',
        'br'    => 'br1',
        'na'    => 'na1',
        'las'   => 'la2',
        'lan'   => 'la1',
        'ru'    => 'ru',
        'oce'   => 'oc1',
        'cn'    => 'wt1',
    );

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

    protected function getRedisLOLMatchCollect()
    {
        if (empty($this->redisLOLMatchCollect)) {
            $this->redisLOLMatchCollect = Daemon::getRedis('redis-lol-match-collect', 'redis-lol-match-collect');
        }

        return $this->redisLOLMatchCollect;
    }

    protected function getPlatforms()
    {
        $lolPlatformModel = new MySQL_LOL_PlatformModel($this->getLolDb());

        return $lolPlatformModel->getAvailablePlatforms();
    }

    public function checkAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $platform   = $request->get('platform', '');
            $gameid     = $request->get('gameid', 0);

            if (in_array(strtolower($platform), $this->getPlatforms())) {
                try {
                    $redisModelClass = sprintf('Redis_LOL_Match_%sModel', strtoupper($platform));
                    $redisModel = new $redisModelClass($this->getRedisLOLMatchCollect());

                    $result['exists'] = $redisModel->exists($gameid);
                    $result['code']   = 200;
                } catch (Exception $e) {
                    Misc::log(sprintf('Error(*%s* %s): %s', $platform, $gameid, $e->getMessage()), Zend_Log::WARN);
                }
            } else {
                Misc::log(sprintf('Invalid platform: *%s* %s', $platform, $gameid), Zend_Log::WARN);
                $result['code'] = 404;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function collectAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $platform   = $request->get('platform', '');
            $gameid     = $request->get('gameid', 0);
            $data       = $request->get('data', '');

            if (in_array(strtolower($platform), $this->getPlatforms())) {
                $redisModelClass = sprintf('Redis_LOL_Match_%sModel', strtoupper($platform));
                $redisModel = new $redisModelClass($this->getRedisLOLMatchCollect());

                // Update queue
                if ($redisModel->update($gameid, $data)) {
                    $workload = array(
                        'platform'  => $platform,
                        'gameid'    => $gameid,
                    );

                    // Send job
                    $gearmanClient = Daemon::getGearmanClient();
                    $gearmanClient->doBackground('lol-match-collect', json_encode($workload));

                    if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                        Misc::log(sprintf("gearman job (lol-match-collect) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                    }

                    $result['code'] = 200;
                } else {
                    $result['code'] = 302;
                }
            } else {
                $result['code'] = 404;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function getAction()
    {
        $request = $this->getRequest();
        $result = array(
            'code'  => 500,
        );

        $platform   = strtoupper($request->get('platform', ''));
        $game       = (int) $request->get('game', 0);

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $mongoLOLMatchModelClass = sprintf('Mongo_LOL_Match_%sModel', $platform);
            $mongoLOLMatchModel = new $mongoLOLMatchModelClass;

            $data = $mongoLOLMatchModel->getRow($game);

            $result = array(
                'code'          => 200,
                'data'          => $data,
            );
        }

        $this->callback($result);

        return false;
    }
}