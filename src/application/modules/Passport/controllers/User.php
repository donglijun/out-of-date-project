<?php
class UserController extends ApiController
{
    protected $authActions = array(
//        'changepassword',
//        'changeemail',
//        'info',
        'update_password',
    );

    protected $passportDb;

    protected $streamingDb;

    protected $redisSession;

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getRedisSession()
    {
        if (empty($this->redisSession)) {
            $this->redisSession = Daemon::getRedis('redis-session', 'redis-session');
        }

        return $this->redisSession;
    }
//
//    public function infoAction()
//    {
//        $request = $this->getRequest();
//
//        $result = array(
//            'code'  => 500,
//        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
//
//        $this->getAccountDb();
//        $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);
//
//        $result['code'] = 200;
//        $result['data'] = $mkjogoUserModel->getRow($userid, array('user_email'));
//
//        $this->callback($result);
//
//        return false;
//    }
//
//    public function change_passwordAction()
//    {
//        $request = $this->getRequest();
//
//        $result = array(
//            'code'  => 500,
//        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
//
//        if ($request->isPost()) {
//            $successful = false;
//            $error = '';
//
//            $this->getAccountDb();
//            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);
//            $userInfo = $mkjogoUserModel->getRow($userid, array('user_password'));
//
//            Yaf_Loader::import('phpbb_helper.php');
//
//            $currentPassword = $request->get('current_password', '');
//            $newPassword = $request->get('new_password', '');
//            $newPasswordConfirm = $request->get('new_password_confirm', '');
//
//            if (!phpbb_check_hash($currentPassword, $userInfo['user_password'])) {
//                $error = 'Wrong password';
//            } else if ($newPassword == '') {
//                $error = 'Blank password';
//            } else if ($newPassword != $newPasswordConfirm) {
//                $error = 'New password not match';
//            } else {
//                $newPasswordHash = phpbb_hash($newPassword);
//                $mkjogoUserModel->update($userid, array(
//                    'user_password' => $newPasswordHash,
//                ));
//
//                $successful = true;
//            }
//
//            if ($successful) {
//                $result['code'] = 200;
//            } else {
//                $result['code'] = 400;
//                $result['error'] = $error;
//            }
//        } else {
//            $result['code'] = 404;
//        }
//
//        $this->callback($result);
//
//        return false;
//    }
//
//    public function change_emailAction()
//    {
//        $request = $this->getRequest();
//
//        $result = array(
//            'code'  => 500,
//        );
//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
//
//        if ($request->isPost()) {
//            $successful = false;
//            $error = '';
//
//            $this->getAccountDb();
//            $mkjogoUserModel = new MySQL_MkjogoUserModel($this->accountDb);
//            $userInfo = $mkjogoUserModel->getRow($userid, array('user_password', 'user_email'));
//
//            Yaf_Loader::import('phpbb_helper.php');
//            $emailValidator = new Zend_Validate_EmailAddress();
//
//            $currentPassword = $request->get('current_password', '');
//            $newEmail = strtolower($request->get('new_email', ''));
//            $newEmailConfirm = strtolower($request->get('new_email_confirm', ''));
//
//            if (!phpbb_check_hash($currentPassword, $userInfo['user_password'])) {
//                $error = 'Wrong password';
//            } else if (!$emailValidator->isValid($newEmail)) {
//                $error = 'Invalid email address';
//            } else if ($newEmail != $newEmailConfirm) {
//                $error = 'New email not match';
//            } else {
//                $newEmailHash = phpbb_email_hash($newEmail);
//                $mkjogoUserModel->update($userid, array(
//                    'user_email'    => $newEmail,
//                    'email_hash'    => $newEmailHash,
//                ));
//
//                $successful = true;
//            }
//
//            if ($successful) {
//                $result['code'] = 200;
//            } else {
//                $result['code'] = 400;
//                $result['error'] = $error;
//            }
//        } else {
//            $result['code'] = 404;
//        }
//
//        $this->callback($result);
//
//        return false;
//    }

    public function signinAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

//        $from = $request->get('from', '');

        if ($request->isPost()) {
            $successful = false;

            $username = $request->get('name', '');
            $password = $request->get('password', '');

            $sessionKey = 'captcha-signin';
            $captchaSession = $this->session->{$sessionKey};

            $userAccountModel = new MySQL_User_AccountModel($this->getPassportDb());
            $redisUserLoginMarkModel = new Redis_User_Login_MarkModel($this->getRedisSession());

            if ($redisUserLoginMarkModel->hasMark($username) && (!($captcha_value = $request->get('captcha_value')) || strcasecmp($captcha_value, $captchaSession['word']) || ($captchaSession['timeout'] < $request->getServer('REQUEST_TIME')))) {
                $result['error'][] = array(
                    'element'   => 'captcha_value',
                    'message'   => 'Invalid captcha value',
                );
            } else if (!($accountInfo = $userAccountModel->getUnique($username))) {
                $result['error'][] = array(
                    'element'   => 'name',
                    'message'   => 'Invalid user or password',
                );
            } else if ($accountInfo['ban_until'] > $request->getServer('REQUEST_TIME')) {
                $result['error'][] = array(
                    'message'   => 'Banned user',
                );
            } else if ($accountInfo['password']) {
                if (!($successful = password_verify($password, $accountInfo['password']))) {
                    $result['error'][] = array(
                        'element'   => 'name',
                        'message'   => 'Invalid user or password',
                    );
                }
            } else if ($accountInfo['old_password']) {
                Yaf_Loader::import('phpbb_helper.php');

                if ($successful = phpbb_check_hash($password, $accountInfo['old_password'])) {
                    $userAccountModel->upgradePassword($accountInfo['id'], $password);
                } else {
                    $result['error'][] = array(
                        'element' => 'name',
                        'message' => 'Invalid user or password',
                    );
                }
            }

            if ($successful) {
                $extraInfo = array(
                    'client'         => $request->get('client', ''),
                    'client_version' => $request->get('client_version', ''),
                    'timestamp'      => time(),
                );

                $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->redisSession);
                // Auto signin
                $mkjogoPassportUser->signin($accountInfo, $extraInfo);

                // Clear mark
                $redisUserLoginMarkModel->removeMark($username);

                $result['code'] = 200;
            } else {
                // Set mark
//                $redisUserLoginMarkModel->setMark($username);

                $result['code'] = 403;
            }

            $this->session->del($sessionKey);
        }

        $this->callback($result);

        return false;
    }

    public function signoutAction()
    {
        $request = $this->getRequest();

        $from = $request->get('from', $request->getServer('HTTP_REFERER'));

        $mkjogoPassportUser = new Mkjogo_Passport_User($this->getPassportDb(), $this->getRedisSession());
        $mkjogoPassportUser->signout();

//        if (isset($this->session->user)) {
//            unset($this->session->user);
//        }

        $this->redirect($from ?: '/passport/user/signin');

        return false;
    }

    public function signupAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();

        if ($request->isPost()) {
            $captcha_value = $request->get('captcha_value');

            if ($name = trim($request->get('name'))) {
                $data['name'] = $name;
            }

            if ($email = trim(strtolower($request->get('email')))) {
                $data['email'] = $email;
            }

            if ($password = $request->get('password')) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sessionKey = 'captcha-signup';
            $captchaSession = $this->session->{$sessionKey};

            $this->getPassportDb();
            $this->getStreamingDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            if (!$captcha_value || strcasecmp($captcha_value, $captchaSession['word']) || ($captchaSession['timeout'] < $request->getServer('REQUEST_TIME'))) {
                $result['error'][] = array(
                    'element' => 'captcha_value',
                    'message' => 'Invalid captcha value',
                );
            } else if (!preg_match('/^[A-Za-z]\w{4,29}$/', $name)) {
                $result['error'][] = array(
                    'element'   => 'name',
                    'message'   => 'Incorrect format',
                );
            } else if ($userAccountModel->getUnique($name)) {
                $result['error'][] = array(
                    'element' => 'name',
                    'message' => 'Name has been used by others',
                );
            } else if (!preg_match('/@.+\..+/', $email)) {
                $result['error'][] = array(
                    'element'   => 'email',
                    'message'   => 'Invalid email address',
                );
            } else if (strlen($password) <= 5) {
                $result['error'][] = array(
                    'element' => 'password',
                    'message' => 'Too short',
                );
            } else {
                try {
                    $this->passportDb->beginTransaction();

                    // Create account
                    $userid = $userAccountModel->insert($data);

                    // Create profile
                    $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                    $userProfileModel->insert(array(
                        'user'          => $userid,
                        'email'         => $data['email'],
                        'registered_ip' => Misc::getClientIp(),
                        'registered_on' => $request->getServer('REQUEST_TIME'),
                    ));

                    // Create gift account
                    $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
                    $giftAccountModel->insert(array(
                        'id' => $userid,
                    ));

                    // Create point account
                    $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
                    $pointAccountModel->insert(array(
                        'id' => $userid,
                    ));

                    // Create gold account
                    $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
                    $goldAccountModel->insert(array(
                        'id' => $userid,
                    ));

                    // Create activate code
//                $userActivateLogModel = new MySQL_User_ActivateLogModel($this->passportDb);
//                $userActivateLogModel->insert(array(
//                    'user'          => $userid,
//                    'email'         => $email,
//                    'code'          => md5($request->getServer('REQUEST_TIME')),
//                    'created_on'    => $request->getServer('REQUEST_TIME'),
//                ));

                    //@todo send welcome mail
//                $workload = array(
//                    'username'  => $name,
//                    'email'     => $email,
//                );
//
//                $gearmanClient = Daemon::getGearmanClient();
//                $gearmanClient->doBackground('send-welcome-email', json_encode($workload));
//
//                if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
//                    Misc::log(sprintf("gearman job (lol-match-collect) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
//                }

                    $extraInfo = array(
                        'client'         => $request->get('client', ''),
                        'client_version' => $request->get('client_version', ''),
                        'timestamp'      => $request->getServer('REQUEST_TIME'),
                    );

                    // Auto signin
                    $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->getRedisSession());
                    $mkjogoPassportUser->signin(array(
                        'id' => $userid,
                        'name' => $name,
                    ), $extraInfo);

                    $this->passportDb->commit();

                    $result['code'] = 200;
                } catch (Exception $e) {
                    $this->passportDb->rollBack();
                    Misc::log($e->getMessage(), Zend_Log::ERR);

                    $result['error'][] = array(
                        'message' => 'Internal error',
                    );
                }
            }

            //@todo Unset captcha info
            $this->session->del($sessionKey);
        }

        $this->callback($result);

        return false;
    }

    public function signup_with_facebookAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $data = array();

        if ($request->isPost()) {
            try {
                if (($name = $request->get('name')) !== null) {
                    $data['name'] = $name;
                }

                if (($password = $request->get('password')) !== null) {
                    $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                $this->getPassportDb();
                $this->getStreamingDb();
                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

                $config = Yaf_Registry::get('config');
                \Facebook\FacebookSession::setDefaultApplication($config->facebook->app_id, $config->facebook->app_secret);

                if (!$this->session->facebook) {
                    $result['error'][] = array(
                        'message' => 'Forbidden',
                    );
                } else if (!($fbSession = new \Facebook\FacebookSession($this->session->facebook['access_token']))) {
                    $result['error'][] = array(
                        'message' => 'Invalid facebook session',
                    );
                } else if (!preg_match('/^[A-Za-z]\w{4,29}$/', $name)) {
                    $result['error'][] = array(
                        'element'   => 'name',
                        'message'   => 'Incorrect format',
                    );
                } else if ($userAccountModel->getUnique($name)) {
                    $result['error'][] = array(
                        'element' => 'name',
                        'message' => 'Name has been used by others',
                    );
                } else if (strlen($password) <= 5) {
                    $result['error'][] = array(
                        'element' => 'password',
                        'message' => 'Too short',
                    );
                } else {
                    $accessToken = $this->session->facebook['access_token'];
                    $this->session->del('facebook');

                    // Get user info from facebook
                    $fbRequest = new \Facebook\FacebookRequest($fbSession, 'GET', '/me');
                    $fbUserInfo = $fbRequest->execute()->getGraphObject()->asArray();

//                    $data['email'] = $fbUserInfo['email'];

                    try {
                        $this->passportDb->beginTransaction();

                        // Create account
                        $userid = $userAccountModel->insert($data);

                        // Create profile
                        $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                        $userProfileModel->insert(array(
                            'user'          => $userid,
                            'email'         => strtolower($fbUserInfo['email']),
                            'registered_ip' => Misc::getClientIp(),
                            'registered_on' => $request->getServer('REQUEST_TIME'),
                        ));

                        // Save connection info
                        $info = array(
                            'user'         => $userid,
                            'name'         => $name,
                            'foreign_user' => $fbUserInfo['id'],
                            'access_token' => $accessToken,
                            'expires_in'   => 0,
                            'created_on'   => $request->getServer('REQUEST_TIME'),
                        );

                        $connectionFacebookModel = new MySQL_Connection_FacebookModel($this->passportDb);
                        $connectionFacebookModel->insert($info);

                        // Create gift account
                        $giftAccountModel = new MySQL_Gift_AccountModel($this->streamingDb);
                        $giftAccountModel->insert(array(
                            'id' => $userid,
                        ));

                        // Create point account
                        $pointAccountModel = new MySQL_Point_AccountModel($this->streamingDb);
                        $pointAccountModel->insert(array(
                            'id' => $userid,
                        ));

                        // Create gold account
                        $goldAccountModel = new MySQL_Gold_AccountModel($this->streamingDb);
                        $goldAccountModel->insert(array(
                            'id' => $userid,
                        ));

                        $extraInfo = array(
                            'client'         => $request->get('client', ''),
                            'client_version' => $request->get('client_version', ''),
                            'timestamp'      => $request->getServer('REQUEST_TIME'),
                        );

                        // Auto signin
                        $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->getRedisSession());
                        $mkjogoPassportUser->signin(array(
                            'id' => $userid,
                            'name' => $name,
                        ), $extraInfo);

                        $this->passportDb->commit();

                        $result['code'] = 200;
                    } catch (Exception $e) {
                        $this->passportDb->rollBack();

                        $result['error'][] = array(
                            'message' => 'Internal error',
                        );
                    }
                }
            } catch (Exception $e) {
                $result['error'][] = array(
                    'message' => $e->getMessage(),
                );
            }
        }

        $this->callback($result);

        return false;
    }

    public function bind_with_facebookAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $successful = false;

            try {
                $name = $request->get('name');
                $password = $request->get('password');

                $this->getPassportDb();
                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

                $config = Yaf_Registry::get('config');
                \Facebook\FacebookSession::setDefaultApplication($config->facebook->app_id, $config->facebook->app_secret);

                if (!$this->session->facebook) {
                    $result['error'][] = array(
                        'message' => 'Forbidden',
                    );
                } else if (!($fbSession = new \Facebook\FacebookSession($this->session->facebook['access_token']))) {
                    $result['error'][] = array(
                        'message' => 'Invalid facebook session',
                    );
                } else if (!($accountInfo = $userAccountModel->getUnique($name))) {
                    $result['error'][] = array(
                        'element'   => 'name',
                        'message'   => 'Invalid user or password',
                    );
                } else if ($accountInfo['password']) {
                    if (!($successful = password_verify($password, $accountInfo['password']))) {
                        $result['error'][] = array(
                            'element'   => 'name',
                            'message'   => 'Invalid user or password',
                        );
                    }
                } else if ($accountInfo['old_password']) {
                    Yaf_Loader::import('phpbb_helper.php');

                    if ($successful = phpbb_check_hash($password, $accountInfo['old_password'])) {
                        $userAccountModel->upgradePassword($accountInfo['id'], $password);
                    } else {
                        $result['error'][] = array(
                            'element' => 'name',
                            'message' => 'Invalid user or password',
                        );
                    }
                }

                if ($successful) {
                    $accessToken = $this->session->facebook['access_token'];
                    $this->session->del('facebook');

                    // Get user info from facebook
                    $fbRequest = new \Facebook\FacebookRequest($fbSession, 'GET', '/me');
                    $fbUserInfo = $fbRequest->execute()->getGraphObject()->asArray();

                    // Save connection info
                    $info = array(
                        'user'          => $accountInfo['id'],
                        'name'          => $name,
                        'foreign_user'  => $fbUserInfo['id'],
                        'access_token'  => $accessToken,
                        'expires_in'    => 0,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                    );

                    $connectionFacebookModel = new MySQL_Connection_FacebookModel($this->passportDb);
                    $connectionFacebookModel->insert($info);

                    $extraInfo = array(
                        'client'         => $request->get('client', ''),
                        'client_version' => $request->get('client_version', ''),
                        'timestamp'      => $request->getServer('REQUEST_TIME'),
                    );

                    // Auto signin
                    $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->getRedisSession());
                    $mkjogoPassportUser->signin(array(
                        'id'    => $accountInfo['id'],
                        'name'  => $name,
                    ), $extraInfo);

                    $result['code'] = 200;
                }
            } catch (Exception $e) {
                $result['error'][] = array(
                    'message' => $e->getMessage(),
                );
            }
        }

        $this->callback($result);

        return false;
    }

    public function update_passwordAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');

        if ($request->isPost()) {
            $successful = false;

            $this->getPassportDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $userInfo = $userAccountModel->getRow($currentUser['id'], array('password'));

            $oldPassword = $request->get('old_password', '');
            $newPassword = $request->get('new_password', '');
            $newPasswordConfirm = $request->get('new_password_confirm', '');

            if (!password_verify($oldPassword, $userInfo['password'])) {
                $result['error'][] = array(
                    'element'   => 'old_password',
                    'message'   => 'Wrong password',
                );
            } else if ($newPassword == '') {
                $result['error'][] = array(
                    'element'   => 'new_password',
                    'message'   => 'Blank password',
                );
            } else if (strlen($newPassword) <= 5) {
                $result['error'][] = array(
                    'element' => 'new_password',
                    'message' => 'Too short',
                );
            } else if ($newPassword != $newPasswordConfirm) {
                $result['error'][] = array(
                    'element'   => 'new_password_confirm',
                    'message'   => 'New password not match',
                );
            } else {
                $userAccountModel->upgradePassword($currentUser['id'], $newPassword);

                $successful = true;
            }

            $result['code'] = $successful ? 200 : 400;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function reset_passwordAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $successful = false;

            $this->getPassportDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            if (!($name = $request->get('name'))) {
                $result['error'][] = array(
                    'element'   => 'name',
                    'message'   => 'Empty user name',
                );
            } else if (!($accountInfo = $userAccountModel->getUnique($name))) {
                $result['error'][] = array(
                    'element'   => 'name',
                    'message'   => 'Invalid user',
                );
            } else {
                $userResetPasswordLogModel = new MySQL_User_ResetPasswordLogModel($this->passportDb);

                if (!($logInfo = $userResetPasswordLogModel->getUnique($accountInfo['id'], 'user'))) {
                    $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
                    $profileInfo = $userProfileModel->getRow($accountInfo['id'], array('email'));

                    $logInfo = array(
                        'user'          => $accountInfo['id'],
                        'name'          => $accountInfo['name'],
                        'email'         => $profileInfo['email'],
                        'code'          => md5(time() . $accountInfo['id'] . $profileInfo['email']),
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                        'send_times'    => 1,
                    );

                    $logInfo['id'] = $userResetPasswordLogModel->insert($logInfo);
                }

                if ($logInfo['send_times'] <= MySQL_User_ResetPasswordLogModel::MAX_RESET_TIMES) {
                    $this->_view->assign(array(
                        'resetUrl'  => sprintf('%s%s.%s',Yaf_Registry::get('config')->passport->resetPasswordUrl, $logInfo['id'], $logInfo['code']),
                    ));

                    $workload = array(
                        'username'  => $logInfo['name'],
                        'email'     => $logInfo['email'],
//                        'logId'     => $logInfo['id'],
//                        'code'      => $logInfo['code'],
                        'subject'   => 'Sizin NIKKSY şifre',
                        'body'      => $this->_view->render('mail/reset-password.phtml'),
                    );

                    $gearmanClient = Daemon::getGearmanClient();
                    $gearmanClient->doBackground('send-service-email', json_encode($workload));

                    if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                        Misc::log(sprintf("gearman job (send-service-email) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                    }

                    // Update send_times
                    $userResetPasswordLogModel->send($logInfo['id']);
                }

                $successful = true;
            }

            $result['code'] = $successful ? 200 : 400;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function confirm_reset_passwordAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost() && ($rawCode = $request->get('code'))) {
            list($id, $code,) = explode('.', $rawCode);

            $successful = false;

            $newPassword = $request->get('new_password', '');
            $newPasswordConfirm = $request->get('new_password_confirm', '');

            $this->getPassportDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
            $userResetPasswordLogModel = new MySQL_User_ResetPasswordLogModel($this->passportDb);

            if (!($logInfo = $userResetPasswordLogModel->getRow($id)) || ($logInfo['code'] != $code)) {
                $result['error'][] = array(
                    'element'   => '',
                    'message'   => 'Invalid code',
                );
            } else if ($newPassword == '') {
                $result['error'][] = array(
                    'element'   => 'new_password',
                    'message'   => 'Blank password',
                );
            } else if (strlen($newPassword) <= 5) {
                $result['error'][] = array(
                    'element' => 'new_password',
                    'message' => 'Too short',
                );
            } else if ($newPassword != $newPasswordConfirm) {
                $result['error'][] = array(
                    'element'   => 'new_password_confirm',
                    'message'   => 'New password not match',
                );
            } else {
                $userAccountModel->upgradePassword($logInfo['user'], $newPassword);

                $userResetPasswordLogModel->delete(array($id));

                $successful = true;
            }

            $result['code'] = $successful ? 200 : 400;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function existsAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost() && ($name = $request->get('name'))) {
            $this->getPassportDb();
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            if ($userAccountModel->getUnique($name)) {
                $result['data'] = true;
            } else {
                $result['data'] = false;
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function forgot_usernameAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $successful = false;

            $this->getPassportDb();
            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);

            if (!($email = $request->get('email'))) {
                $result['error'][] = array(
                    'element'   => 'email',
                    'message'   => 'Empty email',
                );
            } else if (!($profileInfo = $userProfileModel->getUsersByEmail($email))) {
                $result['error'][] = array(
                    'element'   => 'email',
                    'message'   => 'Invalid email',
                );
            } else {
                $userForgotUsernameLogModel = new MySQL_User_ForgotUsernameLogModel($this->passportDb);

                if (!($logInfo = $userForgotUsernameLogModel->getUnique($email, 'email'))) {
                    $logInfo = array(
                        'email'         => $email,
                        'created_on'    => $request->getServer('REQUEST_TIME'),
                        'send_times'    => 1,
                    );

                    $logInfo['id'] = $userForgotUsernameLogModel->insert($logInfo);
                }

                $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
                $accountInfo = $userAccountModel->getRows($profileInfo, array('name'));

                foreach ($accountInfo as $row) {
                    $names[] = $row['name'];
                }

                if ($logInfo['send_times'] <= MySQL_User_ForgotUsernameLogModel::MAX_RESET_TIMES) {
                    $this->_view->assign(array(
                        'email' => $logInfo['email'],
                        'names' => $names,
                    ));

                    $workload = array(
                        'username'  => $names[0],
                        'email'     => $logInfo['email'],
                        'subject'   => 'Sizin NIKKSY adları',
                        'body'      => $this->_view->render('mail/forgot-username.phtml'),
                    );

                    $gearmanClient = Daemon::getGearmanClient();
                    $gearmanClient->doBackground('send-service-email', json_encode($workload));

                    if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) {
                        Misc::log(sprintf("gearman job (send-service-email) failed with %d", $gearmanClient->returnCode()), Zend_Log::WARN);
                    }

                    // Update send_times
                    $userForgotUsernameLogModel->send($logInfo['id']);
                }

                $successful = true;
            }

            $result['code'] = $successful ? 200 : 400;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

//    public function simpleauthAction()
//    {
//        $request = $this->getRequest();
//
//        $result = array(
//            'code'  => 500,
//        );
//
//        if ($request->isPost()) {
//            $username = $request->get('name', '');
//            $password = $request->get('password', '');
//
//            $this->getPassportDb();
//            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
//
//            if (($accountInfo = $userAccountModel->getUnique($username)) && password_verify($password, $accountInfo['password'])) {
//                $result['data'] = $accountInfo['id'];
//                $result['code'] = 200;
//            } else {
//                $result['code'] = 404;
//            }
//        } else {
//            $result['code'] = 404;
//        }
//
//        $this->callback($result);
//
//        return false;
//    }

//    public function activateAction()
//    {
//        $request = $this->getRequest();
//
//        $code = $request->get('code', '');
//
//        $this->getPassportDb();
//
//        //@todo validate code
//        $userActivateLogModel = new MySQL_User_ActivateLogModel($this->passportDb);
//        if ($userid = $userActivateLogModel->getUserByCode($code)) {
//            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);
//            $userAccountModel->activate($userid);
//
//            $userActivateLogModel->pass($code);
//        }
//
//        return false;
//    }
}