<?php

define('APPLICATION_PATH', dirname(dirname(__FILE__)));

$application = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");

$application->bootstrap()
    ->getDispatcher()->dispatch(new Yaf_Request_Simple());