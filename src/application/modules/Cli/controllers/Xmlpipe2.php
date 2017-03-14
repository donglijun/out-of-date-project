<?php
class Xmlpipe2Controller extends CliController
{
    protected $mkjogoDb;

    protected $lolDb;

    protected $platforms = array();

    protected $xml;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function checkMkjogoDb()
    {
        if (!$this->mkjogoDb) {
            $this->getMkjogoDb();
        } else {
            try {
                $this->mkjogoDb->query('SHOW DATABASES');
            } catch (Exception $e) {
                Yaf_Registry::del('mkjogo-db');
                $this->mkjogoDb = null;

                $this->getMkjogoDb();
            }
        }
    }

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

    protected function getPlatforms()
    {
        if (!$this->platforms) {
            $lolPlatformModel = new MySQL_LOL_PlatformModel($this->getLolDb());

            $this->platforms = $lolPlatformModel->getAvailablePlatforms();
        }

        return $this->platforms;
    }

    protected function startXml()
    {
        if ($this->xml = new XMLWriter()) {
//        if ($this->xml = new XML_Writer_Sphinx()) {
            $this->xml->openURI('php://output');
            $this->xml->setIndent(true);
            $this->xml->setIndentString('    ');

            // Document
            $this->xml->startDocument('1.0', 'UTF-8');

            // docset
            $this->xml->startElement('sphinx:docset');
        }

        return $this->xml;
    }

    protected function endXml()
    {
        if ($this->xml) {
            // End docset
            $this->xml->endElement();

            // End Document
            $this->xml->endDocument();

            $this->xml->flush();
        }
    }

    public function lol_champion_pick_banAction()
    {
        $request = $this->getRequest();

        $this->getMkjogoDb();
        $this->getLolDb();

        $this->getPlatforms();

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

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $slug = MySQL_SphCounterModel::SLUG_PREFIX_LOL_CHAMPION_PICK_BAN . strtolower($platform);

            $lolChampionPickBanModelClass = sprintf('MySQL_LOL_Champion_PickBan_%sModel', $platform);
            $lolChampionPickBanModel = new $lolChampionPickBanModelClass($this->lolDb);

            // Champion pick-ban use gameid as primary key, but gameid is not incremental, so use created_on as column
            $column = 'created_on';
//            $where  = "`created_on` >= {$from} AND `created_on` < {$to}";
//
//            $range = $lolChampionPickBanModel->getRange($column, $where);
//
//            $start = (int) $range['min'];
//            $end   = (int) $range['max'];
//
            $start = (int) $from;
            $end   = (int) $to;

            if ($this->startXml()) {
                while ($rowset = $lolChampionPickBanModel->getRowsByStep($column, $start, $end, MySQL_SphCounterModel::RANGE_STEP)) {
                    foreach ($rowset as $row) {
                        // validate
                        if (!$row['map'] || !$row['mode']) {
                            $start = (int) $row[$column];

                            continue;
                        }

                        // document
                        $this->xml->startElement('sphinx:document');
                        $this->xml->writeAttribute('id', $row['id']);

                        // pick
                        $this->xml->startElement('pick');
                        $this->xml->text(Helper_Formatter_Sphinx::formatMVA(json_decode($row['pick'], true)));
                        $this->xml->endElement();

                        // ban
                        $this->xml->startElement('ban');
                        $this->xml->text(Helper_Formatter_Sphinx::formatMVA(json_decode($row['ban'], true)));
                        $this->xml->endElement();

                        // map
                        $this->xml->startElement('map');
                        $this->xml->text($row['map']);
                        $this->xml->endElement();

                        // mode
                        $this->xml->startElement('mode');
                        $this->xml->text($row['mode']);
                        $this->xml->endElement();

                        // start
                        $this->xml->startElement('start');
                        $this->xml->text($row['start']);
                        $this->xml->endElement();

                        // created_on
                        $this->xml->startElement('created_on');
                        $this->xml->text($row['created_on']);
                        $this->xml->endElement();

                        // End document
                        $this->xml->endElement();

                        $start = (int) $row[$column];
                    }

                    $start += 1;
                }

                $this->endXml();

                $this->checkMkjogoDb();

                $sphCounterModel = new MySQL_SphCounterModel($this->mkjogoDb);

                $sphCounterModel->replace(array(
                    'index_slug'    => $slug,
                    'max_doc_id'    => $end,
                ));
            }
        }

        return false;
    }

    public function lol_matchAction()
    {
        $request = $this->getRequest();

        $this->getMkjogoDb();
        $this->getLolDb();

        $this->getPlatforms();

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

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $slug = MySQL_SphCounterModel::SLUG_PREFIX_LOL_MATCH . strtolower($platform);

            $lolMatchModelClass = sprintf('MySQL_LOL_Match_%sModel', $platform);
            $lolMatchModel = new $lolMatchModelClass($this->lolDb);

            $column = 'id';
            $where  = "`created_on` >= {$from} AND `created_on` < {$to}";

            $range = $lolMatchModel->getRange($column, $where);

            $start = (int) $range['min'];
            $end   = (int) $range['max'];

            if ($this->startXml()) {
                while ($rowset = $lolMatchModel->getRowsByStep($column, $start, $end, MySQL_SphCounterModel::RANGE_STEP)) {
                    foreach ($rowset as $row) {
                        // validate
                        if (!$row['map'] || !$row['mode']) {
                            $start = (int) $row[$column];

                            continue;
                        }

                        // document
                        $this->xml->startElement('sphinx:document');
                        $this->xml->writeAttribute('id', $row['id']);

                        // game
                        $this->xml->startElement('game');
                        $this->xml->text($row['game']);
                        $this->xml->endElement();

                        // user
                        $this->xml->startElement('user');
                        $this->xml->text($row['user']);
                        $this->xml->endElement();

                        // champion
                        $this->xml->startElement('champion');
                        $this->xml->text($row['champion']);
                        $this->xml->endElement();

                        // map
                        $this->xml->startElement('map');
                        $this->xml->text($row['map']);
                        $this->xml->endElement();

                        // mode
                        $this->xml->startElement('mode');
                        $this->xml->text($row['mode']);
                        $this->xml->endElement();

                        // ranked
                        $this->xml->startElement('ranked');
                        $this->xml->text($row['ranked']);
                        $this->xml->endElement();

                        // start
                        $this->xml->startElement('start');
                        $this->xml->text($row['start']);
                        $this->xml->endElement();

                        // k
                        $this->xml->startElement('k');
                        $this->xml->text($row['k']);
                        $this->xml->endElement();

                        // d
                        $this->xml->startElement('d');
                        $this->xml->text($row['d']);
                        $this->xml->endElement();

                        // a
                        $this->xml->startElement('a');
                        $this->xml->text($row['a']);
                        $this->xml->endElement();

                        // mddp
                        $this->xml->startElement('mddp');
                        $this->xml->text($row['mddp']);
                        $this->xml->endElement();

                        // pddp
                        $this->xml->startElement('pddp');
                        $this->xml->text($row['pddp']);
                        $this->xml->endElement();

                        // tdt
                        $this->xml->startElement('tdt');
                        $this->xml->text($row['tdt']);
                        $this->xml->endElement();

                        // lmk
                        $this->xml->startElement('lmk');
                        $this->xml->text($row['lmk']);
                        $this->xml->endElement();

                        // mk
                        $this->xml->startElement('mk');
                        $this->xml->text($row['mk']);
                        $this->xml->endElement();

                        // nmk
                        $this->xml->startElement('nmk');
                        $this->xml->text($row['nmk']);
                        $this->xml->endElement();

                        // gold
                        $this->xml->startElement('gold');
                        $this->xml->text($row['gold']);
                        $this->xml->endElement();

                        // len
                        $this->xml->startElement('len');
                        $this->xml->text($row['len']);
                        $this->xml->endElement();

                        // win
                        $this->xml->startElement('win');
                        $this->xml->text($row['win']);
                        $this->xml->endElement();

                        // items
                        $this->xml->startElement('items');
                        $this->xml->text(Helper_Formatter_Sphinx::formatMVA(json_decode($row['items'], true)));
                        $this->xml->endElement();

                        // spells
                        $this->xml->startElement('spells');
                        $this->xml->text(Helper_Formatter_Sphinx::formatMVA(json_decode($row['spells'], true)));
                        $this->xml->endElement();

                        // aps
//                        $this->xml->startElement('aps');
//                        $this->xml->text($row['aps'] && $row['aps'] != 'null' ? $row['aps'] : '[]');
//                        $this->xml->endElement();

                        // created_on
                        $this->xml->startElement('created_on');
                        $this->xml->text($row['created_on']);
                        $this->xml->endElement();

                        // End document
                        $this->xml->endElement();

                        $start = (int) $row[$column];
                    }

                    $start += 1;
                }

                $this->endXml();

                $this->checkMkjogoDb();

                $sphCounterModel = new MySQL_SphCounterModel($this->mkjogoDb);

                $sphCounterModel->replace(array(
                    'index_slug'    => $slug,
                    'max_doc_id'    => $end,
                ));
            }
        }

        return false;
    }

    public function lol_userAction()
    {
        $request = $this->getRequest();

        $this->getMkjogoDb();
        $this->getLolDb();

        $this->getPlatforms();

        $platform   = strtoupper($request->get('platform', ''));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $slug = MySQL_SphCounterModel::SLUG_PREFIX_LOL_USER . strtolower($platform);

            $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
            $lolUserModel = new $lolUserModelClass($this->lolDb);

            $column = 'id';

            $range = $lolUserModel->getRange($column);

            $start = (int) $range['min'];
            $end   = (int) $range['max'];

            if ($this->startXml()) {
                while ($rowset = $lolUserModel->getRowsByStep($column, $start, $end, MySQL_SphCounterModel::RANGE_STEP)) {
                    foreach ($rowset as $row) {
                        // validate
                        if (!$row['id']) {
                            $start = (int) $row[$column];

                            continue;
                        }

                        // document
                        $this->xml->startElement('sphinx:document');
                        $this->xml->writeAttribute('id', $row['id']);

                        // level
                        $this->xml->startElement('level');
                        $this->xml->text($row['level']);
                        $this->xml->endElement();

                        // icon_id
                        $this->xml->startElement('icon_id');
                        $this->xml->text($row['icon_id']);
                        $this->xml->endElement();

                        // metadata
                        $this->xml->startElement('metadata');
                        $this->xml->text($row['metadata']);
                        $this->xml->endElement();

                        // updated_on
                        $this->xml->startElement('updated_on');
                        $this->xml->text($row['updated_on']);
                        $this->xml->endElement();

                        // End document
                        $this->xml->endElement();

                        $start = (int) $row[$column];
                    }

                    $start += 1;
                }

                $this->endXml();

                $this->checkMkjogoDb();

                $sphCounterModel = new MySQL_SphCounterModel($this->mkjogoDb);

                $sphCounterModel->replace(array(
                    'index_slug'    => $slug,
                    'max_doc_id'    => $end,
                ));
            }
        }

        return false;
    }

    public function lol_user_nameAction()
    {
        $request = $this->getRequest();

        $this->getMkjogoDb();
        $this->getLolDb();

        $this->getPlatforms();

        $platform   = strtoupper($request->get('platform', ''));

        if (in_array(strtolower($platform), $this->getPlatforms())) {
            $slug = MySQL_SphCounterModel::SLUG_PREFIX_LOL_USER_NAME . strtolower($platform);

            $lolUserModelClass = sprintf('MySQL_LOL_User_%sModel', $platform);
            $lolUserModel = new $lolUserModelClass($this->lolDb);

            $column = 'id';

            $range = $lolUserModel->getRange($column);

            $start = (int) $range['min'];
            $end   = (int) $range['max'];

            if ($this->startXml()) {
                while ($rowset = $lolUserModel->getRowsByStep($column, $start, $end, MySQL_SphCounterModel::RANGE_STEP)) {
                    foreach ($rowset as $row) {
                        // validate
                        if (!$row['id'] || !$row['name']) {
                            $start = (int) $row[$column];

                            continue;
                        }

                        // document
                        $this->xml->startElement('sphinx:document');
                        $this->xml->writeAttribute('id', $row['id']);

                        // name
                        $this->xml->startElement('name');
                        $this->xml->text($row['name']);
                        $this->xml->endElement();

                        // End document
                        $this->xml->endElement();

                        $start = (int) $row[$column];
                    }

                    $start += 1;
                }

                $this->endXml();

                $this->checkMkjogoDb();

                $sphCounterModel = new MySQL_SphCounterModel($this->mkjogoDb);

                $sphCounterModel->replace(array(
                    'index_slug'    => $slug,
                    'max_doc_id'    => $end,
                ));
            }
        }

        return false;
    }
}