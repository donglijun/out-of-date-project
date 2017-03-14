<?php
class StreamingcampaignmemberController extends AdminController
{
    protected $authActions = array(
        'list' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'memo' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
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
        $where = $channels = $onlines = $memos = array();

        $this->getStreamingDb();
        $streamingCampaignMemberModel = new MySQL_Streaming_CampaignMemberModel($this->streamingDb);

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $search_field = $request->get('search_field', '');
        if ($search_field) {
            $filter['search_field'] = $search_field;
        }
        $search_value = $request->get('search_value', '');
        if ($search_value) {
            $where[] = $streamingCampaignMemberModel->quoteIdentifier($search_field) . '=' . $this->streamingDb->quote(strtolower(trim($search_value)));
            $filter['search_value'] = $search_value;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $streamingCampaignMemberModel->search('*', $where, '`id` DESC', $offset, $limit);

        foreach ($result['data'] as $val) {
            $channels[] = $val['id'];
        }

        if ($channels) {
            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            if ($rows = $streamingChannelModel->getRows($channels, array('id', 'is_online', 'memo'))) {
                foreach ($rows as $row) {
                    $onlines[$row['id']] = $row['is_online'];
                    $memos[$row['id']] = $row['memo'];
                }

                foreach ($result['data'] as $key => $val) {
                    if (isset($onlines[$val['id']])) {
                        $result['data'][$key]['is_online'] = $onlines[$val['id']];
                        $result['data'][$key]['memo'] = $memos[$val['id']];
                    } else {
                        $result['data'][$key]['is_online'] = 0;
                        $result['data'][$key]['memo'] = '';
                    }
                }
            }
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/streamingcampaignmember/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

//    public function memoAction()
//    {
//        $result = array();
//        $request = $this->getRequest();
//
//        if (($id = $request->get('id')) && ($memo = $request->get('memo'))) {
//            $this->getStreamingDb();
//            $streamingCampaignMemberModel = new MySQL_Streaming_CampaignMemberModel($this->streamingDb);
//
//            $streamingCampaignMemberModel->update($id, array(
//                'memo'  => $memo,
//            ));
//
//            $result['code'] = 200;
//        } else {
//            $result['code'] = 404;
//        }
//
//        if ($request->isXmlHttpRequest()) {
//            header('Content-Type: application/json; charset=utf-8');
//
//            echo json_encode($result);
//
//            return false;
//        }
//
//        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingcampaignmember/list');
//
//        return false;
//    }
}