<?php
class CardController extends AdminController
{
    protected $authActions = array(
        'list'  => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', 'hs-db');
        }

        return $this->hsDb;
    }

    public function listAction()
    {
        $result = $filter = array();
        $request = $this->getRequest();

        $offset = 0;
        $limit = 999;

        $cardModel = new MySQL_CardModel($this->getHsDb());
        $result = $cardModel->search('*', null, 'id ASC', $offset, $limit);

        $this->getView()->assign($result);
    }
}