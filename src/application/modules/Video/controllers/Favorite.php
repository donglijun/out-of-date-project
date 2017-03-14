<?php
class FavoriteController extends ApiController
{
    protected $authActions = array(
        'save',
        'unsave',
        'list',
    );

    protected $videoDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    public function saveAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
//            $mkuser = Yaf_Registry::get('mkuser');
//            $userid = $mkuser['userid'];
            $currentUser = Yaf_Registry::get('user');
            $userid = $currentUser['id'];

            $this->getVideoDb();

            $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
            $videoFavoriteModel = new MySQL_Video_FavoriteModel($this->videoDb);

            if (($link = $request->get('link')) && ($linkInfo = $videoLinkModel->getRow($link, array('id')))) {
                $videoFavoriteModel->add($userid, $link);

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function unsaveAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
//            $mkuser = Yaf_Registry::get('mkuser');
//            $userid = $mkuser['userid'];
            $currentUser = Yaf_Registry::get('user');
            $userid = $currentUser['id'];

            $this->getVideoDb();

            $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
            $videoFavoriteModel = new MySQL_Video_FavoriteModel($this->videoDb);

            if (($link = $request->get('link')) && ($linkInfo = $videoLinkModel->getRow($link, array('id')))) {
                $videoFavoriteModel->remove($userid, $link);

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function myAction()
    {
        $request = $this->getRequest();
        $where = array();

        $result = array(
            'code'  => 500,
        );

        $this->getVideoDb();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = ($page - 1) * $limit;

        $mkuser = Yaf_Registry::get('mkuser');
        $userid = $mkuser['userid'];

        $where[] = '`user`=' . (int) $userid;

        $where = $where ? implode(' AND ', $where) : '';

        $videoFavoriteModel = new MySQL_Video_FavoriteModel($this->videoDb);
        $result = $videoFavoriteModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}