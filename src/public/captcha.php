<?php
// Init application
define('APPLICATION_PATH', dirname(dirname(__FILE__)));

$application = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");

$application->bootstrap();

// Init session
$session = Yaf_Session::getInstance();

// Get config
$config = Yaf_Registry::get('config');

// Get namespace
$ns = Yaf_Dispatcher::getInstance()->getRequest()->get('ns') ?: 'default';
$sessionKey = 'captcha-' . $ns;

// Generate image
$captcha = new Captcha($config->captcha->toArray());
$image = $captcha->generate();

// Save session
$session->{$sessionKey} = array(
    'word'      => $captcha->getWord(),
    'timeout'   => $captcha->getTimeout(),
    'id'        => $captcha->getId(),
);

// Set header
if (empty($image)) {
    if (substr(PHP_SAPI, 0, 3) == 'cgi') {
        header('Status: 404 Not Found');
    } else {
        header('HTTP/1.1 404 Not Found');
    }

    return;
}

// Send image to browser
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);