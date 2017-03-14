<?php
class FavoriteController extends ApiController
{
    protected $authActions = array('follow', 'unfollow', 'list');

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', 'hs-db');
        }

        return $this->hsDb;
    }

    public function followAction()
    {
        $result = array();
        $request = $this->getRequest();
        $mkuser = Yaf_Registry::get('mkuser');

        $user = $mkuser['userid'];
        $deck = $request->get('deckid', 0);

        $userFavoriteModel = MySQL_UserFavoriteModel::getModel($this->getHsDb());
        $favoriteId = $userFavoriteModel->add($user, $deck);

        if ($favoriteId !== false) {
            $result['code'] = 200;
            $result['id'] = (int) $favoriteId;
        } else {
            $result['code'] = 500;
        }

        echo json_encode($result);

        return false;
    }

    public function unfollowAction()
    {
        $result = array();
        $request = $this->getRequest();
        $mkuser = Yaf_Registry::get('mkuser');

        $user = $mkuser['userid'];
        $favoriteIds = array_filter(array_unique(array_map(function($val) {
            return (int) $val;
        }, explode(',', trim($request->get('favorite_ids'), ', ')))));

        $userFavoriteModel = MySQL_UserFavoriteModel::getModel($this->getHsDb());
        $affectedCount = $userFavoriteModel->remove($user, $favoriteIds);

        $result['code'] = 200;
        $result['affected'] = (int) $affectedCount;

        echo json_encode($result);

        return false;
    }

    public function listAction()
    {
        $result = $data = $favorites = $decks = $uids = $unames = array();
        $request = $this->getRequest();
        $mkuser = Yaf_Registry::get('mkuser');

        $user = $mkuser['userid'];
        $userFavoriteModel = MySQL_UserFavoriteModel::getModel($this->getHsDb());
        $favorites = $userFavoriteModel->my($user, array('deck', 'added_on'));

        // Get deck info
        foreach ($favorites as $row) {
            $decks[] = $row['deck'];
        }
        $deckModel = new MySQL_DeckModel($this->getHsDb());
        $decks = $deckModel->getRows(array_unique($decks));

        // Get deck user name
        foreach ($decks as $row) {
            $uids[] = $row['user'];
        }

        $uids = array_unique($uids);
        if ($uids) {
            sort($uids);
            $mkjogouserModel = new MySQL_MkjogoUserModel(Daemon::getDb('account-db', 'account-db'));
            $rowset = $mkjogouserModel->getRows($uids, array('username'));
            foreach ($rowset as $row) {
                $unames[$row['user_id']] = $row['username'];
            }
        }

        foreach ($decks as $key => $row) {
//            $decks[$key]['username'] = isset($unames[$row['user']]['username']) ? $unames[$row['user']]['username'] : '';
            $decks[$row['id']]['username'] = isset($unames[$row['user']]) ? $unames[$row['user']] : '';
            unset($decks[$key]['id']);
        }

        // Merge deck info
        foreach ($favorites as $key => $row) {
            if (isset($decks[$row['deck']])) {
                $data[] = array_merge($decks[$row['deck']], $row);
            } else {
                $data[] = $row;
            }
        }

        $result['code'] = 200;
        $result['data'] = $data;

        echo json_encode($result);

        return false;
    }
}