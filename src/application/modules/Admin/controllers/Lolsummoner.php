<?php
class LolsummonerController extends AdminController
{
    protected $authActions = array(
        'view'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'search'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $lolDb;

    protected $lolChampionsNameMap = array();

    protected $lolModeNameMap = array();

    protected $lolPlatformNameMap = array();

    protected function getLOLDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }
        return $this->lolDb;
    }

    protected function getLOLChampionsNameMap()
    {
        if (!$this->lolChampionsNameMap) {

            $model = new MySQL_LOL_Original_Champions_enUSModel($this->getLolDb());

            $sql = "SELECT `id`,`name` FROM `%s` ORDER BY `name` ASC";

            $stmt = $this->lolDb->query(sprintf($sql, $model->getTableName()));

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->lolChampionsNameMap[$row['id']] = $row['name'];
            }
        }

        return $this->lolChampionsNameMap;
    }

    protected function getLOLModeNameMap()
    {
        if (!$this->lolModeNameMap) {

            $model = new MySQL_LOL_ModeModel($this->getLolDb());

            $sql = "SELECT `id`,`name` FROM `%s` WHERE `is_index` > 0";

            $stmt = $this->lolDb->query(sprintf($sql, $model->getTableName()));

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->lolModeNameMap[$row['id']] = $row['name'];
            }
        }

        return $this->lolModeNameMap;
    }

    protected function getLOLPlatformNameMap()
    {
        if (!$this->lolPlatformNameMap) {
            $model = new MySQL_LOL_PlatformModel($this->getLOLDb());

            foreach ($model->getPlatformMap() as $row) {
                $this->lolPlatformNameMap[$row['abbr']] = $row['name'];
            }
        }

        return $this->lolPlatformNameMap;
    }

    public function viewAction()
    {
        $request = $this->getRequest();

        $platform = strtoupper($request->get('platform', ''));
        $summoner = $request->get('summoner', 0);

        $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
        $lolUserModel = new $lolUserModelClass($this->getLOLDb());

        $data = $lolUserModel->getRow($summoner);
        if (isset($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }

        $this->getView()->assign(array(
            'platforms' => $this->getLOLPlatformNameMap(),
            'champions' => $this->getLOLChampionsNameMap(),
            'modes'     => $this->getLOLModeNameMap(),
            'platform'  => $platform,
            'summoner'  => $summoner,
            'data'      => $data,
        ));
    }

    public function searchAction()
    {
        $result = $rowset = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 50;
        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        if ($platform = strtoupper($request->get('platform', ''))) {
            $filter['platform'] = $platform;
        }

        if ($q = $request->get('q', '')) {
            $filter['q'] = $q;
        }

        if ($platform && $q) {
            $opts = array(
                'max_matches'   => 1000,
            );

            $params = array(
                'select'    => '`id`,`name`',
                'q'         => trim($this->getLOLDb()->quote(sprintf('^%s*', $q)), "'"),
                'sort'      => '`name` ASC',
                'offset'    => $offset,
                'limit'     => $limit,
            );

            $sphinxqlLOLUserNameModelClass = sprintf('SphinxQL_LOL_User_Name_%sModel', $platform);
            $sphinxqlLOLUserNameModel = new $sphinxqlLOLUserNameModelClass(Daemon::getSphinxQL('sphinxql-lol-rt', 'sphinxql-lol-rt'));

            $rowset = $sphinxqlLOLUserNameModel->search($params, $opts);

            $result['data']         = $rowset['matches'];
            $result['total_found']  = (int) $rowset['total_found'];
            $result['page_count']   = ceil($result['total_found'] / $limit);
            $result['pageUrlPattern'] = '/admin/lolsummoner/search?' . http_build_query($filter);

            $paginator = Zend_Paginator::factory($result['total_found']);
            $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($limit)
                ->setPageRange(10);
            $result['paginator'] = $paginator;
        }

        $result['filter'] = $filter;

        $this->getView()->assign($result);

        $this->getView()->assign(array(
            'platforms' => $this->getLOLPlatformNameMap(),
        ));
    }

    public function rankAction()
    {
        $result = $rowset = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 50;
        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        if ($platform = strtoupper($request->get('platform', ''))) {
            $filter['platform'] = $platform;
        }

        if ($champion = $request->get('champion', 0)) {
            $filter['champion'] = $champion;
        }

        if ($platform && $champion) {
//            $opts = array(
//                'max_matches'   => 1000,
//            );
//
//            $params = array(
//                'select'    => '`id`,`name`',
//                'q'         => trim($this->getLOLDb()->quote(sprintf('^%s*', $q)), "'"),
//                'sort'      => '`name` ASC',
//                'offset'    => $offset,
//                'limit'     => $limit,
//            );
//
//            $sphinxqlLOLUserNameModelClass = sprintf('SphinxQL_LOL_User_Name_%sModel', $platform);
//            $sphinxqlLOLUserNameModel = new $sphinxqlLOLUserNameModelClass(Daemon::getSphinxQL('sphinxql-lol-rt', 'sphinxql-lol-rt'));
//
//            $rowset = $sphinxqlLOLUserNameModel->search($params, $opts);
            $lolChampionSummonerRankingModel = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), $platform, $champion);
            $result = $lolChampionSummonerRankingModel->search('*', '`rank`>0', '`rank` ASC', $offset, $limit);

            $result['pageUrlPattern'] = '/admin/lolsummoner/rank?' . http_build_query($filter);

            $paginator = Zend_Paginator::factory($result['total_found']);
            $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($limit)
                ->setPageRange(10);
            $result['paginator'] = $paginator;
        }

        $result['filter'] = $filter;

        $this->getView()->assign($result);

        $this->getView()->assign(array(
            'platforms' => $this->getLOLPlatformNameMap(),
            'champions' => $this->getLOLChampionsNameMap(),
        ));
    }
}