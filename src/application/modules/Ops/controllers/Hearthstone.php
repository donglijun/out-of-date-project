<?php
class HearthstoneController extends AdminController
{
    protected $authActions = array(
        'import_enus_decks'         => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update_deck_distribution'  => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', ',hs-db');
        }

        return $this->hsDb;
    }

    public function init()
    {
        Yaf_Registry::get('layout')->disableLayout();
    }

    public function import_enus_decksAction()
    {
        $db = $this->getHsDb();
        $stmt = $db->query("SELECT * FROM `tbl_hearthhead` WHERE `check_status`=1");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $timestamp = time();
            $data = array(
                'user'          => 2196768,
                'title'         => $row['title'],
                'created_on'    => $timestamp,
                'modified_on'   => $timestamp,
                'game_version'  => $row['game_version'],
                'category'      => $row['category'],
                'class'         => $row['class'],
                'lang'          => 'en_US',
                'ncards'        => 30,
                'is_public'     => 1,
                'source'        => 'hearthhead.com',
                'source_url'    => $row['deck_url'],
                'author'        => $row['author'],
                'cards'         => $row['cards'],
                'description'   => $row['final_description'],
            );

            $deckModel = new MySQL_DeckModel($db);
            $deckId = $deckModel->insert($data);
        }

        echo 'ok';

        return false;
    }

    public function update_deck_distributionAction()
    {
        $data = $distr = array();
        $counter = 0;

        $db = $this->getHsDb();
        $stmt = $db->query("SELECT `deck` FROM `deck_extra` WHERE `distr_rarity`='' ORDER BY `deck` ASC");

        $deckModel = new MySQL_DeckModel($db);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $deck = $deckModel->getRow($row['deck'], array('cards'));
            $distr = $deckModel->calcDistribution($deck['cards']);

            $data = array();
            foreach ($distr as $key => $val) {
                $data[$key] = json_encode($val);
            }

            $deckModel->update($row['deck'], $data);

            $counter += 1;
        }

        echo $counter;

        return false;
    }
}