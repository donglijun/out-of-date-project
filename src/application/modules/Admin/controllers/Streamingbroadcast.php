<?php
class StreamingbroadcastController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
        'view'      => MySQL_AdminAccountModel::GROUP_DIRECTOR,
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

        $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
        $result = $streamingBroadcastModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingbroadcast/list?' . http_build_query($filter);

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

        $broadcast = $request->get('broadcast', 0);

        if ($broadcast) {
            $this->getStreamingDb();

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
            $data = $streamingBroadcastModel->getRow($broadcast);
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));
    }
}