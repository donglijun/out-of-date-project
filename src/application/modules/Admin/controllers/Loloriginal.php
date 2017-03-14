<?php
class LoloriginalController extends AdminController
{
    protected $authActions = array(
        'champions'         => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'abilities'         => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'items'             => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'getchampionsjs'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'getabilitiesjs'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'getitemsjs'        => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $lolDb;

    protected $lolChampionsNameMap = array();

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

    public function championsAction()
    {
        $result = $filter = $where = array();
        $request = $this->getRequest();

        $this->getLOLDb();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        if ($lang = $request->get('lang', '')) {
            $filter['lang'] = $lang;

            if ($q = $request->get('q', '')) {
                $filter['q'] = $q;
                $where[] = '`name` LIKE ' . $this->lolDb->quote('%' . $q . '%');
            }

            $where = implode(' AND ', $where);

            $lolOriginalChampionsModelClass = sprintf('MySQL_LOL_Original_Champions_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalChampionsModel = new $lolOriginalChampionsModelClass($this->lolDb);

            $result = $lolOriginalChampionsModel->search('`id`,`displayName`,`title`,`description`', $where, 'name ASC', $offset, $limit);

            $result['filter'] = $filter;
            $result['pageUrlPattern'] = '/admin/loloriginal/champions?' . http_build_query($filter);

            $paginator = Zend_Paginator::factory($result['total_found']);
            $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($limit)
                ->setPageRange(10);
            $result['paginator'] = $paginator;
        }

        $result['langs']     = MySQL_LOL_LangModel::getModel($this->lolDb)->getLangEnum();

        $this->getView()->assign($result);
    }

    public function abilitiesAction()
    {
        $result = $filter = $where = array();
        $request = $this->getRequest();

        $this->getLOLDb();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        if ($lang = $request->get('lang', '')) {
            $filter['lang'] = $lang;

            if ($champion = $request->get('champion', 0)) {
                $filter['champion'] = $champion;
                $where[] = '`championId`=' . $this->lolDb->quote($champion);
            }

            if ($q = $request->get('q', '')) {
                $filter['q'] = $q;
                $where[] = '`name` LIKE ' . $this->lolDb->quote('%' . $q . '%');
            }

            $where = implode(' AND ', $where);

            $lolOriginalChampionAbilitiesModelClass = sprintf('MySQL_LOL_Original_ChampionAbilities_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalChampionAbilitiesModel = new $lolOriginalChampionAbilitiesModelClass($this->lolDb);

            $result = $lolOriginalChampionAbilitiesModel->search('*', $where, 'id ASC', $offset, $limit);

            $result['filter'] = $filter;
            $result['pageUrlPattern'] = '/admin/loloriginal/abilities?' . http_build_query($filter);

            $paginator = Zend_Paginator::factory($result['total_found']);
            $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($limit)
                ->setPageRange(10);
            $result['paginator'] = $paginator;
        }

        $result['champions'] = $this->getLOLChampionsNameMap();
        $result['langs']     = MySQL_LOL_LangModel::getModel($this->lolDb)->getLangEnum();

        $this->getView()->assign($result);
    }

    public function itemsAction()
    {
        $result = $filter = $where = array();
        $request = $this->getRequest();

        $this->getLOLDb();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';

        if ($lang = $request->get('lang', '')) {
            $filter['lang'] = $lang;

            if ($q = $request->get('q', '')) {
                $filter['q'] = $q;
                $where[] = '`name` LIKE ' . $this->lolDb->quote('%' . $q . '%');
            }

            $where = implode(' AND ', $where);

            $lolOriginalItemsModelClass = sprintf('MySQL_LOL_Original_Items_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalItemsModel = new $lolOriginalItemsModelClass($this->lolDb);

            $result = $lolOriginalItemsModel->search('`id`,`name`,`price`,`description`', $where, 'name ASC', $offset, $limit);

            $result['filter'] = $filter;
            $result['pageUrlPattern'] = '/admin/loloriginal/items?' . http_build_query($filter);

            $paginator = Zend_Paginator::factory($result['total_found']);
            $paginator->setCurrentPageNumber($page)
                ->setItemCountPerPage($limit)
                ->setPageRange(10);
            $result['paginator'] = $paginator;
        }

        $result['langs']     = MySQL_LOL_LangModel::getModel($this->lolDb)->getLangEnum();

        $this->getView()->assign($result);
    }

    public function getchampionsjsAction()
    {
        $request = $this->getRequest();

        if ($lang = $request->get('lang', '')) {
            $this->getLOLDb();

            $lolOriginalChampionsModelClass = sprintf('MySQL_LOL_Original_Champions_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalChampionsModel = new $lolOriginalChampionsModelClass($this->lolDb);
            $data = $lolOriginalChampionsModel->formatForJS();

            $lolOriginalChampionAbilitiesModelClass = sprintf('MySQL_LOL_Original_ChampionAbilities_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalChampionAbilitiesModel = new $lolOriginalChampionAbilitiesModelClass($this->lolDb);
            $abilities = $lolOriginalChampionAbilitiesModel->groupAbilitiesByChampion();

            foreach ($data as $key => &$val) {
                if (isset($abilities[$key])) {
                    $val['abilities'] = explode(',', $abilities[$key]);
                }
            }

            Misc::httpOutputFile(array(
                'fileName'  => sprintf('champions-%s.json', strstr($lang, '_', true)),
                'raw'       => json_encode($data, JSON_PRETTY_PRINT),
            ));
        }

        return false;
    }

    public function getabilitiesjsAction()
    {
        $request = $this->getRequest();

        if ($lang = $request->get('lang', '')) {
            $this->getLOLDb();

            $lolOriginalChampionAbilitiesModelClass = sprintf('MySQL_LOL_Original_ChampionAbilities_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalChampionAbilitiesModel = new $lolOriginalChampionAbilitiesModelClass($this->lolDb);
            $data = $lolOriginalChampionAbilitiesModel->formatForJS();

            Misc::httpOutputFile(array(
                'fileName'  => sprintf('abilities-%s.json', strstr($lang, '_', true)),
                'raw'       => json_encode($data, JSON_PRETTY_PRINT),
            ));
        }

        return false;
    }

    public function getitemsjsAction()
    {
        $request = $this->getRequest();

        if ($lang = $request->get('lang', '')) {
            $this->getLOLDb();

            $lolOriginalItemsModelClass = sprintf('MySQL_LOL_Original_Items_%sModel', strtr($lang, array('_' => '')));
            $lolOriginalItemsModel = new $lolOriginalItemsModelClass($this->lolDb);
            $data = $lolOriginalItemsModel->formatForJS();

            Misc::httpOutputFile(array(
                'fileName'  => sprintf('items-%s.json', strstr($lang, '_', true)),
                'raw'       => json_encode($data, JSON_PRETTY_PRINT),
            ));
        }

        return false;
    }
}