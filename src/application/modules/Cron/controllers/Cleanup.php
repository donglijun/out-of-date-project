<?php
class CleanupController extends CliController
{
    protected $lolDb;

    protected function getLolDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }

        return $this->lolDb;
    }

    public function mysqllolmatchAction()
    {
        $request = $this->getRequest();

        $platform = strtoupper($request->get('platform', ''));

        $day = $this->getRequest()->get('day', 33);
        $day = $day > 0 ? $day * -1 : $day;

        $from = 0;
        $to   = strtotime(sprintf('%d day', $day));

        $lolMatchModelClass = sprintf('MySQL_LOL_Match_%sModel', $platform);
        $lolMatchModel = new $lolMatchModelClass($this->getLolDb());

        $count = $lolMatchModel->cleanup($from, $to);

        printf("Clear LOL match data in MySQL daily till %s: %s - %d\n", date('Y-m-d', $to), $platform, $count);

        return false;
    }

    public function mysqllolchampionpickbanAction()
    {
        $request = $this->getRequest();

        $platform = strtoupper($request->get('platform', ''));

        $day = $this->getRequest()->get('day', 33);
        $day = $day > 0 ? $day * -1 : $day;

        $from = 0;
        $to   = strtotime(sprintf('%d day', $day));

        $lolChampionPickBanModelClass = sprintf('MySQL_LOL_Champion_PickBan_%sModel', $platform);
        $lolChampionPickBanModel = new $lolChampionPickBanModelClass($this->getLolDb());

        $count = $lolChampionPickBanModel->cleanup($from, $to);

        printf("Clear LOL champion pickban data in MySQL daily till %s: %s - %d\n", date('Y-m-d', $to), $platform, $count);

        return false;
    }

    public function mongololmatchAction()
    {
        $request = $this->getRequest();

        $platform = strtoupper($request->get('platform', ''));

        $day = $this->getRequest()->get('day', 33);
        $day = $day > 0 ? $day * -1 : $day;

        $from = 0;
        $to   = strtotime(sprintf('%d day', $day));

        $mongoLOLMatchModelClass = sprintf('Mongo_LOL_Match_%sModel', $platform);
        $mongoLOLMatchModel = new $mongoLOLMatchModelClass();

        $count = $mongoLOLMatchModel->cleanup((int) $from * 1000, (int) $to * 1000);

        printf("Clear LOL match data in MongoDB daily till %s: %s - %d\n", date('Y-m-d', $to), $platform, $count);

        return false;
    }
}