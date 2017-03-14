<?php
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initConfig(Yaf_Dispatcher $dispatcher)
    {
        $arrConfig = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $arrConfig);
    }

    public function _initIncludePath(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        set_include_path(get_include_path() . PATH_SEPARATOR . $config->application->library);
    }

    public function _initTimezone(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->date->timezone)) {
            date_default_timezone_set($config->date->timezone);
        }
    }

    public function _initNamespace(Yaf_Dispatcher $dispatcher)
    {
        Yaf_Loader::getInstance()->registerLocalNameSpace(array('Zend'));
    }

    public function _initSession(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config')->toArray();

        if (isset($config['session']) && is_array($config['session'])) {
            if (isset($config['session']['ini']) && is_array($config['session']['ini'])) {
                foreach ($config['session']['ini'] as $key => $val) {
                    ini_set('session.' . $key, $val);
                }
            }

            Yaf_Session::getInstance()->start();
        }
    }

//    public function _initDb(Yaf_Dispatcher $dispatcher)
//    {
//        $config = Yaf_Registry::get('config');
//        $db = Misc::connectDatabase($config->db->toArray());
//        Yaf_Registry::set('db', $db);
//    }

    public function _initDefaultName(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->setDefaultModule('index')
                   ->setDefaultController('index')
                   ->setDefaultAction('index');
    }

//    public function _initMemcached(Yaf_Dispatcher $dispatcher)
//    {
//        $config = Yaf_Registry::get('config');
//        $memcached = new Memcached();
//        $memcached->addServer($config->memcached->host, $config->memcached->port, $config->memcached->weight);
//        Yaf_Registry::set('memcached', $memcached);
//    }
//
//    public function _initMemcache(Yaf_Dispatcher $dispatcher)
//    {
//        $config = Yaf_Registry::get('config');
//        $memcache = new Memcache();
//        $memcache->addServer($config->memcached->host, $config->memcached->port);
//        Yaf_Registry::set('memcache', $memcache);
//    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->layout)) {
            $layout = new LayoutPlugin();
            $dispatcher->registerPlugin($layout);
            Yaf_Registry::set('layout', $layout);
        }

        if (isset($config->i18n)) {
            $i18n = new I18nPlugin();
            $dispatcher->registerPlugin($i18n);
            Yaf_Registry::set('i18n', $i18n);
        }
    }

    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->routes)) {
            $router = $dispatcher->getRouter();
            $router->addConfig($config->routes);
        }
    }

    public function _initView(Yaf_Dispatcher $dispatcher)
    {
//        $config = Yaf_Registry::get('config');
//
//        $view = new View($config->layout->path);
//        $view->setLayout($config->layout->name);
//
//        Yaf_Registry::set('layout', $view);
//
//        $dispatcher->setView($view);
    }

    public function _initAws(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->aws)) {
            ini_set('yaf.use_spl_autoload', 1);

            Yaf_Loader::import($config->application->library . '/aws-autoloader.php');
        }
    }

    public function _initFacebook(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->facebook)) {
            ini_set('yaf.use_spl_autoload', 1);

            Yaf_Loader::import($config->application->library . '/Facebook/autoload.php');
        }
    }

    public function _initGoogle(Yaf_Dispatcher $dispatcher)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->google)) {
            ini_set('yaf.use_spl_autoload', 1);

            Yaf_Loader::import($config->application->library . '/Google/src/Google/autoload.php');
        }
    }
}
