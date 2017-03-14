<?php
class Mkjogo_Passport_User
{
    protected $db;

    protected $redis;

    protected $s3;

    public function __construct($db = null, $redis = null, $s3 = null)
    {
        $this->db = $db;
        $this->redis = $redis;
        $this->s3 = $s3;
    }

    public function signin($userInfo, $extraInfo = array())
    {
        $sessionId = session_id();

        // Set cookie
        Misc::setcookie('u', $userInfo['id']);
        Misc::setcookie('s', $sessionId);
        Misc::setcookie('n', $userInfo['name']);

        // Register session
        $redisUserSessionSet = new Redis_User_Session_SetModel($this->redis);
        $redisUserSessionSet->update($userInfo['id'], $sessionId, Redis_User_Session_SetModel::WEB);

        // Save session data
        $redisUserSessionData = new Redis_User_Session_DataModel($this->redis);
        if (!$redisUserSessionData->fexists($userInfo['id'], 'name')) {
            $redisUserSessionData->mset($userInfo['id'], array(
                'id'        => $userInfo['id'],
                'name'      => $userInfo['name'],
//                'email'     => $userInfo['email'],
            ));
        }

        // Log
        $userSigninLogModel = new MySQL_User_SigninLogModel($this->db);
        $userSigninLogModel->insert(array(
            'user'           => $userInfo['id'],
            'ip'             => Misc::getClientIp(),
            'client'         => isset($extraInfo['client']) ? $extraInfo['client'] : '',
            'client_version' => isset($extraInfo['client_version']) ? $extraInfo['client_version'] : '',
            'created_on'     => isset($extraInfo['timestamp']) ? $extraInfo['timestamp'] : time(),
        ));

        // Send job
        $workload = array_merge($userInfo, $extraInfo);

        $gearmanClient = Daemon::getGearmanClient();
        $gearmanClient->doBackground('user-login', json_encode($workload));

        if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
            Misc::log(sprintf("gearman job (user-login) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
        }
    }

    public function signout($cookies = null)
    {
        $expire = time() - 3600;

        if (isset($_COOKIE['u']) && isset($_COOKIE['s'])) {
            // Un-register session
            $redisUserSessionSet = new Redis_User_Session_SetModel($this->redis);
            $redisUserSessionSet->rem($_COOKIE['u'], $_COOKIE['s']);
        }

        // Clear cookie
        Misc::setcookie('u', '', $expire);
        Misc::setcookie('s', '', $expire);
        Misc::setcookie('n', '', $expire);

        // Clear more cookies
        if (is_array($cookies)) {
            foreach ($cookies as $key) {
                Misc::setcookie($key, '', $expire);
            }
        }

        // Destroy session
        session_destroy();

        return true;
    }

    public function kick($user)
    {
        $redisUserSessionSet = new Redis_User_Session_SetModel($this->redis);
        $redisUserSessionSet->clear($user);
    }
}