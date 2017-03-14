<?php
class DeckController extends AdminController
{
    protected $authActions = array(
        'view'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'today'     => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', 'hs-db');
        }

        return $this->hsDb;
    }

    /**
     * Convert JSON format cards to formatted string for edit purpose in web page
     *
     * @param string $json
     * @return string
     */
    private function cardsToString($json)
    {
        $result = '';

        $data = is_string($json) ? json_decode($json, true) : $json;
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = sprintf('i:%s,c:%d,k:%d', $val['i'], $val['c'], $val['k']);
            }

            $result = implode("\n", $data);
        }

        return $result;
    }

    /**
     * Convert formatted string to JSON format cards
     *
     * @param string $data
     * @return string
     */
    private function stringToCards($data)
    {
        $result = array();
        $ick = array('i', 'c', 'k');

        $data = str_replace(array("\r", ' '), array("\n", ''), $data);
        $data = array_filter(explode("\n", $data));
        foreach ($data as $key => $val) {
            $card = array();

            $val = array_filter(explode(',', $val));
            foreach ($val as $chunk) {
                list($k, $v, ) = explode(':', $chunk);

                if (in_array($k, $ick)) {
                    $card[$k] = $v;
                }
            }

            if ($card) {
                $result[] = $card;
            }
        }

        return json_encode($result);
    }

    /**
     * Display form edit template
     *
     * @param string $action
     * @param array $data
     */
    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('deck/edit.phtml'));
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $deck = $request->get('deck', 0);

        $deckModel = MySQL_DeckModel::getModel($this->getHsDb());
        $data = $deckModel->getRow($deck, $deckModel->getAllFields());

        $this->_view->assign(array(
            'data'      => $data,
            'rarityMap' => MySQL_CardModel::getRarityMap(),
            'typeMap'   => MySQL_CardModel::getTypeMap(),
        ));
    }

    public function createAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data['user'] = (int) $this->session->admin['user_id'];

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
                $data['cards'] = $this->stringToCards($cards);
                $cards = json_decode($data['cards'], true);

                $ncards = 0;
                foreach ($cards as $card) {
                    $ncards += $card['c'];
                }
                $data['ncards'] = $ncards;
            }
            if (($description = $request->get('description')) !== null) {
                $data['description'] = $description;
            }

            $data['created_on']  = $timestamp;
            $data['modified_on'] = $timestamp;

            $deckModel = new MySQL_DeckModel($this->getHsDb());

            if (isset($cards)) {
                $distr = $deckModel->calcDistribution($cards);

                foreach ($distr as $key => $val) {
                    $data[$key] = json_encode($val);
                }
            }

            $deckId = $deckModel->insert($data);

            $this->redirect('/admin/deck/view?deck=' . $deckId);

            return false;
        } else {
            $data = array(
                'is_public' => 1,
            );
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $deck = $request->get('deck', 0);
        if ($deck) {
            $deckModel = new MySQL_DeckModel($this->getHsDb());

            if ($request->isPost()) {
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
                    $data['cards'] = $this->stringToCards($cards);
                    $cards = json_decode($data['cards'], true);

                    $ncards = 0;
                    foreach ($cards as $card) {
                        $ncards += $card['c'];
                    }
                    $data['ncards'] = $ncards;
                }
                if (($description = $request->get('description')) !== null) {
                    $data['description'] = $description;
                }

                $data['modified_on'] = $timestamp;

                if (isset($cards)) {
                    $distr = $deckModel->calcDistribution($cards);

                    foreach ($distr as $key => $val) {
                        $data[$key] = json_encode($val);
                    }
                }

                $affectedCount = $deckModel->update($deck, $data);

                $this->redirect('/admin/deck/view?deck=' . $deck);

                return false;
            } else {
                $data = $deckModel->getRow($deck, $deckModel->getAllFields());
                $data['cards'] = isset($data['cards']) ? $this->cardsToString($data['cards']) : '';
                $this->_view->assign('deck', $deck);
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $deckModel = new MySQL_DeckModel($this->getHsDb());
            $affectedCount = $deckModel->delete($ids);
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
                'data'  => array(
                    'affected'  => $affectedCount,
                ),
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/deck/list');

        return false;
    }

    public function listAction()
    {
        $result = $where = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 50;
        $offset = ($page - 1) * $limit;

        $deckModel = new MySQL_DeckModel($this->getHsDb());

        $filter['page'] = '0page0';
        $lang = $request->get('lang', '');
        if ($lang) {
            $where[] = '`lang`=' . $this->hsDb->quote($lang);
            $filter['lang'] = $lang;
        }
        $class = $request->get('class', '');
        if ($class) {
            $where[] = '`class`=' . $this->hsDb->quote($class);
            $filter['class'] = $class;
        }
        $search_field = $request->get('search_field', '');
        if ($search_field) {
            $filter['search_field'] = $search_field;
        }
        $search_value = $request->get('search_value', '');
        if ($search_value) {
            $where[] = $deckModel->quoteIdentifier($search_field) . '=' . $this->hsDb->quote($search_value);
            $filter['search_value'] = $search_value;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $deckModel->search('*', $where, 'created_on DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/deck/list?' . http_build_query($filter);
        $result['classes'] = $deckModel->getClassMap();
        $result['langs'] = $deckModel->getLangMap();

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
                  ->setItemCountPerPage($limit)
                  ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function todayAction()
    {
        $result = array(
            'code'  => 404,
        );

        $deckModel = new MySQL_DeckModel($this->getHsDb());
        $recommendedModel = new MySQL_RecommendedModel($this->getHsDb());

        $result['data'] = array(
            'total_decks'           => $deckModel->getTotalCount(),
            'today_new_decks'       => $deckModel->getTodayTotal(),
            'total_recommended'     => $recommendedModel->getTotalCount(),
            'today_new_recommended' => $recommendedModel->getTodayTotal(),
        );
        $result['code'] = 200;

        echo json_encode($result);

        return false;
    }
}