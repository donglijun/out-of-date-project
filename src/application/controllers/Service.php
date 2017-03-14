<?php
class ServiceController extends Yaf_Controller_Abstract
{
    protected $authActions = array();

    public function init()
    {
        header('Content-Type: application/json; charset=utf-8');

        /**
         * Authenticate client before response
         */
        $action = $this->getRequest()->getActionName();
        if (in_array($action, $this->authActions)) {
            $this->authClient();
        }

        /**
         * Disable layout
         */
        Yaf_Registry::get('layout')->disableLayout();

//        date_default_timezone_set('Asia/Shanghai');

        ini_set('default_socket_timeout', -1);

        set_time_limit(0);
    }

    protected function authClient()
    {
        /**
         * IP address limit
         */
        if (!preg_match('/^(10|192|127)\.[\d\.]+$/', Misc::getClientIp())) {
            header('HTTP/1.0 403 Forbidden');
            exit();
        }

        return true;
    }
}