<?php
class RecommendedController extends AdminController
{
    protected $authActions = array(
        'create'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'update'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'delete'    => MySQL_AdminAccountModel::GROUP_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
    );

    protected $hsDb;

    protected function getHsDb()
    {
        if (empty($this->hsDb)) {
            $this->hsDb = Daemon::getDb('hs-db', 'hs-db');
        }

        return $this->hsDb;
    }

    protected function gotoEdit($action, $data)
    {
        $this->_view->assign(array(
            'action'    => $action,
            'data'      => $data,
        ));

        Yaf_Registry::get('layout')->displayOther($this->getView()->render('recommended/edit.phtml'));
    }

    public function createAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $recommendedModel = new MySQL_RecommendedModel($this->getHsDb());
        if ($request->isPost()) {
            $data = array(
                'lang'          => $request->get('lang', ''),
                'class'         => $request->get('class', ''),
                'category'      => $request->get('category', 0),
                'deck'          => $request->get('deck', 0),
                'ranking'       => $request->get('ranking', 1),
                'created_on'    => $timestamp,
                'modified_on'   => $timestamp,
            );

            $id = $recommendedModel->insert($data);

            $this->redirect('/admin/recommended/list');

            return false;
        } else {
            $deck = $request->get('deck', 0);

            if ($deck) {
                $data = MySQL_DeckModel::getModel($this->getHsDb())->getRow($deck, array('lang', 'class', 'category'));
                $data['deck'] = $deck;
            }

            if (!isset($data['ranking'])) {
                $data['ranking'] = 1;
            }
        }

        $this->gotoEdit($this->getRequest()->getActionName(), $data);

        return false;
    }

    public function updateAction()
    {
        $data = array();
        $timestamp = time();
        $request = $this->getRequest();

        $id = $request->get('id', 0);
        if ($id) {
            $recommendedModel = new MySQL_RecommendedModel($this->getHsDb());

            if ($request->isPost()) {
                $data = array(
                    'lang'          => $request->get('lang', ''),
                    'class'         => $request->get('class', ''),
                    'category'      => $request->get('category', 0),
                    'deck'          => $request->get('deck', 0),
                    'ranking'       => $request->get('ranking', 1),
                    'modified_on'   => $timestamp,
                );

                $affectedCount = $recommendedModel->update($id, $data);

                $this->redirect('/admin/recommended/list');

                return false;
            } else {
                $data = $recommendedModel->getRow($id);
                $this->_view->assign('id', $id);
            }

            $this->gotoEdit($request->getActionName(), $data);
        } else {
            $this->forward('create');
        }

        return false;
    }

    public function deleteAction()
    {
        $request = $this->getRequest();

        $ids = Misc::parseIds($request->get('ids'));
        if ($ids) {
            $recommendedModel = new MySQL_RecommendedModel($this->getHsDb());
            $affectedCount = $recommendedModel->delete($ids);
        }

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            $result = array(
                'code'  => 200,
                'data'  => array(
                    'affected'  => $affectedCount,
                ),
            );

            echo json_encode($result);

            return false;
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/recommended/list');

        return false;
    }

    public function listAction()
    {
        $result = $where = $filter = array();
        $request = $this->getRequest();

        $page = intval($request->get('page'));
        $page = $page < 1 ? 1 : $page;
        $limit = intval($request->get('limit'));
        $limit = $limit ?: 50;
        $offset = ($page - 1) * $limit;

        $db = $this->getHsDb();

        $filter['page'] = '0page0';
        $lang = $request->get('lang');
        if (!is_null($lang)) {
            $where[] = '`lang`=' . $db->quote($lang);
            $filter['lang'] = $lang;
        }
        $class = $request->get('class');
        if (!is_null($class)) {
            $where[] = '`class`=' . $db->quote($class);
            $filter['class'] = $class;
        }
        $category = $request->get('category');
        if (is_numeric($category)) {
            $where[] = '`category`=' . $db->quote($category);
            $filter['category'] = $category;
        }
        $deck = $request->get('deck');
        if (is_numeric($deck)) {
            $where[] = '`deck`=' . $db->quote($deck);
            $filter['deck'] = $deck;
        }
        $where = $where ? implode(' AND ', $where) : '';

        $recommendedModel = new MySQL_RecommendedModel($this->getHsDb());
        $result = $recommendedModel->search('*', $where, 'ranking ASC, created_on DESC', $offset, $limit);

        $result['filter'] = $filter;
        $result['pageUrlPattern'] = '/admin/recommended/list?' . http_build_query($filter);
        $result['classes'] = MySQL_DeckModel::getModel($this->getHsDb())->getClassMap();
        $result['langs'] = MySQL_DeckModel::getModel($this->getHsDb())->getLangMap();

        $paginator = Zend_Paginator::factory($result['total_found']);
        $paginator->setCurrentPageNumber($page)
                  ->setItemCountPerPage($limit)
                  ->setPageRange(10);
        $result['paginator'] = $paginator;

        $this->getView()->assign($result);
    }
}