<?php
class LogController extends AdminController
{
    protected $authActions = array(
        'missing_region'    => MySQL_AdminAccountModel::GROUP_SUPER_ADMIN,
    );

    public function init()
    {
        Yaf_Registry::get('layout')->disableLayout();
    }

    public function missing_regionAction()
    {
        $log = '/var/log/vhosts/api.lol.mkjogo.com/nginx/error.log';
        $pattern = "|Class '(.*)' not found|";
        $pattern2 = "|Class '.*_(.*)Model' not found|";

        $data = array();
        $fp = fopen($log, 'rb');

        while (($line = fgets($fp, 1024)) !== false) {
            if (preg_match($pattern, $line, $matches)) {
                $data[$matches[1]] = 1;
            }
        }

        ksort($data);

        Debug::dump(implode(',', array_keys($data)));

        return false;
    }
}