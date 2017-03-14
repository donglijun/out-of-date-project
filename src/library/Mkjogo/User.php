<?php
class Mkjogo_User
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getDetail($users)
    {
        $result = '';

//        $mkjogoUserModel = new MySQL_MkjogoUserModel($this->db);
        $userAccountModel = new MySQL_User_AccountModel($this->db);

        if (is_array($users)) {
            $result = $userAccountModel->getRows($users, array('id', 'name'));
        } else if ((int) $users) {
            if ($rowset = $userAccountModel->getRow($users, array('name'))) {
                $result = $rowset['name'];
            }
        }

        return $result;
    }
}