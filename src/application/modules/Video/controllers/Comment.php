<?php
class CommentController extends ApiController
{
    protected $authActions = array(
        'submit',
        'delete',
        'vote',
    );

    protected $videoDb;

    protected $passportDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    public function submitAction()
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
            $this->getPassportDb();

            $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
            $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);
            $mkjogoUser = new Mkjogo_User($this->passportDb);

            if (($link = $request->get('link')) && ($linkInfo = $videoLinkModel->getRow($link, array('id')))) {
                $data = array(
                    'link'          => $link,
                    'body'          => $request->get('body'),
                    'author'        => $userid,
                    'author_name'   => $mkjogoUser->getDetail($userid),
                    'ip'            => Misc::getClientIp(),
                    'ups'           => 1,
                    'hot_point'     => 1,
                    'created_on'    => $request->getServer('REQUEST_TIME'),
                );

                $commentId = $videoCommentModel->insert($data);

                $videoLinkModel->comment($link);

                $result['data'] = array(
                    'comment'       => $commentId,
                    'created_on'    => $request->getServer('REQUEST_TIME'),
                );
                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function deleteAction()
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

            $videoCommentModel = new MySQL_Video_CommentModel($this->getVideoDb());
            if (($comment = $request->get('comment')) && ($commentInfo = $videoCommentModel->getRow($comment, array('author')))) {
                if ($commentInfo['author'] == $userid) {
                    $result['data'] = $videoCommentModel->delete(array($comment));

                    $result['code'] = 200;
                } else {
                    $result['code'] = 403;
                }
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $result = array(
            'code'  => 500,
        );

        $this->getVideoDb();

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 20;

        $offset = intval($request->get('offset', 0));
        $offset = $offset ?: 0;

        if ($link = intval($request->get('link'))) {
            $where[] = '`link`=' . $link;

            if ($offset) {
                $where[] = '`id`>' . $offset;
            }

            $where = $where ? implode(' AND ', $where) : '';

            $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);
            $result = $videoCommentModel->search('*', $where, '`id` ASC', 0, $limit);
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function voteAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $dir = $request->get('dir', 1);
            $up = ($dir == 1);

            $videoCommentModel = new MySQL_Video_CommentModel($this->getVideoDb());

            if (($comment = $request->get('comment')) && ($linkInfo = $videoCommentModel->getRow($comment, array('id')))) {
                $videoCommentModel->vote($comment, $up);

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }

            $result['code'] = 200;
        }

        $this->callback($result);

        return false;
    }

}