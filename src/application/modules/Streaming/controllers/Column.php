<?php
class ColumnController extends ApiController
{
    protected $authActions = array();

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

        $result = array(
            'code'  => 500,
        );

        $streamingColumnModel = new MySQL_Streaming_ColumnModel($this->getStreamingDb());
        $result['data'] = $streamingColumnModel->getAllRows();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function itemsAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($column = $request->get('column')) {
            $where = $channels = $titles = $onlines = array();
            $where[] = '`column`=' . (int) $column;
            $where = $where ? implode(' AND ', $where) : '';

            $this->getStreamingDb();

            $page = intval($request->get('page', 0));
            $page = $page < 1 ? 1 : $page;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 20;

            $offset = ($page - 1) * $limit;

            $streamingColumnItemModel = new MySQL_Streaming_ColumnItemModel($this->streamingDb);
            $result = $streamingColumnItemModel->search('`id`,`column`,`media_type`,`source`,`title`,`small_image`,`large_image`', $where, '`display_order` DESC', $offset, $limit);

            foreach ($result['data'] as $row) {
                if ($row['media_type'] == 'streaming') {
                    $channels[] = $row['source'];
                }
            }

            if ($channels) {
                $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
                foreach ($streamingChannelModel->getRows($channels, array('id', 'title', 'is_online')) as $row) {
//                    $titles[$row['id']] = $row['title'];
                    $onlines[$row['id']] = $row['is_online'];
                }

                foreach ($result['data'] as $key => $val) {
                    if (($val['media_type'] == 'streaming')) {
//                        $result['data'][$key]['title'] = $titles[$val['source']];
                        $result['data'][$key]['is_online'] = $onlines[$val['source']];
                    }
                }
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}