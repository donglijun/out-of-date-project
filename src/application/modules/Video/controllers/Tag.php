<?php
class TagController extends ApiController
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

        $videoTagModel = new MySQL_Video_TagModel($this->getVideoDb());
        $result['data'] = $videoTagModel->getRowsByGroup();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function listbygroupAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $videoTagModel = new MySQL_Video_TagModel($this->getVideoDb());
        $result['data'] = $videoTagModel->getRowsByGroup($request->get('group', ''));

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function grouplistAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $videoTagGroupModel = new MySQL_Video_TagGroupModel($this->getVideoDb());
        $result['data'] = $videoTagGroupModel->getAllRows();

        $result['code'] = 200;

        $this->callback($result);

        return false;
    }
}