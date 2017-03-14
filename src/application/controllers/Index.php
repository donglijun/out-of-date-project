<?php
class IndexController extends Yaf_Controller_Abstract
{
    public function indexAction()
    {
        Yaf_Registry::get('layout')->disableLayout();

        return false;
    }
}
