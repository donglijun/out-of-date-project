<?php
final class Daemon
{
    /**
     * @param string $configKey
     * @param string $registryKey
     * @return mixed|null|Redis
     */
    public static function getRedis($configKey = 'redis', $registryKey = 'redis')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey};

                $result = new Redis();
                $result->connect($config->host, $config->port, $config->timeout);

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getMemcached($configKey = 'memcached', $registryKey = 'memcached')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey}->toArray();
                $server = array();

                $result = new Memcached();

                if (isset($config['cluster']) && is_array($config['cluster'])) {
//                    foreach ($config['cluster'] as $server) {
//                        $result->addServer($server['host'], $server['port']);
//                    }
                    $result->addServers($config['cluster']);
                } else if (isset($config['host'])) {
                    $result->addServer($config['host'], $config['port'], $config['weight']);
                }

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getMemcache($configKey = 'memcached', $registryKey = 'memcache')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey}->toArray();
                $server = array();

                $result = new Memcache();

                if (isset($config['cluster']) && is_array($config['cluster'])) {
                    foreach ($config['cluster'] as $server) {
                        $result->addServer($server['host'], $server['port'], true, $server['weight']);
                    }
                } else if (isset($config['host'])) {
                    $result->addServer($config['host'], $config['port'], $config['weight']);
                }

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getLogger($configKey = 'logger', $registryKey = 'logger')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey};

                $result = new Zend_Log();

                if (isset($config->priority)) {
                    $filter = new Zend_Log_Filter_Priority((int) $config->priority);
                    $result->addFilter($filter);
                }

                $format = '[%timestamp%] [%priorityName%] [client %ip%] %host% %uri% (%sessid%): %message%' . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);

                $logFileName = sprintf('%s/%s.log', $config->path, date($config->pattern));
                $writer = new Zend_Log_Writer_Stream($logFileName);
                $writer->setFormatter($formatter);

                $result->addWriter($writer);

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getGearmanClient($configKey = 'gearmand', $registryKey = 'gearman-client')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey}->toArray();
                $server = array();

                $result = new GearmanClient();

                if (isset($config['cluster']) && is_array($config['cluster'])) {
                    foreach ($config['cluster'] as $server) {
                        $result->addServer($server['host'], $server['port']);
                    }
                } else if (isset($config['host'])) {
                    $result->addServer($config['host'], $config['port']);
                }

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getGearmanWorker($configKey = 'gearmand', $registryKey = 'gearman-worker')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey}->toArray();
                $server = array();

                $result = new GearmanWorker();

                if (isset($config['cluster']) && is_array($config['cluster'])) {
                    foreach ($config['cluster'] as $server) {
                        $result->addServer($server['host'], $server['port']);
                    }
                } else if (isset($config['host'])) {
                    $result->addServer($config['host'], $config['port']);
                }

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getMongoClient($configKey = 'mongos', $registryKey = 'mongos')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey}->toArray();
                $servers = $server = array();

                if (isset($config['cluster']) && is_array($config['cluster'])) {

                    foreach ($config['cluster'] as $server) {
                        if (isset($server['host'])) {
                            $servers[] = $server['host'] . (isset($server['port']) ? ':' . $server['port'] : '');
                        }
                    }

                    $servers = implode(',', $servers);
                } else if (isset($config['host'])) {
                    $servers = $config['host'] . (isset($config['port']) ? ':' . $config['port'] : '');
                }

                $connectionString = 'mongodb://' . $servers;

                $result = new MongoClient($connectionString, isset($config['options']) && is_array($config['options']) ? $config['options'] : array());

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getDb($configKey = 'db', $registryKey = 'db', $delimiter = '_', $suffix = null)
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey};

                $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $config->driver, $config->host,
                    $config->port, $suffix ? $config->dbname . $delimiter . $suffix : $config->dbname);
                $result = new PDO($dsn, $config->username, $config->password, $config->driver_options->toArray());
                $result->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getSphinxQL($configKey = 'sphinxql', $registryKey = 'sphinxql')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey};

                $dsn = sprintf('%s:host=%s;port=%s', $config->driver, $config->host, $config->port);
                $result = new PDO($dsn);
                $result->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }

    public static function getRtmpClient($configKey = 'rtmp-client', $registryKey = 'rtmp-client')
    {
        $result = null;

        if (!($result = Yaf_Registry::get($registryKey))) {
            $config = Yaf_Registry::get('config');

            if (isset($config->{$configKey})) {
                $config = $config->{$configKey};

                Yaf_Loader::import('Rtmp/RtmpClient.class.php');
                $result = new RTMPClient();

                Yaf_Registry::set($registryKey, $result);
            }
        }

        return $result;
    }
}