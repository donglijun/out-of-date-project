<?php
class TranslateController extends AdminController
{
    protected $authActions = array(
        'test'          => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'totranstable'  => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'totmp'         => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'todeck'        => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'fix'           => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'export'        => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', 'hs-db');
        }

        return $this->hsDb;
    }

    private function loadTranslateTable()
    {
        global $db;

        $result = array();
        $select_sql = 'SELECT `content_from`,`content_to` FROM `translate_table`';
        $stmt = $db->query($select_sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['content_from']] = $row['content_to'];
        }

        return $result;
    }


    private function translatePhrase($data)
    {
        global $translateTable;

        return strtr($data, $translateTable);
    }

    private function translateWord($data)
    {
        global $zh2Hant, $zh2TW;

        return strtr(strtr($data, $zh2TW), $zh2Hant);
    }

    public function testAction()
    {
        global $translateTable, $zh2Hant, $zh2TW, $db;

        $db = $this->getHsDb();
        require_once APPLICATION_PATH . '/library/ZhConversion.php';

        echo '<pre>';

        $translateTable = $this->loadTranslateTable();
        var_dump($translateTable);

        $select_sql = 'SELECT `d`.`id`,`d`.`title`,`de`.`description` FROM `deck` AS d LEFT JOIN `deck_extra` AS de ON d.`id`=de.`deck` WHERE `d`.`id`=33';
        $stmt = $db->query($select_sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        var_dump($row);

        var_dump($desc1 = $this->translatePhrase($row['description']));

        var_dump($desc2 = $this->translateWord($desc1));

        echo '</pre>';

        return false;
    }

    public function totmpAction()
    {
        global $translateTable, $zh2Hant, $zh2TW, $db;

        $db = $this->getHsDb();
        require_once APPLICATION_PATH . '/library/ZhConversion.php';

        $min = 29;
        $max = 204;
        $select_sql = 'SELECT `d`.`id`,`d`.`title`,`de`.`description` FROM `deck` AS d LEFT JOIN `deck_extra` AS de ON d.`id`=de.`deck` WHERE `d`.`id` BETWEEN :min AND :max';
        $insert_sql = 'INSERT INTO `tmp` (`id`,`title`,`description`,`old_title`,`old_description`) VALUES(:id,:title,:description,:old_title,:old_description)';
        $stmt2 = $db->prepare($insert_sql);
        $stmt = $db->prepare($select_sql);
        $stmt->execute(array(
            ':min'  => $min,
            ':max'  => $max,
        ));

        $translateTable = $this->loadTranslateTable();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = $row['id'];
            $title = $this->translateWord($this->translatePhrase($row['title']));
            $description = $this->translateWord($this->translatePhrase($row['description']));

            $stmt2->execute(array(
                ':id'               => $id,
                ':title'            => $title,
                ':description'      => $description,
                ':old_title'        => $row['title'],
                ':old_description'  => $row['description'],
            ));
        }

        echo 'ok';

        return false;
    }

    public function totranstableAction()
    {
        $db = $this->getHsDb();

        $insert_sql = 'INSERT INTO `translate_table` (`lang_from`,`lang_to`,`content_from`,`content_to`) VALUES(:lang_from,:lang_to,:content_from,:content_to)';
        $stmt = $db->prepare($insert_sql);

        if ($fh = fopen(APPLICATION_PATH . '/library/translate_table.csv', 'rb')) {
            while (($data = fgetcsv($fh, 1000, ',', '"')) !== false) {
                $lang_from  = $data[1];
                $lang_to    = $data[2];
                $cn         = $data[3];
                $tw         = $data[4];

                Debug::dump($data, true);
                $stmt->execute(array(
                    ':lang_from'    => $lang_from,
                    ':lang_to'      => $lang_to,
                    ':content_from' => $cn,
                    ':content_to'   => $tw,
                ));
            }
        }

        return false;
    }

    public function todeckAction()
    {
        $db = $this->getHsDb();
        $deckModel = new MySQL_DeckModel($db);

        $new_user = 2256141;
        $timestamp = $_SERVER['REQUEST_TIME'];
        $select_sql = 'SELECT `id`,`title`,`description` FROM `tmp` WHERE `new_id`=0';
        $update_sql = 'UPDATE `tmp` SET `new_id`=:new_id WHERE `id`=:id';
        $stmt2 = $db->prepare($update_sql);
        $stmt = $db->query($select_sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            Debug::dump($row, true);

            $data = $deckModel->getRow($row['id'], $deckModel->getAllFields());
            Debug::dump($data, true);

            $data['user']           = $new_user;
            $data['title']          = $row['title'];
            $data['description']    = $row['description'];
            $data['created_on']     = $timestamp;
            $data['modified_on']    = $timestamp;
            $data['lang']           = 'zh_TW';
            $data['favorites']      = 0;
            $data['comments']       = 0;
            $data['views']          = 0;
            $data['distribution']   = json_encode($data['distribution']);
            $data['cards']          = json_encode($data['cards']);
            Debug::dump($data, true);

            unset($data['id']);
            unset($data['deck']);

            $new_id = $deckModel->insert($data);

            $stmt2->execute(array(
                ':new_id'   => $new_id,
                ':id'       => $row['id'],
            ));
        }

        echo 'ok';

        return false;
    }

    public function fixAction()
    {
        $db = $this->getHsDb();
        $deckModel = new MySQL_DeckModel($db);

        $config = Yaf_Registry::get('config')->toArray();
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $config['old-db']['driver'], $config['old-db']['host'],
            $config['old-db']['port'], $config['old-db']['dbname']);
        $olddb = new PDO($dsn, $config['old-db']['username'], $config['old-db']['password'], $config['old-db']['driver_options']);
        $olddb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $oldDeckModel = new MySQL_DeckModel($olddb);

        $result = $deckModel->search('id', null, null, 0, 1000);
        foreach ($result['data'] as $val) {
            $id = $val['id'];

            $row = $deckModel->getRow($id, array('title', 'description'));
            Debug::dump($row);
            $row = $oldDeckModel->getRow($id, array('title', 'description'));
            Debug::dump($row, true);

            $deckModel->update($id, $row);
        }

        echo 'ok';

        return false;
    }

    public function exportAction()
    {
        $db = $this->getHsDb();

        $update_title_sql = "UPDATE deck SET title=%s WHERE id=%d;";
        $update_description_sql = "UPDATE deck_extra SET description=%s WHERE deck=%d;";
        $select_sql = 'SELECT id,title,description FROM deck AS d LEFT JOIN deck_extra AS de ON d.id=de.deck ORDER BY id ASC';
        $stmt = $db->query($select_sql);

        $data = array();
        $data[] = 'SET NAMES utf8;';
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            Debug::dump($row, true);
            $data[] = sprintf($update_title_sql, $db->quote($row['title']), $row['id']);
            $data[] = sprintf($update_description_sql, $db->quote($row['description']), $row['id']);
        }

        Misc::httpOutputFile(array(
            'fileName'  => 'fix-deck.sql',
            'raw'       => implode("\n", $data),
        ));

        return false;
    }
}