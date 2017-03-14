<?php
class AccountController extends AdminController
{
    protected $authActions = array(
        'create'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $mkjogoDb;

    protected $passportDb;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
            'groups'    => MySQL_AdminAccountModel::getModel($this->getMkjogoDb())->getGroupMap(),
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('account/edit.phtml'));
    }

    public function createAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $user = $request->get('user', 0);
        $group = $request->get('group', MySQL_AdminAccountModel::GROUP_DIRECTOR);

        if ($request->isPost()) {
            $this->getMkjogoDb();
            $this->getPassportDb();

            $adminAccountModel = new MySQL_AdminAccountModel($this->mkjogoDb);
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            $logContent = array(
                'user'      => $user,
                'group'     => $group,
                'result'    => '',
            );

//            $mkjogouserModel = new MySQL_MkjogoUserModel(Daemon::getDb('account-db', 'account-db'));
//            $userInfo = $mkjogouserModel->getRow($user);

            if ($userInfo = $userAccountModel->getRow($user)) {
                $data = array(
                    'user'          => $user,
                    'name'          => $userInfo['name'],
                    'created_on'    => $timestamp,
                    'created_by'    => $this->session->admin['user'],
                    'group'         => $group,
                );

                $logContent['result'] = $adminAccountModel->insert($data);

                Misc::adminLog(MySQL_AdminLogModel::OP_ADD_ADMIN_ACCOUNT, $logContent);

                $this->redirect('/admin/account/list');

                return false;
            }
        }

        if ($user) {
            $this->_view->assign(array(
                'user'  => $user,
            ));
        }

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();

        $users = Misc::parseIds($request->get('users'));

        if ($users) {
            $logContent = array(
                'users' => $users,
            );

            $adminAccountModel = new MySQL_AdminAccountModel($this->getMkjogoDb());
            $logContent['affected'] = $adminAccountModel->delete($users);

            Misc::adminLog(MySQL_AdminLogModel::OP_REMOVE_ADMIN_ACCOUNT, $logContent);
        }

        $this->redirect('/admin/account/list');

        return false;
    }

    public function listAction()
    {
        $result = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $adminAccountModel = new MySQL_AdminAccountModel($this->getMkjogoDb());
        $result = $adminAccountModel->search('*', null, 'created_on DESC', $offset, $limit);

        $result['page'] = $page;
        $filter['page'] = '0page0';
        $result['pageUrlPattern'] = '/admin/account/list?' . http_build_query($filter);
        $result['groups'] = $adminAccountModel->getGroupMap();

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }

    public function signinAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', '');

        if ($request->isPost()) {
            $successful = false;

            $username = $request->get('name', '');
            $password = $request->get('password', '');

            $sessionKey = 'captcha-admin-signin';
            $captchaSession = $this->session->{$sessionKey};

            $userAccountModel = new MySQL_User_AccountModel($this->getPassportDb());

            if (!($captcha_value = $request->get('captcha_value')) || strcasecmp($captcha_value, $captchaSession['word']) || ($captchaSession['timeout'] < $request->getServer('REQUEST_TIME'))) {
                $error = 'Invalid captcha value';
            } else if (!($accountInfo = $userAccountModel->getUnique($username))) {
                $error = 'Invalid user or password';
            } else if ($accountInfo['ban_until'] > $request->getServer('REQUEST_TIME')) {
                $error = 'Banned user';
            } else if ($accountInfo['password']) {
                if (!($successful = password_verify($password, $accountInfo['password']))) {
                    $error = 'Invalid user or password';
                }
            } else if ($accountInfo['old_password']) {
                Yaf_Loader::import('phpbb_helper.php');

                if ($successful = phpbb_check_hash($password, $accountInfo['old_password'])) {
                    $userAccountModel->upgradePassword($accountInfo['id'], $password);
                } else {
                    $error = 'Invalid user or password';
                }
            }

            $this->session->del($sessionKey);

            $adminAccountModel = new MySQL_AdminAccountModel($this->getMkjogoDb());
            if ($successful) {
                if ($adminAccountModel->authenticate($accountInfo['id'])) {
                    $account = $adminAccountModel->getRow($accountInfo['id'], array(
                        'user',
                        'name',
                        'email',
                        'is_immovable',
                        'group',
                        'last_login_on',
                        'last_login_ip',
                    ));

                    $this->session->admin = $account;

                    $adminAccountModel->login($accountInfo['id'], Misc::getClientIp());

                    Misc::adminLog(MySQL_AdminLogModel::OP_LOGIN);

                    $this->redirect($from ?: '/admin/index/index');

                    return false;
                } else {
                    $error = 'No privileges';
                }
            }

            $this->_view->assign(array(
                'name'  => $username,
                'from'  => $from,
                'error' => $error,
            ));
        } else {
            $this->_view->assign(array(
                'from'  => $from,
            ));
        }
    }

    public function signoutAction()
    {
        if (isset($this->session->admin)) {
            Misc::adminLog(MySQL_AdminLogModel::OP_LOGOUT);

            unset($this->session->admin);
        }

        $this->redirect('/admin/account/signin');

        return false;
    }
}