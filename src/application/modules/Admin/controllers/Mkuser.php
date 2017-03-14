<?php
class MkuserController extends AdminController
{
    protected $authActions = array(
        'view'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'mute'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'ban'       => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
        'today'      => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    protected $accountDb;

    protected function getAccountDb()
    {
        if (empty($this->accountDb)) {
            $this->accountDb = Daemon::getDb('account-db', 'account-db');
        }
        return $this->accountDb;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('mkuser/edit.phtml'));
    }

    public function viewAction()
    {
        $data = array();
        $request = $this->getRequest();

        $user = $request->get('user', 0);

        if ($user) {
            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());
            $data = $mkjogoUserModel->getRow($user, $mkjogoUserModel->getAllFields());
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
            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());

            if ($request->isPost()) {
//                $data = array(
//                    'user_email'    => $request->get('user_email', ''),
//                    'user_password' => $request->get('user_password', ''),
//                    'user_avatar'   => $request->get('user_avatar', ''),
//                    'nickname'      => $request->get('nickname', ''),
//                );
                if ($user_email = $request->get('user_email', '')) {
                    $data['user_email'] = $user_email;
                }

                if (!is_null($request->get('nickname'))) {
                    $data['nickname'] = $request->get('nickname', '');
                }

                if (($user_password = $request->get('user_password', '')) && !MySQL_AdminAccountModel::getModel(Daemon::getDb('mkjogo-db', 'mkjogo-db'))->authenticate($user)) {
                    Yaf_Loader::import('phpbb_helper.php');
                    $data['user_password'] = phpbb_hash($user_password);
                }

                $affectedCount = $mkjogoUserModel->update($user, $data);

                Misc::adminLog(MySQL_AdminLogModel::OP_MODIFY_USER, $data);

                $this->redirect('/admin/mkuser/list');

                return false;
            } else {
                $data = $mkjogoUserModel->getRow($user, $mkjogoUserModel->getAllFields());
                $this->_view->assign('user', $user);
            }

            $this->gotoEdit($request->getActionName(), $data);
        }

        return false;
    }

    public function muteAction()
    {
        $request = $this->getRequest();

        $ids = Misc::parseIds($request->get('ids'));
        $days = $request->get('days', 14);

        if ($ids) {
            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());
            $mkjogoUserModel->mute($ids, $days);

            Misc::adminLog(MySQL_AdminLogModel::OP_MUTE_USER, array(
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/mkuser/list');

        return false;
    }

    public function banAction()
    {
        $request = $this->getRequest();

        $ids = Misc::parseIds($request->get('ids'));
        $days = $request->get('days', 14);

        if ($ids) {
            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());
            $mkjogoUserModel->ban($ids, $days);

            // Clear login session
            foreach ($ids as $id) {
                User::kick($id);
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

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/mkuser/list');

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

        $db = $this->getAccountDb();
        $mkjogoUserModel = new MySQL_MkjogoUserModel($db);

        $filter['page'] = '0page0';
        $search_field = $request->get('search_field', '');
        if ($search_field) {
            $filter['search_field'] = $search_field;
        }
        $search_value = $request->get('search_value', '');
        if ($search_value) {
            $where[] = $mkjogoUserModel->quoteIdentifier($search_field) . '=' . $db->quote(strtolower(trim($search_value)));
            $filter['search_value'] = $search_value;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $mkjogoUserModel->search('*', $where, 'user_id DESC', $offset, $limit);

        $data = $result['data'];
        if ($data) {
            foreach ($data as $row) {
                $ids[] = $row['user_id'];
            }

            $rowset = $mkjogoUserModel->getRows($ids, array(
                'nickname',
                'mute_until',
                'ban_until',
            ));

            foreach ($rowset as $row) {
                $data[$row['user_id']] = $row;
            }

            foreach ($result['data'] as $key => $val) {
                if (isset($data[$val['user_id']])) {
                    $result['data'][$key] = array_merge($val, $data[$val['user_id']]);
                }
            }
        }

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/mkuser/list?' . http_build_query($filter);

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $result['now'] = time();

        $this->getView()->assign($result);
    }

    public function todayAction()
    {
        $result = array(
            'code'  => 404,
        );

        $mkjogoUserModel = new MySQL_MkjogoUserModel($this->getAccountDb());

        $result['data'] = array(
            'total'         => $mkjogoUserModel->getTotalCount(),
            'today_new'     => $mkjogoUserModel->getTodayTotal(),
            'today_active'  => Redis_DauModel::getModel()->count(),
        );
        $result['code'] = 200;

        echo json_encode($result);

        return false;
    }
}