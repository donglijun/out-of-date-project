<?php
class DeckController extends ApiController
{
    protected $authActions = array('create', 'update', 'delete', 'list');

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', 'hs-db');
        }

        return $this->hsDb;
    }

    public function createAction()
    {
        $data   = array();
        $result = array(
            'code'  => 500,
        );
        $timestamp = time();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            if (($userid) !== null) {
                $data['user'] = (int) $userid;
            }
            if (($title = $request->get('title')) !== null) {
                $data['title'] = $title;
            }
            if (($checksum = $request->get('checksum')) !== null) {
                $data['checksum'] = $checksum;
            }
            if (($game_version = $request->get('game_version')) !== null) {
                $data['game_version'] = $game_version;
            }
            if (($category = $request->get('category')) !== null) {
                $data['category'] = (int) $category;
            }
            if (($class = $request->get('class')) !== null) {
                $data['class'] = $class;
            }
            if (($lang = $request->get('lang')) !== null) {
                $data['lang'] = $lang;
            }
            if (($distribution = $request->get('distribution')) !== null) {
                $data['distribution'] = json_encode($distribution);
            }
            if (($is_public = $request->get('is_public')) !== null) {
                $data['is_public'] = (int) $is_public ? 1 : 0;
            }
            if (($source = $request->get('source')) !== null) {
                $data['source'] = $source;
            }
            if (($source_url = $request->get('source_url')) !== null) {
                $data['source_url'] = $source_url;
            }
            if (($author = $request->get('author')) !== null) {
                $data['author'] = $author;
            }
            if (($cards = $request->get('cards')) !== null) {
                $data['cards'] = json_encode($cards);

                $ncards = 0;
                foreach ($cards as $card) {
                    $ncards += $card['c'];
                }
                $data['ncards'] = $ncards;
            }
            if (($description = $request->get('description')) !== null) {
                $data['description'] = $description;
            }
            if (($distr_rarity = $request->get('distr_rarity')) !== null) {
                $data['distr_rarity'] = json_encode($distr_rarity);
            }
            if (($distr_type = $request->get('distr_type')) !== null) {
                $data['distr_type'] = json_encode($distr_type);
            }
            if (($distr_cost = $request->get('distr_cost')) !== null) {
                $data['distr_cost'] = json_encode($distr_cost);
            }
            if (($distr_attack = $request->get('distr_attack')) !== null) {
                $data['distr_attack'] = json_encode($distr_attack);
            }
            if (($distr_health = $request->get('distr_health')) !== null) {
                $data['distr_health'] = json_encode($distr_health);
            }

            $data['created_on']  = $timestamp;
            $data['modified_on'] = $timestamp;

            $deckModel = new MySQL_DeckModel($this->getHsDb());
            $deckId = $deckModel->insert($data);

            if ($deckId !== false) {
                $result['code'] = 200;
                $result['id'] = (int) $deckId;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function updateAction()
    {
        $data   = array();
        $result = array(
            'code'  => 500,
        );
        $timestamp = time();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $mkuser = Yaf_Registry::get('mkuser');
            $user = $mkuser['userid'];

            $deck = $request->get('deckid', 0);

            $deckModel = new MySQL_DeckModel($this->getHsDb());
            if ($deckModel->checkOwnership($user, $deck)) {
                if (($title = $request->get('title')) !== null) {
                    $data['title'] = $title;
                }
                if (($checksum = $request->get('checksum')) !== null) {
                    $data['checksum'] = $checksum;
                }
                if (($game_version = $request->get('game_version')) !== null) {
                    $data['game_version'] = $game_version;
                }
                if (($category = $request->get('category')) !== null) {
                    $data['category'] = (int) $category;
                }
                if (($class = $request->get('class')) !== null) {
                    $data['class'] = $class;
                }
                if (($lang = $request->get('lang')) !== null) {
                    $data['lang'] = $lang;
                }
                if (($distribution = $request->get('distribution')) !== null) {
                    $data['distribution'] = json_encode($distribution);
                }
                if (($is_public = $request->get('is_public')) !== null) {
                    $data['is_public'] = (int) $is_public ? 1 : 0;
                }
                if (($source = $request->get('source')) !== null) {
                    $data['source'] = $source;
                }
                if (($source_url = $request->get('source_url')) !== null) {
                    $data['source_url'] = $source_url;
                }
                if (($author = $request->get('author')) !== null) {
                    $data['author'] = $author;
                }
                if (($cards = $request->get('cards')) !== null) {
                    $data['cards'] = json_encode($cards);

                    $ncards = 0;
                    foreach ($cards as $card) {
                        $ncards += $card['c'];
                    }
                    $data['ncards'] = $ncards;
                }
                if (($description = $request->get('description')) !== null) {
                    $data['description'] = $description;
                }
                if (($distr_rarity = $request->get('distr_rarity')) !== null) {
                    $data['distr_rarity'] = json_encode($distr_rarity);
                }
                if (($distr_type = $request->get('distr_type')) !== null) {
                    $data['distr_type'] = json_encode($distr_type);
                }
                if (($distr_cost = $request->get('distr_cost')) !== null) {
                    $data['distr_cost'] = json_encode($distr_cost);
                }
                if (($distr_attack = $request->get('distr_attack')) !== null) {
                    $data['distr_attack'] = json_encode($distr_attack);
                }
                if (($distr_health = $request->get('distr_health')) !== null) {
                    $data['distr_health'] = json_encode($distr_health);
                }

                $data['modified_on'] = $timestamp;

                $affectedCount = $deckModel->update($deck, $data);

                if ($affectedCount) {
                    $result['code'] = 200;
                }
            } else {
                $result['code'] = 403;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function deleteAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        if ($request->isPost()) {
            $mkuser = Yaf_Registry::get('mkuser');
            $user = $mkuser['userid'];

            $ids = array_filter(array_unique(array_map(function($val) {
                return (int) $val;
            }, explode(',', trim($request->get('deck_ids'), ', ')))));

            $deckModel = new MySQL_DeckModel($this->getHsDb());
            $ids = $deckModel->validateOwnership($user, $ids);

            if ($ids) {
                $affectedCount = $deckModel->delete($ids);

                $result['code'] = 200;
                $result['affected'] = $affectedCount;
            } else {
                $result['code'] = 403;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function getAction()
    {
        $result = array(
            'code'  => 404,
        );
        $request = $this->getRequest();
        $deck  = $request->get('deckid', 0);

        $deckModel = new MySQL_DeckModel($this->getHsDb());
        if ($data = $deckModel->getRow($deck, $deckModel->getAllFields())) {
            $result['code'] = 200;
            $result['data'] = $data;

            $deckModel->view($deck);
        }

        echo json_encode($result);

        return false;
    }

    public function listAction()
    {
        $result = $data = array();
        $request = $this->getRequest();
        $mkuser = Yaf_Registry::get('mkuser');

        $user = $mkuser['userid'];
        $where = "user={$user}";
        $sort = 'created_on DESC';
        $offset = 0;
        $limit = 999;

        $deckModel = new MySQL_DeckModel($this->getHsDb());
        $result = $deckModel->search('*', $where, $sort, $offset, $limit);

        foreach ($result['data'] as $row) {
            $data[] = $row;
        }

        $result['data'] = $data;
        $result['code'] = 200;

        echo json_encode($result);

        return false;
    }

    public function searchAction()
    {
        $result = $data = $where = $uids = $unames = $comments = array();
        $request = $this->getRequest();
        $mkuser = Yaf_Registry::get('mkuser');
        $user = $mkuser['userid'];

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

//        $db = Yaf_Registry::get('db');
        $db = $this->getHsDb();

        if ($user) {
            $where[] = "`user`={$user}";
        }

        if ($class = $request->get('class', '')) {
            $where[] = "`class`=" . $db->quote($class);
        }

        if ($lang = $request->get('lang', '')) {
            $where[] = "`lang`=" . $db->quote($lang);
        }

        if ($category = $request->get('category', 0)) {
            $where[] = "`category`=" . $db->quote($category);
        }

        if ($q = $request->get('q', '')) {
            $where[] = sprintf("`title` LIKE '%%%s%%'", trim($db->quote($q), "'"));
        }

        $where[] = '`is_public`=1';
        $where[] = '`ncards`=30';

        $where = implode(' AND ', $where);
        $sort = '`created_on` DESC';
        $deckModel = new MySQL_DeckModel($db);
        $result = $deckModel->search('*', $where, $sort, $offset, $limit);

        $result['code']  = 200;

        foreach ($result['data'] as $row) {
            $uids[] = $row['user'];
            $comments[] = $row['id'];
        }

        /**
         * Get user names from account system
         */
        $uids = array_unique($uids);
        if ($uids) {
            sort($uids);
            $mkjogouserModel = new MySQL_MkjogoUserModel(Daemon::getDb('account-db', 'account-db'));
//            $unames = $mkjogouserModel->getRows($uids, array('username'));
            $rowset = $mkjogouserModel->getRows($uids, array('username'));
            foreach ($rowset as $row) {
                $unames[$row['user_id']] = $row['username'];
            }
        }

        /**
         * Get comments count from comment service
         */
        $comments = Mkjogo_Comment::getCount($comments);

        foreach ($result['data'] as $row) {
            $row['username'] = isset($unames[$row['user']]) ? $unames[$row['user']] : '';

            if (isset($comments[$row['id']]) && $comments[$row['id']] != $row['comments']) {
                $row['comments'] = $comments[$row['id']];

                $deckModel->update($row['id'], array(
                    'comments' => $comments[$row['id']],
                ));
            }

            $data[] = $row;
        }
        $result['data'] = $data;

        echo json_encode($result);

        return false;
    }

    public function recommendedAction()
    {
        $result = $where = $data = $decks = $uids = $unames = array();
        $request = $this->getRequest();

//        $db = Yaf_Registry::get('db');
        $db = $this->getHsDb();

        $lang = $request->get('lang');
        if (!is_null($lang)) {
            $where[] = '`lang`=' . $db->quote($lang);
        }
        $class = $request->get('class');
        if (!is_null($class)) {
            $where[] = '`class`=' . $db->quote($class);
        }
        $category = $request->get('category');
        if (is_numeric($category)) {
            $where[] = '`category`=' . $db->quote($category);
        }
        $where = $where ? implode(' AND ', $where) : '';

        $limit = $request->get('topn', 10);

        $recommendedModel = new MySQL_RecommendedModel($this->getHsDb());
        $result = $recommendedModel->search('deck', $where, 'ranking ASC', 0, $limit);

        foreach ($result['data'] as $row) {
            $decks[] = $row['deck'];
        }
        $decks = array_unique($decks);
        $decks = MySQL_DeckModel::getModel($this->getHsDb())->getRows($decks);

        foreach ($decks as $row) {
            $uids[] = $row['user'];
        }

        $uids = array_unique($uids);
        if ($uids) {
            sort($uids);
            $mkjogouserModel = new MySQL_MkjogoUserModel(Daemon::getDb('account-db', 'account-db'));
//            $unames = $mkjogouserModel->getRows($uids, array('username'));
            $rowset = $mkjogouserModel->getRows($uids, array('username'));
            foreach ($rowset as $row) {
                $unames[$row['user_id']] = $row['username'];
            }
        }

        foreach ($decks as $row) {
            $row['username'] = isset($unames[$row['user']]) ? $unames[$row['user']] : '';
            $data[] = $row;
        }

        $result = array(
            'code'  => 200,
            'data'  => $data,
        );

        echo json_encode($result);

        return false;
    }
}