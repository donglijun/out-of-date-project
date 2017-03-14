<?php
class CardController extends ApiController
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

    public function typesAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getStreamingDb();

        $cardTypeModel = new MySQL_Card_TypeModel($this->streamingDb);
        $result['data'] = $cardTypeModel->getAll(array(
            'id',
            'title',
            'price',
            'game',
        ));

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}