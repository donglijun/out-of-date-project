<?php
class CliController extends Yaf_Controller_Abstract
{
    public function init()
    {
        /**
         * Deny web request
         */
        if (!$this->getRequest()->isCli()) {
            header('HTTP/1.0 403 Forbidden');
            exit();
        }

        /**
         * Disable layout
         */
        Yaf_Registry::get('layout')->disableLayout();

//        date_default_timezone_set('Asia/Shanghai');

        ini_set('default_socket_timeout', -1);

        set_time_limit(0);
    }
}