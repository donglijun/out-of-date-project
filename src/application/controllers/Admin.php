<?php
class AdminController extends ApibaseController
{
    protected $session;

    protected $i18n;

    protected $layout = 'layout-admin';

    public function init()
    {
        $config = Yaf_Registry::get('config');

        if (empty($config->layout->name)) {
            /**
             * Set layout
             */
            Yaf_Registry::get('layout')->setName($this->layout);
        }

        $this->session = Yaf_Session::getInstance();
        $this->session->start();

        /**
         * Authenticate administrator before response
         */
        $this->authenticate();

        $admin = $this->session->admin;
        if (is_array($admin)) {
            $admin['last_request_at'] = $this->getRequest()->getServer('REQUEST_TIME');
            $this->session->admin = $admin;
        }

        header('Content-Type: text/html; charset=utf-8');

        $this->i18n = Yaf_Registry::get('i18n');
    }

    protected function authenticate()
    {
        $action = $this->getRequest()->getActionName();

        if (array_key_exists($action, $this->authActions)) {
            if (!$this->session->admin) {
                $config = Yaf_Registry::get('config');

                $from = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $from = rawurlencode($from);

                return $this->redirect((isset($config->admin->signin) ? $config->admin->signin : "/admin/user/login") . "?from={$from}");
            } else if (!($this->session->admin['is_immovable']) && (!$this->session->admin['group'] || ($this->session->admin['group'] > $this->authActions[$action]))) {
                header('HTTP/1.0 403 Forbidden');
                exit();
            }
        }

        return true;
    }

    public function indexAction()
    {
        $this->forward('admin', 'index', 'index');
        return false;
    }
}