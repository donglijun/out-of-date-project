<?php
class WorkerController extends CliController
{
    // 1 hour
    const WORKER_LIFETIME_MIN   = 3600;

    // 2 hour
    const WORKER_LIFETIME_MAX   = 7200;

    const REG_LOGGER_KEY        = 'GEARMAN_WORKER_LOGGER';

    protected $loggerPattern;

    protected $quitAt;

    protected $loggerFlag;

    protected function getIp()
    {
        $cmd = "/sbin/ifconfig -a|grep inet|grep -v 127.0.0.1|grep -v inet6|awk '{print $2}'|tr -d \"addr:\"";

        return system($cmd);
    }

    protected function dead()
    {
        return $this->quitAt < time();
    }

    protected function getLoggerFlag()
    {
        return (int) date($this->loggerPattern);
    }

    protected function validateLoggerFlag()
    {
        return $this->loggerFlag == $this->getLoggerFlag();
    }

    public function init()
    {
        parent::init();

        $config                 = Yaf_Registry::get('config')->toArray();
        $this->loggerPattern    = isset($config['logger']['pattern']) ? $config['logger']['pattern'] : '';

        $this->loggerFlag       = $this->getLoggerFlag();

        $this->quitAt           = time() + mt_rand(self::WORKER_LIFETIME_MIN, self::WORKER_LIFETIME_MAX);
    }
}