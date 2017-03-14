<?php
class ConnectionController extends FrontController
{
    protected $authActions = array(
    );

    protected $passportDb;

    protected $redisSession;

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getRedisSession()
    {
        if (empty($this->redisSession)) {
            $this->redisSession = Daemon::getRedis('redis-session', 'redis-session');
        }

        return $this->redisSession;
    }

    public function init()
    {
        parent::init();

        $config = Yaf_Registry::get('config');

        \Facebook\FacebookSession::setDefaultApplication($config->facebook->app_id, $config->facebook->app_secret);

        Yaf_Registry::get('layout')->disableLayout();
    }

    public function fb_loginAction()
    {
        $request = $this->getRequest();

        $info = array(
            ':m'    => strtolower($request->getModuleName()),
            ':c'    => strtolower($request->getControllerName()),
            ':a'    => 'fb_handle_login',
        );
        $query = array();

        if ($from = $request->get('from', '/uc/')) {
            $query['from'] = urlencode($from);
        }

        $router = Yaf_Dispatcher::getInstance()->getRouter();
        $target = $request->get('target') ?: 'https://' . $_SERVER['HTTP_HOST'] . $router->getRoute($router->getCurrentRoute())->assemble(
                $info,
                $query
            );

        $helper = new \Facebook\FacebookRedirectLoginHelper($target);
        $scope = array('public_profile', 'email');
        $loginUrl = $helper->getLoginUrl($scope);

        $this->redirect($loginUrl);

        return false;
    }

    public function fb_login_urlAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $router = Yaf_Dispatcher::getInstance()->getRouter();
        $target = $request->get('target') ?: 'https://' . $_SERVER['HTTP_HOST'] . $router->getRoute($router->getCurrentRoute())->assemble(
                array(
                    ':m'    => strtolower($request->getModuleName()),
                    ':c'    => strtolower($request->getControllerName()),
                    ':a'    => 'fb_handle_login',
                ),
                array()
            );

        $helper = new \Facebook\FacebookRedirectLoginHelper($target);
        $scope = array('public_profile', 'email');
        $result['data'] = $helper->getLoginUrl($scope);
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function fb_handle_loginAction()
    {
        $request = $this->getRequest();

        $info = array(
            ':m'    => strtolower($request->getModuleName()),
            ':c'    => strtolower($request->getControllerName()),
            ':a'    => 'fb_handle_login',
        );
        $query = array();

        if ($from = $request->get('from', '/uc/')) {
            $query['from'] = urlencode($from);
        }

        $router = Yaf_Dispatcher::getInstance()->getRouter();
        $target = $request->get('target') ?: 'https://' . $_SERVER['HTTP_HOST'] . $router->getRoute($router->getCurrentRoute())->assemble(
                $info,
                $query
            );

        $helper = new \Facebook\FacebookRedirectLoginHelper($target);
        try {
            if ($fbSession = $helper->getSessionFromRedirect()) {
                $fbRequest = new \Facebook\FacebookRequest($fbSession, 'GET', '/me');
                $fbUserInfo = $fbRequest->execute()->getGraphObject()->asArray();

                $connectionFacebookModel = new MySQL_Connection_FacebookModel($this->getPassportDb());
                if ($connectionInfo = $connectionFacebookModel->getRowByForeignUser($fbUserInfo['id'])) {
                    // Update token
                    $connectionFacebookModel->updateByUser($connectionInfo['user'], array(
                        'foreign_user'  => $fbUserInfo['id'],
                        'access_token'  => $fbSession->getToken(),
//                        'expires_in'    => 0,
                    ));

                    $accountInfo = array(
                        'id'    => $connectionInfo['user'],
                        'name'  => $connectionInfo['name'],
                    );

                    $extraInfo = array(
                        'client'         => $request->get('client', ''),
                        'client_version' => $request->get('client_version', ''),
                        'timestamp'      => $request->getServer('REQUEST_TIME'),
                    );

                    $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->getRedisSession());
                    // Auto signin
                    $mkjogoPassportUser->signin($accountInfo, $extraInfo);

                    $this->redirect($from);
                } else {
                    //Save token
                    $this->session->facebook = array(
                        'access_token'  => $fbSession->getToken(),
                    );

                    $config = Yaf_Registry::get('config');
                    $this->redirect($config->facebook->signup_url);
                }
            } else {
                echo "Can not get session.";
            }
        } catch (\Facebook\FacebookRequestException $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }

    public function fb_verify_client_tokenAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost() && ($accessToken = $request->get('access_token'))) {
            if (!($fbSession = new \Facebook\FacebookSession($accessToken))) {
                $result['code'] = 403;
                $result['error'][] = array(
                    'message' => 'Invalid facebook session',
                );
            } else {
                $fbRequest = new \Facebook\FacebookRequest($fbSession, 'GET', '/me');
                $fbUserInfo = $fbRequest->execute()->getGraphObject()->asArray();

                $this->getPassportDb();
                $this->getRedisSession();

                $connectionFacebookModel = new MySQL_Connection_FacebookModel($this->passportDb);
                if ($connectionInfo = $connectionFacebookModel->getRowByForeignUser($fbUserInfo['id'])) {
                    // Update token
                    $connectionFacebookModel->updateByUser($connectionInfo['user'], array(
                        'foreign_user'  => $fbUserInfo['id'],
                        'access_token'  => $fbSession->getToken(),
//                        'expires_in'    => 0,
                    ));

                    $accountInfo = array(
                        'id'    => $connectionInfo['user'],
                        'name'  => $connectionInfo['name'],
                    );

                    $extraInfo = array(
                        'client'         => $request->get('client', ''),
                        'client_version' => $request->get('client_version', ''),
                        'timestamp'      => $request->getServer('REQUEST_TIME'),
                    );

                    $mkjogoPassportUser = new Mkjogo_Passport_User($this->passportDb, $this->redisSession);
                    // Auto signin
                    $mkjogoPassportUser->signin($accountInfo, $extraInfo);

                    $result['code'] = 200;
                    $result['error'][] = array(
                        'message' => 'ok',
                    );
                } else {
                    //Save token
                    $this->session->facebook = array(
                        'access_token'  => $fbSession->getToken(),
                    );

                    $result['code'] = 302;
                    $result['error'][] = array(
                        'message' => 'Redirect to sign up',
                    );
                }
            }
        } else {
            $result['code'] = 404;
            $result['error'][] = array(
                'message' => 'Invalid request',
            );
        }

        $this->callback($result);

        return false;
    }

    public function fb_user_infoAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($this->session->facebook) {
            $session = new \Facebook\FacebookSession($this->session->facebook['access_token']);

            $fbRequest = new \Facebook\FacebookRequest($session, 'GET', '/me');
            $fbResponse = $fbRequest->execute();
            $graphObject = $fbResponse->getGraphObject();

            Debug::dump($graphObject->asArray());
        }

        return false;
    }
}