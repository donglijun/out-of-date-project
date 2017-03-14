<?php
final class Misc
{
    /**
     * Escape string data to display in browser safely
     *
     * @param string $data
     * @return string
     */
    public static function escape($data)
    {
        return htmlspecialchars($data, ENT_QUOTES);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public static function getClientIp()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (!empty($_SERVER["REMOTE_ADDR"])) {
            $ip = $_SERVER["REMOTE_ADDR"];
        } else {
            $ip = "UNKNOWN";
        }

        return $ip;
    }

    /**
     * Log important actions of administrator
     *
     * @param string $action
     * @param string $content
     * @return mixed
     */
    public static function adminLog($action, $content = '')
    {
        $session = Yaf_Session::getInstance();
        $data = array(
            'user'      => $session->admin['user'],
            'action'    => $action,
            'content'   => json_encode($content),
            'logged_on' => $_SERVER['REQUEST_TIME'],
            'logged_ip' => static::getClientIp(),
        );

        return MySQL_AdminLogModel::getModel(Daemon::getDb('mkjogo-db', 'mkjogo-db'))->insert($data);
    }

    /**
     * Output file content to browser for download purpose
     *
     * @param array $options
     *              $options['file']        Local file name to download
     *              $options['raw']         String data to download, if no $options['file'], will use $options['raw']
     *              $options['fileName']    File name displays in browser's download prompt
     *              $options['fileSize']    Length of file
     *              $options['silent']      Whether or not display an error if file is not available
     *              $options['deleteFile']  Delete source file after download
     *              $options['notExit']     Don't terminate script immediately after download
     *
     */
    public static function httpOutputFile(array $options)
    {
        if ((!isset($options['file']) && !isset($options['raw']))) {
            if (!isset($options['silent']) || !$options['silent']) {
                header('HTTP/1.0 404 Not Found');
            }
            exit();
        }

        if (isset($options['file']) && !is_file($options['file'])) {
            if (!isset($options['silent']) || !$options['silent']) {
                header('HTTP/1.0 403 Forbidden');
            }
            exit();
        }

        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
            $options['fileName'] = urlencode($options['fileName']);
        }
        $options['fileSize'] = isset($options['file']) ? filesize($options['file']) : strlen($options['raw']);

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        header("Pragma: public");
        header('Content-Description: File Transfer');
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false) {
            header('Content-Type: application/force-download; charset=UTF-8');
        } else {
            header('Content-Type: application/octet-stream; charset=UTF-8');
        }
        header('Content-Disposition: attachment; filename="' . $options['fileName'] . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Pragma: public');
        header('Content-Length: ' . $options['fileSize']);
        ob_clean();
        flush();

        if (isset($options['file'])) {
            readfile($options['file']);

            if ($options['deleteFile']) {
                @unlink($options['file']);
            }
        } else {
            echo $options['raw'];
            ob_flush();
            flush();
        }
        if (empty($options['notExit'])) {
            exit();
        }
    }

    /**
     * Parse a string into array
     *
     * @param string $ids Comma delimited integer string
     * @return array
     */
    public static function parseIds($ids)
    {
        $result = array_filter(array_unique(array_map(function($val) {
            return (int) $val;
        }, explode(',', trim($ids, ', ')))));

        sort($result);

        return $result;
    }

    /**
     * Post data to url
     *
     * @param string $url
     * @param array $data
     * @return string
     */
    public static function curlPost($url, $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        return $response;
    }

    /**
     * @param array $data
     * @return string
     */
    public static function formatKeyValue($data)
    {
        $result = '';

        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = is_int($key) ? static::escape($val) : sprintf('%s="%s"', static::escape($key), static::escape($val));
            }

            $result = implode(' ', $data);
        }

        return $result;
    }

    /**
     * Get a database connection
     *
     * @param array $config
     * @return null|PDO
     */
    public static function connectDatabase($config = array())
    {
        $db = null;

        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $config['driver'], $config['host'],
            $config['port'], $config['dbname']);
        $db = new PDO($dsn, $config['username'], $config['password'], (array) $config['driver_options']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $db;
    }

    public static function mkdir($dir)
    {
        $result = true;

        if (!file_exists($dir)) {
            $oldumask = umask(0);

            $result   = mkdir($dir, 0777, TRUE);

            umask($oldumask);
        }

        return $result;
    }

    public static function rmdir($dir)
    {
        $result = false;

        if (is_dir($dir)) {
            system('/bin/rm -rf ' . escapeshellarg($dir), $result);
        }

        return $result;
    }

    public static function log($message, $priority = Zend_Log::NOTICE)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->logger->active) && $config->logger->active) {
            $request = Yaf_Dispatcher::getInstance()->getRequest();

            $logger = Daemon::getLogger();

            $extras = array(
                'timestamp' => date('Y-m-d H:i:s'),
                'ip'        => static::getClientIp(),
                'host'      => $request->isCli() ? $request->getServer('HOSTNAME') : $request->getServer('HTTP_HOST'),
                'uri'       => $request->isCli() ? $request->getServer('SCRIPT_FILENAME') : $request->getServer('REQUEST_URI'),
                'sessid'    => session_id() ?: 'NO SESSION',
            );

            $logger->log($message, (int) $priority, $extras);
        }

        return ;
    }

    public static function cacheKey($data, $delimiter = ':')
    {
        if (!is_array($data)) {
            $data = array($data);
        }

        return implode($delimiter, $data);
    }

    public static function normalizeUrl($data, $withFragment = false)
    {
        $result = '';

        $data = parse_url($data);

        if (isset($data['scheme'])) {
            $result .= strtolower($data['scheme']) . '://';
        }

        if (isset($data['host'])) {
            $result .= strtolower($data['host']);
        }

        if (isset($data['port'])) {
            $result .= ':' . $data['port'];
        }

        if (isset($data['path'])) {
            $result .= $data['path'];
        }

        if (isset($data['query'])) {
            $pieces = explode('&', $data['query']);
            sort($pieces);
            $query = implode('&', $pieces);

//            parse_str($data['query'], $query);
//            ksort($query);
//            $query = http_build_query($query, null, null, PHP_QUERY_RFC3986);
//            $query = urldecode($query);

            $result .= '?' . $query;
        }

        if ($withFragment && isset($data['fragment'])) {
            $result .= '#' . $data['fragment'];
        }

        return $result;
    }

    public static function arrayUnite($a1, $a2)
    {
        $result = array_filter(array_unique(array_merge($a1, $a2)));
        sort($result);

        return $result;
    }

    public static function setcookie($name, $value, $expire = null, $path = null, $domain = null)
    {
        $config = Yaf_Registry::get('config');

        if (isset($config->cookie)) {
            $expire = $expire ?: $config->cookie->ttl + time();
            $path   = $path ?: $config->cookie->path;
            $domain = $domain ?: $config->cookie->domain;
        }

        return setcookie($name, $value, $expire, $path, $domain);
    }

    public static function formatTimeLength($length)
    {
        $result = array();
        $h = $m = $s = 0;

        $h = floor($length / 3600);

        if ($length = $length % 3600) {
            $m = floor($length / 60);

            $s = $length % 60;
        }

        if ($h) {
            $result[] = $h . 'h';
        }

        if ($m) {
            $result[] = $m . 'm';
        }

        if ($s) {
            $result[] = $s . 's';
        }

        return implode(':', $result) ?: '-';
    }

    public static function timezoneOffset()
    {
        $dtz = new DateTimeZone(date_default_timezone_get());
        $dt = new DateTime('now', $dtz);

        return array(
            $dt->format('P'),
            (int) ($dt->format('Z') / 3600),
        );
    }
}