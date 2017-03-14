<?php
class ApiController extends ApibaseController
{
    protected $session;

    protected $i18n;

    public function init()
    {
//        header('Access-Control-Allow-Origin: *');

        if ($this->getRequest()->isXmlHttpRequest()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $this->session = Yaf_Session::getInstance();

        /**
         * Authenticate client before response
         */
        $this->authenticate();

        /**
         * Disable layout
         */
        Yaf_Registry::get('layout')->disableLayout();

        $this->i18n = Yaf_Registry::get('i18n');

        ini_set('default_socket_timeout', -1);
    }

    protected function authenticate()
    {
        $request = $this->getRequest();
        $action = $request->getActionName();
        $config = Yaf_Registry::get('config');

        $cookieKeyUser = isset($config->session->legacy->u) ? $config->session->legacy->u : 'mkjogo_u';
        $cookieKeySession = isset($config->session->legacy->s) ? $config->session->legacy->s : 'mkjogo_s';
        $cookieKeyName = isset($config->session->legacy->n) ? $config->session->legacy->n : 'mkjogo_n';
        $cookieKeyLang  = isset($config->session->legacy->lang) ? $config->session->legacy->lang : 'mkjogo_lang';

        $logged = false;

        if (($userid = $request->get($cookieKeyUser, 0)) && ($session = $request->get($cookieKeySession, '')) && User::authMkjogoToken($userid, $session, false)) {
            Yaf_Registry::set('mkuser', array(
                'userid'    => $userid,
                'session'   => $session,
                'lang'      => $request->get($cookieKeyLang, 'en'),
                'name'      => $request->get($cookieKeyName, ''),
            ));

            $logged = true;
        } else if (($userid = $request->get('userid', 0)) && ($session = $request->get('session', '')) && User::authMkjogoToken($userid, $session)) {
            Yaf_Registry::set('mkuser', array(
                'userid'    => $userid,
                'session'   => $session,
            ));

            $logged = true;
        } else if (($userid = $request->get('u', 0)) && ($session = $request->get('s', ''))) {
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
}