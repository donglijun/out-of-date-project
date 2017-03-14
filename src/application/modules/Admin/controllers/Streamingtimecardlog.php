<?php
class StreamingtimecardlogController extends AdminController
{
    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
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
        if ($number = $request->get('number')) {
            $where[] = "`number`=" . $this->streamingDb->quote($number);
            $filter['number'] = $number;
        }
        if ($user = $request->get('user')) {
            $where[] = "`user`=" . (int) $user;
            $filter['user'] = $user;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $streamingTimeCardLogModel = new MySQL_Streaming_TimeCardLogModel($this->streamingDb);
        $result = $streamingTimeCardLogModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingtimecardlog/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}