<?php
class ColumnController extends ApiController
{
    protected $authActions = array(
    );

    protected $videoDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    public function listAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $videoColumnModel = new MySQL_Video_ColumnModel($this->getVideoDb());
        $result['data'] = $videoColumnModel->getAllRows();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}