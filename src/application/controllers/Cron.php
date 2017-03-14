<?php
class CronController extends Yaf_Controller_Abstract
{
    public function init()
    {
        $request = $this->getRequest();

        /**
         * Deny web request
         */
        if (strcasecmp($request->getMethod(), 'CLI') !== 0) {
            die('Application cannot be executed.');
        }

        /**
         * Disable layout
         */
        Yaf_Registry::get('layout')->disableLayout();

//        date_default_timezone_set('Asia/Shanghai');
    }
}