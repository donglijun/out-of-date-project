<?php
class StreamingbulletController extends AdminController
{
    protected $authActions = array(
        'delete'            => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'deletebyauthor'    => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'listbyhighlight'   => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'list'              => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);
            $affectedCount = $streamingBulletModel->delete($ids);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingbullet/list');

        return false;
    }

    public function deletebyauthorAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        if ($author = $request->get('author')) {
            $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);
            $affectedCount = $streamingBulletModel->deleteByAuthor($author);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingbullet/list');

        return false;
    }

    public function listbyhighlightAction()
    {
        $request = $this->getRequest();
        $where = $highlights = $rowset = array();

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $highlight = $request->get('highlight');
        if ($highlight) {
            $where[] = '`highlight`=' . (int) $highlight;
            $filter['highlight'] = $highlight;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);
        $result = $streamingBulletModel->search('*', $where, '`id` ASC', $offset, $limit);

        foreach ($result['data'] as $row) {
            $highlights[] = $row['highlight'];
        }
        $highlights = array_unique($highlights);
        sort($highlights);

        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $rowset = $streamingBroadcastHighlightModel->getRows($highlights, array('id', 'remote_path', 'title'));
        $highlights = array();
        foreach ($rowset as $row) {
            $highlights[$row['id']] = $row;
        }

        $result['highlights'] = $highlights;
        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingbullet/listbyhighlight?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('streamingbullet/list.phtml'));

        return false;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = $highlights = $rowset = array();

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);
        $result = $streamingBulletModel->search('*', null, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $row) {
            $highlights[] = $row['highlight'];
        }
        $highlights = array_unique($highlights);
        sort($highlights);

        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $rowset = $streamingBroadcastHighlightModel->getRows($highlights, array('id', 'remote_path', 'title'));
        $highlights = array();
        foreach ($rowset as $row) {
            $highlights[$row['id']] = $row;
        }

        $result['highlights'] = $highlights;
        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingbullet/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}