<?php
class BadwordController extends ApiController
{
    protected $authActions = array(
        'list',
        'add',
    );

    protected $streamingDb;

    protected $mkjogoDb;

    protected $redisChat;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    protected function updateCache()
    {
        $this->getMkjogoDb();
        $this->getRedisChat();

        $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
        if ($words = $mkjogoBadwordModel->getAllWords()) {
            $words = implode('|', array_map(function ($val) {
                return preg_quote($val, '#');
            }, $words));
        } else {
            $words = '';
        }

        $redisStreamingBadWordModel = new Redis_Streaming_BadWordModel($this->redisChat);
        return $redisStreamingBadWordModel->set($words);
    }

    public function listAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);

        if ($streamingSupervisorModel->isMember($userid)) {
            $this->getMkjogoDb();

            $where = array();

            $page = intval($request->get('page', 0));
            $page = $page < 1 ? 1 : $page;

            $limit = intval($request->get('limit', 0));
            $limit = $limit ?: 50;

            $offset = ($page - 1) * $limit;

            $filter['page'] = '0page0';

            if ($keyword = $request->get('keyword')) {
                $where[] = sprintf("`content` LIKE %s", $this->mkjogoDb->quote('%' . $keyword . '%'));
            }

            $where = $where ? implode(' AND ', $where) : '';

            $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
            $result = $mkjogoBadwordModel->search('`content`', $where, '`content` ASC', $offset, $limit);

            $result['code'] = 200;
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function addAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $streamingSupervisorModel = new MySQL_Streaming_SupervisorModel($this->streamingDb);

        if ($streamingSupervisorModel->isMember($userid)) {
            if ($content = $request->get('content')) {
                $mkjogoBadwordModel = new MySQL_Mkjogo_BadwordModel($this->mkjogoDb);
                try {
                    $mkjogoBadwordModel->insert(array(
                        'content'   => $content,
                    ));

                    $this->updateCache();
                } catch (Exception $e) {
                    ;
                }

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }
}