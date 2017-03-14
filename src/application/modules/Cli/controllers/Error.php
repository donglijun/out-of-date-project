<?php
class ErrorController extends Yaf_Controller_Abstract
{
    public function errorAction()
    {
        Yaf_Registry::get('layout')->disableLayout();

        $exception = $this->getRequest()->getException();

        switch ($exception->getCode()) {
            case YAF_ERR_NOTFOUND_MODULE:
            case YAF_ERR_NOTFOUND_CONTROLLER:
            case YAF_ERR_NOTFOUND_ACTION:
            case YAF_ERR_NOTFOUND_VIEW:
                header('HTTP/1.0 404 Not Found');
                break;
            default:
                Misc::log($exception->getMessage() . "\n" . $exception->getTraceAsString(), Zend_Log::ERR);
                header('HTTP/1.0 500 Internal Error');
                break;
        }

        return false;
    }
}