<?php
class SystembroadcastController extends AdminController
{
    protected $authActions = array(
        'list'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'send'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $streamingDb;

    protected $redisStreaming;

    protected $redisChat;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    public function listAction()
    {
        $request = $this->getRequest();
        $where = array();

        $this->getStreamingDb();
        $config = Yaf_Registry::get('config')->toArray();

        $page = intval($request->get('page', 0));
        $page = $page < 1 ? 1 : $page;

        $limit = intval($request->get('limit', 0));
        $limit = $limit ?: 50;

        $offset = ($page - 1) * $limit;

        $filter['page'] = '0page0';
        $where = $where ? implode(' AND ', $where) : '';

        $streamingSystemBroadcastModel = new MySQL_Streaming_SystemBroadcastModel($this->streamingDb);
        $result = $streamingSystemBroadcastModel->search('*', $where, '`id` DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/systembroadcast/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function sendAction()
    {
        $request = $this->getRequest();
        $timestamp = time();

        $result = array(
            'code'  => 500,
        );

        $userid = $this->session->admin['user'];
        $username = $this->session->admin['name'];

        $this->getStreamingDb();
        $this->getRedisStreaming();
        $this->getRedisChat();

        $targetChannel = 0;

        if ($body = $request->get('body')) {
            $data = array(
                'body'           => $body,
                'target_channel' => $targetChannel,
                'created_on'     => $timestamp,
                'created_by'     => $userid,
            );

            $streamingSystemBroadcastModel = new MySQL_Streaming_SystemBroadcastModel($this->streamingDb);

            $result['data'] = $streamingSystemBroadcastModel->insert($data);

            $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
            $channels = $redisStreamingChannelOnlineChannelModel->getList();

            $redisStreamingChatChannelModel = new Redis_Streaming_Chat_ChannelModel($this->redisChat);
            $redisStreamingChatChannelModel->publishSystem($channels, $data);

            $result['code'] = 200;
            $result['message'] = 'ok';
        } else {
            $result['code'] = 404;
        }

        echo json_encode($result);

        return false;
    }
}