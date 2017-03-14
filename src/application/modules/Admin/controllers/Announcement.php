<?php
class AnnouncementController extends AdminController
{
    const CACHE_EXPIRATION = 3600;

    protected $authActions = array(
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'create'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'publish'   => MySQL_AdminAccountModel::GROUP_ADMIN,
    );

    protected $mkjogoDb;

    protected $cache;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = Daemon::getMemcached('memcached-front', 'memcached-front');
        }

        return $this->cache;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('announcement/edit.phtml'));
    }

    public function listAction()
    {
        $data = $filter = $where = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 20;
        $offset = ($page - 1) * $limit;

        $announcementModel = new MySQL_Mkjogo_AnnouncementModel($this->getMkjogoDb());

        $filter['page'] = '0page0';
        $lang = $request->get('lang', '');
        if ($lang) {
            $where[] = '`lang`=' . $this->mkjogoDb->quote($lang);
            $filter['lang'] = $lang;
        }
        $client = $request->get('client', '');
        if ($client) {
            $where[] = '`client`=' . $this->mkjogoDb->quote($client);
            $filter['client'] = $client;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $result = $announcementModel->search('*', $where, 'id DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/announcement/list?' . http_build_query($filter);
        $result['clients'] = $announcementModel->getDistinct('client');
        $result['langs'] = $announcementModel->getDistinct('lang');

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($limit)
            ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->_view->assign($result);
    }


    public function createAction()
    {
        $data = array();
        $request = $this->getRequest();

        $announcementModel = new MySQL_Mkjogo_AnnouncementModel($this->getMkjogoDb());

        if ($request->isPost()) {
            if (($client = $request->get('client')) !== null) {
                $data['client'] = $client;
            }
            if (($lang = $request->get('lang')) !== null) {
                $data['lang'] = $lang;
            }
            if (($url = $request->get('url')) !== null) {
                $data['url'] = $url;
            }

            if ($url) {
                $announcementModel->insert($data);

                $this->redirect("/admin/announcement/list?client={$client}&lang={$lang}");

                return false;
            }
        }

        $this->_view->assign(array(
            'clients'   => $announcementModel->getDistinct('client'),
            'langs'     => $announcementModel->getDistinct('lang'),
        ));

        $this->gotoEdit($request->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $request = $this->getRequest();

        $id = $request->get('id', 0);

        if ($id) {
            $announcementModel = new MySQL_Mkjogo_AnnouncementModel($this->getMkjogoDb());

            if ($request->isPost()) {
                if (($lang = $request->get('lang')) !== null) {
                    $data['lang'] = $lang;
                }
                if (($client = $request->get('client')) !== null) {
                    $data['client'] = $client;
                }
                if (($url = $request->get('url')) !== null) {
                    $data['url'] = $url;
                }

                $announcementModel->update($id, $data);

                $this->redirect("/admin/announcement/list?client={$client}&lang={$lang}");

                return false;
            } else {
                $data = $announcementModel->getRow($id);
            }

            $this->_view->assign(array(
                'clients'   => $announcementModel->getDistinct('client'),
                'langs'     => $announcementModel->getDistinct('lang'),
            ));

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function publishAction()
    {
        $request = $this->getRequest();

        $id = $request->get('id', 0);

        if ($id) {
            $announcementModel = new MySQL_Mkjogo_AnnouncementModel($this->getMkjogoDb());

            $announcementModel->publish($id, (int) $this->session->admin['user_id']);

            $row = $announcementModel->getRow($id);

            $cacheKey = Misc::cacheKey(array(
                $request->getControllerName(),
                $row['client'],
                $row['lang'],
            ));

            if ($this->getCache()) {
                $data = array(
                    'id'            => $row['id'],
                    'url'           => $row['url'],
                    'published_on'  => $row['published_on'],
                );

                $this->cache->set($cacheKey, $data, self::CACHE_EXPIRATION);
            }
        }

        $this->redirect("/admin/announcement/list");

        return false;
    }
}