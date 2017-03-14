<?php
class TestController extends FrontController
{
    protected $authActions = array(
        'profile',
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

    public function echoAction()
    {
        echo 'hello';

        return false;
    }

    public function signinAction()
    {
        ;
    }

    public function signupAction()
    {
        ;
    }

    public function signupwithfacebookAction()
    {
        ;
    }

    public function profileAction()
    {
        Debug::dump($_COOKIE);

        Debug::dump($this->session);

        return false;
    }

    public function testAction()
    {
        $request = $this->getRequest();

        Debug::dump($this->session);
        Debug::dump($this->session->user);
        Debug::dump($this->session->facebook);

        $router = Yaf_Dispatcher::getInstance()->getRouter();
        $url = '//' . $_SERVER['HTTP_HOST'] . $router->getRoute($router->getCurrentRoute())->assemble(
                array(
                    ':m'    => strtolower($request->getModuleName()),
                    ':c'    => strtolower($request->getControllerName()),
                    ':a'    => 'fb_handle_login',
                ),
                array(
                    'from'  => urlencode('//mkhs.local.dev/admin/test/path?a=b'),
                )
            );
        Debug::dump($url);

        return false;
    }

    public function changepasswordAction()
    {
        ;
    }

    public function resetAction()
    {
        ;
    }

    public function setnewAction()
    {
        $request = $this->getRequest();

        $this->_view->assign(array(
            'code'  => $request->get('code'),
        ));
    }

    public function forgotnameAction()
    {
        ;
    }
}