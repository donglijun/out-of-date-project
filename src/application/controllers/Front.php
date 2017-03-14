<?php
class FrontController extends ApibaseController
{
    protected $session;

    protected $i18n;

    protected $layout = 'layout-front';

    public function init()
    {
        /**
         * Set layout
         */
        Yaf_Registry::get('layout')->setName($this->layout);

        $this->session = Yaf_Session::getInstance();
//        $this->session->start();

        /**
         * Authenticate user before response
         */
        $this->authenticate();

        if ($this->getRequest()->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');

            Yaf_Registry::get('layout')->disableLayout();
        } else {
            header('Content-Type: text/html; charset=utf-8');
        }

        $this->i18n = Yaf_Registry::get('i18n');
    }

    protected function authenticate()
    {
        $request = $this->getRequest();
        $action = $request->getActionName();
        $logged = false;

        if (($userid = $request->get('u', 0)) && ($session = $request->get('s', ''))) {
            $redisSession = Daemon::getRedis('redis-session', 'redis-session');

            $redisUserSessionSet = new Redis_User_Session_SetModel($redisSession);
            if ($redisUserSessionSet->mexists($userid, $session)) {
                $redisUserSessionData = new Redis_User_Session_DataModel($redisSession);
                Yaf_Registry::set('user', $redisUserSessionData->getall($userid));
                Yaf_Registry::set('user-session', $redisUserSessionData);

                $logged = true;
            }
        }

        if (in_array($action, $this->authActions) && !$logged) {
            header('HTTP/1.0 403 Forbidden');
            exit();
        }

        return true;
    }

    protected function goto404()
    {
        $this->getView()->display(APPLICATION_PATH . '/application/views/error/404.phtml');

        exit();
    }
}