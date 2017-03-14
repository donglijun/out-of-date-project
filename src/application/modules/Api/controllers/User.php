<?php
class UserController extends ApiController
{
    protected $authActions = array(
        'pulse',
        'updatelang',
    );

    protected $accountDb;

    protected function getAccountDb()
    {
        if (empty($this->accountDb)) {
            $this->accountDb = Daemon::getDb('account-db', 'account-db');
        }
        return $this->accountDb;
    }

    public function pingAction()
    {
        $request = $this->getRequest();
        $mkuser = Yaf_Registry::get('mkuser');
        $user = isset($mkuser['userid']) ? $mkuser['userid'] : 0;

        if ($user) {
            $lang = strtolower($request->get('lang', ''));

            $ouModel = new Redis_OuModel();
            $ouModel->update($user, $lang);

            // Stat active user by lang
            $key = 'dau:' . $lang . ':' . date('Ymd');
            $offset = $user - 100000;
            $redis = $ouModel->getDb();
            $redis->setBit($key, $offset, 1);
        }

        $this->callback(array(
            'code'  => 200,
        ));

        return false;
    }

    public function pulseAction()
    {
        $mkuser = Yaf_Registry::get('mkuser');

        $code = User::renewalMkjogoToken($mkuser['userid'], $mkuser['session']) ? 200 : 404;

        $this->callback(array(
            'code'  => $code,
        ));

        return false;
    }

    public function updatelangAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        if ($request->isPost() && ($lang = $request->get('lang'))) {
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());
            if ($mkjogoUserModel->checkDetail($userid)) {
                $result['data'] = $mkjogoUserModel->update($userid, array(
                    'lang'  => $lang,
                ));
            } else {
                $result['data'] = $mkjogoUserModel->insertDetail(array(
                    'user_id'   => $userid,
                    'lang'      => $lang,
                ));
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}