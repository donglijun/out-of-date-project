<?php
class LolrankingController extends CliController
{
    protected $lolDb;

    protected $lolSphinxPlain;

    protected $platforms = array();

    protected $championSummonerRankingModels = array();

    protected function getLolDb()
    {
        if (empty($this->lolDb)) {
            $this->lolDb = Daemon::getDb('lol-db', 'lol-db');
        }

        return $this->lolDb;
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

    protected function getLolSphinxPlain()
    {
        if (empty($this->lolSphinxPlain)) {
            $this->lolSphinxPlain = Daemon::getSphinxQL('sphinxql-lol-plain', 'sphinxql-lol-plain');
        }

        return $this->lolSphinxPlain;
    }

    protected function checkLolSphinxPlain()
    {
        if (!$this->lolSphinxPlain) {
            $this->getLolSphinxPlain();
        } else {
            try {
                $this->lolSphinxPlain->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('sphinxql-lol-plain');
                $this->lolSphinxPlain = null;

                $this->getLolSphinxPlain();
            }
        }
    }

    protected function getPlatforms()
    {
        if (!$this->platforms) {
            $lolPlatformModel = new MySQL_LOL_PlatformModel($this->getLolDb());

            $this->platforms = $lolPlatformModel->getAvailablePlatforms();
        }

        return $this->platforms;
    }

    protected function getChampionSummonerRankingModel($platform, $champion)
    {
        $result = null;

        $platform = strtolower($platform);

        $key = sprintf('model-ranking-%s-%s', $platform, $champion);

        if (!($result = Yaf_Registry::get($key))) {
            $result = new MySQL_LOL_Champion_Summoner_Ranking_WeeklyModel($this->getLolDb(), $platform, $champion);

            $result->dropIndexes();

            $result->truncate();

            Yaf_Registry::set($key, $result);

            $this->championSummonerRankingModels[$key] = $result;
        }

        return $result;
    }

    public function calculate_champion_summoner_data_weeklyAction()
    {
        $request = $this->getRequest();

        $platform   = strtoupper($request->get('platform', ''));
        $timestamp  = $request->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

//        /**
//         * Numeric representation of the day of the week, 1(for Monday) through 7(for Sunday)
//         */
//        $weekday    = date('N', $timestamp);
//
//        $today  = mktime(0, 0, 0, $month, $day, $year);
//        $to     = strtotime(sprintf('%d day', 1 - $weekday), $today);
//        $from   = strtotime('-7 day', $to);
//
//        $date   = date('oW', $from);

        $today  = mktime(0, 0, 0, $month, $day, $year);
        $to     = $today;
        $from   = strtotime('-7 day', $today);

        $date   = date('Ymd', $to);

        try {
            $this->getLolSphinxPlain();

            $sphinxqlLOLMatchModelClass = sprintf('SphinxQL_LOL_Match_%sModel', $platform);
            $sphinxqlLOLMatchModel = new $sphinxqlLOLMatchModelClass($this->lolSphinxPlain);

            $start = 1;
            $users = $userRanges = array();

            printf("==== Get user ranges at %s ====\n", date('Y-m-d H:i:s'));

            while ($users = $sphinxqlLOLMatchModel->getUsersInMatch($start)) {
                printf("==== Get users based-on %s ====\n", $start);

                foreach (array_chunk($users, 10000) as $chunk) {
                    if (count($chunk) > 1) {
                        $range = array(
                            array_shift($chunk),
                            array_pop($chunk),
                        );
                    } else {
                        $range = array(
                            current($chunk),
                            current($chunk),
                        );
                    }

                    $userRanges[] = $range;
                }

                $start = $range[1] + 1;
            }

            unset($users);

    //        $column = 'id';
    //
    //        $range = $sphinxqlLOLUserModel->getRange($column);
    //
    //        $start = (int) $range['min'] ?: 1;
    //        $end   = (int) $range['max'];
    //
            $opts = array(
                'max_matches'   => 1000000,
            );

            printf("==== Collect champion summoner data at %s ====\n", date('Y-m-d H:i:s'));

//            $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
//            $lolUserModel = new $lolUserModelClass($this->getLolDb());

    //        while ($rowset = $sphinxqlLOLUserModel->getRowsByStep($column, $start, $end, MySQL_SphCounterModel::RANGE_STEP, 'id')) {
            foreach ($userRanges as $range) {
    //            $tmp = array_pop($rowset);

                printf("==== Collecting by users from %d to %d ... ====\n", $range[0], $range[1]);

                $params = array(
                    'select'    => '`user`, `champion`, COUNT(*) AS `total_matches`, SUM(`win`) AS `total_win`,
                        SUM(`k`) AS `total_k`, SUM(`d`) AS `total_d`, SUM(`a`) AS `total_a`,
                        GROUP_CONCAT(`game`) as `games`',
    //                'range'     => "`user`>={$start} AND `user`<={$tmp['id']} AND `champion`>0",
                    'range'     => "`user`>={$range[0]} AND `user`<={$range[1]} AND `champion`>0",
                    'groupby'   => '`user`, `champion`',
                    'sort'      => '`user` ASC, `start` DESC',
                    'offset'    => 0,
                    'limit'     => 1000000,
                );

                $result = $sphinxqlLOLMatchModel->search($params, $opts);

//                $users = array();
//
//                foreach ($result['matches'] as $row) {
//                    $users[] = $row['user'];
//                }
//
//                $users = $lolUserModel->getNames($users);

                foreach ($result['matches'] as $row) {
                    $rankingModel = $this->getChampionSummonerRankingModel($platform, $row['champion']);

    //                $rankingModel->insert(array(
                    $rankingModel->batchInsert(array(
                        'id'            => $row['user'],
//                        'name'          => isset($users[$row['user']]) ? $users[$row['user']] : '',
                        'total_matches' => $row['total_matches'],
                        'win'           => $row['total_win'],
                        'win_rate'      => $row['total_win'] / $row['total_matches'],
                        'k'             => $row['total_k'],
                        'd'             => $row['total_d'],
                        'a'             => $row['total_a'],
                        'kda'           => ($row['total_k'] + $row['total_a']) / ($row['total_d'] + 1),
                        'games'         => $row['games'],
                        'date'          => $date,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    ));
                }

                unset($result);

    //            $start = $tmp['id'] + 1;
            }

            printf("==== Calculate champion summoner rank at %s ====\n", date('Y-m-d H:i:s'));

            $baseline = strcmp($platform, 'KR') === 0 ? 15 : 5;
//            $baseline = 5;

            foreach ($this->championSummonerRankingModels as $model) {
                $model->completeBatchInsert();

                $model->createIndexes();

                $model->calculateRank($baseline);

                $model->updateName(strtolower($platform));
            }

            printf("==== Complete at %s ====\n", date('Y-m-d H:i:s'));

            printf("Calculate LOL summoner champion data in *%s* weekly: %d\n", $platform, $date);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }

        return false;
    }

    public function calculate_champion_summoner_ranking_weeklyAction()
    {
        ;
    }
}