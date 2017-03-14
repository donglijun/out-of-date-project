<?php
class UserController extends ServiceController
{
    protected $authActions = array(
//        'login'
    );

    protected $passportDb;

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    public function loginAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        if ($user = $request->get('user', 0)) {
            $dauModel = new Redis_DauModel();
            $dauModel->update($user);

            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function existAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $user = $request->get('user', 0);
        $token = $request->get('token');
        $secret = '02579a2d5591ce89badb4970f471beda';

        if ($token == md5($user . $secret)) {
            $this->getPassportDb();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            if ($row = $userAccountModel->getRow($user, array('name'))) {
                $result['code'] = 200;
                $result['username'] = $row['name'];
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 403;
        }

        echo json_encode($result);

        return false;
    }

    public function get_rawAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();
        $config = Yaf_Registry::get('config')->toArray();

        $user = (int) $request->get('user', 0);
        $token = $request->get('token');
        $secret = $config['forum']['secret'];

        if ($user && ($token == md5($user . $secret))) {
            $this->getPassportDb();

            $sql = "SELECT `a`.`id`, `a`.`name`, `a`.`password`, `a`.`status`, `p`.`email`, `p`.`avatar`, `p`.`nickname`, `p`.`gender`, `p`.`birthday`, `p`.`registered_on` FROM `account` `a`, `profile` `p` WHERE `id`=:id AND `id`=`user`";
            $stmt = $this->passportDb->prepare($sql);
            $stmt->execute(array(
                ':id' => $user,
            ));

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result['code'] = 200;
                $result['data'] = $row;
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 403;
        }

        echo json_encode($result);

        return false;
    }
}