<?php
final class User
{
    public static function getFromCookie()
    {
        $result = array();

        if (isset($_COOKIE['mkjogo_u'])) {
            $result['userid'] = $_COOKIE['mkjogo_u'];
        }

        if (isset($_COOKIE['mkjogo_s'])) {
            $result['session'] = $_COOKIE['mkjogo_s'];
        }

        if (isset($_COOKIE['mkjogo_n'])) {
            $result['name'] = $_COOKIE['mkjogo_n'];
        }

        if (isset($_COOKIE['mkjogo_lang'])) {
            $result['lang'] = $_COOKIE['mkjogo_lang'];
        }

        return $result;
    }

    public static function kick($uid)
    {
        $keySuffix = array('_c', '_s');
        $memcache = Daemon::getMemcache();

        foreach ($keySuffix as $suffix) {
            $ck = $uid . $suffix;
            $memcache->delete($ck);
        }
    }

    /**
     * Authenticate client user from account.mkjogo.com
     *
     * @param int $uid User id
     * @param string $token Session id
     * @return bool
     */
    public static function authMkjogoToken($uid, $token, $isClient = true)
    {
        $timestamp = time();
        $ck = $uid . ($isClient ? '_c' : '_s');

        $memcache = Daemon::getMemcache();
        $data = $memcache->get($ck);

        $data = is_string($data) ? unserialize($data) : $data;

        if (is_array($data)) {
            $data = explode(',', current($data));

            foreach ($data as $val) {
                $expiration = substr($val, 32);
                $session = substr($val, 0, 32);

                if ($session === $token && $expiration > $timestamp) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function renewalMkjogoToken($uid, $token, $isClient = true)
    {
        $timestamp = time();
        $ck = $uid . ($isClient ? '_c' : '_s');

        $memcache = Daemon::getMemcache();
        $data = $memcache->get($ck);

        $data = is_string($data) ? unserialize($data) : $data;

        if (is_array($data)) {
            $data       = explode(',', current($data));
            $newdata    = array();
            $newval     = '';

            foreach ($data as $val) {
                $expiration = substr($val, 32);
                $session = substr($val, 0, 32);

                if ($session === $token) {
                    $expiration = $timestamp + 86400;
                    $newval = $session . $expiration;
                } else {
                    $newdata[] = $val;
                }
            }

            if ($newval) {
                $newdata[] = $newval;

                $memcache->set($ck, array(
                    implode(',', $newdata),
                    $timestamp,
                    86400,
                ), 0, 86400);

                $userdata = $memcache->get($uid);
                $memcache->set($uid, array(
                    $userdata,
                    $timestamp,
                    86400,
                ), 0, 86400);
            }

            return true;
        }

        return false;
    }

    /**
     * Authenticate administrator user through account API
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function authAccount($username, $password)
    {
        $result = false;

        $config = Yaf_Registry::get('config');

        $data = array(
            'username'  => $username,
            'password'  => $password,
            'sign'      => md5($username . (isset($config->session->legacy->salt) ? $config->session->legacy->salt : 'mkjogokaka')  . $password),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config->url->account->login);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);

        if ($response !== false) {
            $response = json_decode($response, true);
            if ($response['code'] === 0 && isset($response['result'])) {
                $result = $response['result'];
            }
        }

        return $result;
    }

    /**
     * Retrieve user data through account API
     *
     * @param int $uid
     * @return array
     */
//    public static function getInfo($uid)
//    {
//        $result = array();
//
//        if ($uid) {
//            $config = Yaf_Registry::get('config');
//            $data = Misc::curlPost($config->url->account->userGetData, array(
//                'uid'   => $uid,
//            ));
//            $data = json_decode($data, true);
//            $result = is_array($data['result']) ? $data['result'] : $result;
//        }
//
//        return $result;
//    }

    /**
     * Get user names by user ids
     *
     * @param array $uids A group of user ids
     * @return array
     */
//    public static function getNames($uids)
//    {
//        $result = array();
//
//        $uids = is_array($uids) ? implode(',', $uids) : $uids;
//        if ($uids) {
//            $url = 'http://account.mkjogo.com/api/user/getnames?uids=' . $uids;
//            $data = file_get_contents($url);
//            $data = json_decode($data, true);
//            $result = is_array($data['result']) ? $data['result'] : $result;
//        }
//
//        return $result;
//    }
}