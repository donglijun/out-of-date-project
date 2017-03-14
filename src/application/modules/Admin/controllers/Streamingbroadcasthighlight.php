<?php
class StreamingbroadcasthighlightController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'view'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'hide'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        if ($channel = $request->get('channel')) {
            $where[] = "`channel`=" . (int) $channel;
            $filter['channel'] = $channel;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
        $result = $streamingBroadcastHighlightModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingbroadcasthighlight/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $highlight = $request->get('highlight', 0);

        if ($highlight) {
            $this->getStreamingDb();

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $data = $streamingBroadcastHighlightModel->getRow($highlight);
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));
    }

    public function hideAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        if ($highlight = $request->get('highlight')) {
            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $affectedCount = $streamingBroadcastHighlightModel->update($highlight, array(
                'is_hidden' => 1,
            ));
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingbroadcasthighlight/list');

        return false;
    }
}