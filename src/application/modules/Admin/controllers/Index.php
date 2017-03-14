<?php
class IndexController extends AdminController
{
    protected $authActions = array(
        'index' => MySQL_AdminAccountModel::GROUP_DIRECTOR,
    );

    public function indexAction()
    {
        ;
    }
}