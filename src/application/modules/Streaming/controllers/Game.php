<?php
class GameController extends ApiController
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

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);
        $result['data'] = $streamingGameModel->getGameMap();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function filterAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $keyword = $request->get('keyword');

        if (strlen($keyword) >= 3) {
            $this->getStreamingDb();

            $where = sprintf("`name` LIKE '%s%%'", $keyword);

            $streamingGameModel = new MySQL_Streaming_GameModel($this->streamingDb);
            $data = $streamingGameModel->search('`id`, `name`, `icon`, `logo`', $where, '`name` ASC', 0, 99);

            $result['data'] = $data['data'];
        }

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}