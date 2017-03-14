<?php
class VideocommentController extends AdminController
{
    protected $authActions = array(
        'delete'        => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'listbylink'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'list'          => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $videoDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getVideoDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);
            $affectedCount = $videoCommentModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/videocomment/list');

        return false;
    }

    public function listbylinkAction()
    {
        $request = $this->getRequest();
        $where = $links = $rowset = array();

        $this->getVideoDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $link = $request->get('link');
        if ($link) {
            $where[] = '`link`=' . (int) $link;
            $filter['link'] = $link;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);
        $result = $videoCommentModel->search('*', $where, '`id` ASC', $offset, $limit);

        foreach ($result['data'] as $row) {
            $links[] = $row['link'];
        }
        $links = array_unique($links);
        sort($links);
        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $rowset = $videoLinkModel->getRows($links, array('url', 'title'));
        $links = array();
        foreach ($rowset as $row) {
            $links[$row['id']] = $row;
        }

        $result['links'] = $links;
        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/videocomment/listbylink?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('videocomment/list.phtml'));

        return false;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $links = $rowset = array();

        $this->getVideoDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $videoCommentModel = new MySQL_Video_CommentModel($this->videoDb);
        $result = $videoCommentModel->search('*', null, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $row) {
            $links[] = $row['link'];
        }
        $links = array_unique($links);
        sort($links);
        $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
        $rowset = $videoLinkModel->getRows($links, array('url', 'title'));
        $links = array();
        foreach ($rowset as $row) {
            $links[$row['id']] = $row;
        }

        $result['links'] = $links;
        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/videocomment/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}