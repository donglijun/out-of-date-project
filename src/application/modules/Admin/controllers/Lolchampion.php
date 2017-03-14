<?php
class LolchampionController extends AdminController
{
    protected $authActions = array(
        'weeklyreport'  => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'monthlyreport' => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $mkjogoDb;

    protected $lolDb;

    protected $lolChampionsNameMap = array();

    protected $lolModeNameMap = array();

    protected $lolPlatformNameMap = array();

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

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

    public function weeklyreportAction()
    {
        $filter = $where = $parameter = array();
        $request = $this->getRequest();

        $this->getMkjogoDb();

        if ($platform = strtoupper($request->get('platform', ''))) {
            $filter['platform'] = $platform;
        }

        if ($date = $request->get('date')) {
            $where[] = '`date`=:date';
            $filter['date'] = $date;
            $parameter[':date'] = $date;
        }

        if ($champion = $request->get('champion')) {
            $where[] = '`champion`=:champion';
            $filter['champion'] = $champion;
            $parameter[':champion'] = $champion;
        }

        if ($platform) {
            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $reportLOLChampionWeeklyModelClass = sprintf('MySQL_Report_LOL_Champion_Weekly_%sModel', $platform);
            $reportLOLChampionWeeklyModel = new $reportLOLChampionWeeklyModelClass($this->mkjogoDb);

            $sql = sprintf('SELECT * FROM %s %s ORDER BY `date`,`champion`,`mode` LIMIT 0, 1000', $reportLOLChampionWeeklyModel->getTableName(), $where);
            $stmt = $this->mkjogoDb->prepare($sql);
            $stmt->execute($parameter);

            $this->getView()->assign(array(
                'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
            ));
        }

        $this->getView()->assign(array(
            'platforms' => $this->getLOLPlatformNameMap(),
            'champions' => $this->getLOLChampionsNameMap(),
            'modes'     => $this->getLOLModeNameMap(),
            'filter'    => $filter,
            'dates'     => MySQL_Report_LOL_Champion_Weekly_BR1Model::getModel($this->mkjogoDb)->getDistinctDates(),
        ));
    }

    public function monthlyreportAction()
    {
        $filter = $where = $parameter = array();
        $request = $this->getRequest();

        if ($platform = strtoupper($request->get('platform'))) {
            $filter['platform'] = $platform;
        }

        if ($date = $request->get('date')) {
            $where[] = '`date`=:date';
            $filter['date'] = $date;
            $parameter[':date'] = $date;
        }

        if ($champion = $request->get('champion')) {
            $where[] = '`champion`=:champion';
            $filter['champion'] = $champion;
            $parameter[':champion'] = $champion;
        }

        if ($platform) {
            $where = implode(' AND ', $where);

            $this->getMkjogoDb();

            $reportLOLChampionMonthlyModelClass = sprintf('MySQL_Report_LOL_Champion_Monthly_%sModel', $platform);
            $reportLOLChampionMonthlyModel = new $reportLOLChampionMonthlyModelClass($this->mkjogoDb);

            $sql = sprintf('SELECT * FROM %s WHERE %s ORDER BY `date`,`champion`,`mode`', $reportLOLChampionMonthlyModel->getTableName(), $where);
            $stmt = $this->mkjogoDb->prepare($sql);
            $stmt->execute($parameter);

            $this->getView()->assign(array(
                'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'dates'     => $reportLOLChampionMonthlyModel->getDistinctDates(),
            ));
        }

        $this->getView()->assign(array(
            'platforms' => $this->getLOLPlatformNameMap(),
            'champions' => $this->getLOLChampionsNameMap(),
            'modes'     => $this->getLOLModeNameMap(),
            'filter'    => $filter,
        ));
    }
}