<?php
class UserController extends AdminController
{
    protected $authActions = array(
        'view'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'ban'       => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $mkjogoDb;

    protected $passportDb;

    protected $redisSession;

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

    protected function getRedisSession()
    {
        if (empty($this->redisSession)) {
            $this->redisSession = Daemon::getRedis('redis-session', 'redis-session');
        }

        return $this->redisSession;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('user/edit.phtml'));
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $user = $request->get('user', 0);

        if ($user) {
            $this->getPassportDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);

            $data = array_merge(
                $userAccountModel->getRow($user),
                $userProfileModel->getRow($user)
            );
        }

        $this->_view->assign(array(
            'data'  => $data,
        ));
    }

    public function updateAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $user = $request->get('user', 0);
        if ($user) {
            $this->getMkjogoDb();
            $this->getPassportDb();

            $adminAccountModel = new MySQL_AdminAccountModel($this->mkjogoDb);
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);

            if ($request->isPost()) {
                if ($email = $request->get('email', '')) {
                    $data['email'] = $email;
                }

                if (!is_null($nickname = $request->get('nickname'))) {
                    $data['nickname'] = $nickname;
                }

                if ($data) {
                    $affectedCount = $userProfileModel->update($user, $data);
                }

                if (($password = $request->get('password', '')) && !$adminAccountModel->authenticate($user)) {
                    $userAccountModel->upgradePassword($user, $password);
                }

                Misc::adminLog(MySQL_AdminLogModel::OP_MODIFY_USER, $data);

                $this->redirect('/admin/user/list');

                return false;
            } else {
                $data = array_merge(
                    $userAccountModel->getRow($user),
                    $userProfileModel->getRow($user)
                );

                $this->_view->assign('user', $user);
            }

            $this->gotoEdit($request->getActionName(), $data);
        }

        return false;
    }

    public function banAction()
    {
        $request = $this->getRequest();

        $ids = Misc::parseIds($request->get('ids'));
        $days = $request->get('days', 14);

        if ($ids) {
            $this->getPassportDb();
            $this->getRedisSession();

            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $userAccountModel->ban($ids, $days);

            $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->redisSession);

            // Clear login session
            foreach ($ids as $id) {
                $mkjogoPassportUser->kick($id);
            }

            Misc::adminLog(MySQL_AdminLogModel::OP_BAN_USER, array(
                'user'  => $ids,
                'days'  => $days,
            ));
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
                'data'  => array(
                    'days'  => $days,
                ),
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/user/list');

        return false;
    }

    public function listAction()
    {
        $result = $where = $filter = $data = $ids = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 50;
        $offset = ($page - 1) * $limit;

        $this->getPassportDb();
        $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
        $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);

        $filter['page'] = '0page0';
        $search_field = $request->get('search_field', '');
        if ($search_field) {
            $filter['search_field'] = $search_field;
        }
        $search_value = $request->get('search_value', '');
        if ($search_value) {
            $where[] = $userAccountModel->quoteIdentifier($search_field) . '=' . $this->passportDb->quote(strtolower(trim($search_value)));
            $filter['search_value'] = $search_value;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $userAccountModel->search('*', $where, 'id DESC', $offset, $limit);

        if ($result['data']) {
            foreach ($result['data'] as $row) {
                $ids[] = $row['id'];
            }

            $rowset = $userProfileModel->getRows($ids);

            foreach ($rowset as $row) {
                $data[$row['user']] = $row;
            }

            foreach ($result['data'] as $key => $val) {
                if (isset($data[$val['id']])) {
                    $result['data'][$key] = array_merge($val, $data[$val['id']]);
                }
            }
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/user/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['now'] = time();

        $this->getView()->assign($result);
    }

//    public function loginAction()
//    {
//        $request = $this->getRequest();
//
//        $from = $request->get('from', '');
//
//        if ($request->isPost()) {
//            $username = $request->get('username', '');
//            $password = $request->get('password', '');
//
//            $result = User::authAccount($username, $password);
//
//            if ($result && MySQL_AdminAccountModel::getModel($this->getMkjogoDb())->authenticate($result['user_id'])) {
//                $account = MySQL_AdminAccountModel::getModel($this->getMkjogoDb())->getRow($result['user_id'], array(
//                    'is_immovable',
//                    'group',
//                    'last_login_on',
//                    'last_login_ip',
//                ));
//                $account = array_merge($result, $account);
//
//                $this->session->admin = $account;
//
//                MySQL_AdminAccountModel::getModel($this->getMkjogoDb())->login($result['user_id'], Misc::getClientIp());
//
//                Misc::adminLog(MySQL_AdminLogModel::OP_LOGIN);
//
//                $this->redirect($from ?: '/admin/index/index');
//
//                return false;
//            } else {
//                $this->_view->assign(array(
//                    'username'  => $username,
//                    'from'      => $from,
//                ));
//            }
//        } else {
//            $this->_view->assign(array(
//                'from'  => $from,
//            ));
//        }
//    }

//    public function logoutAction()
//    {
//        if (isset($this->session->admin)) {
//            Misc::adminLog(MySQL_AdminLogModel::OP_LOGOUT);
//
//            unset($this->session->admin);
//        }
//
//        $this->redirect('/admin/user/login');
//
//        return false;
//    }
}