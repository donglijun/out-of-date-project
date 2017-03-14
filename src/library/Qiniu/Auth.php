<?php
class Qiniu_Auth
{
    protected $ak;
    protected $sk;

    public function __construct($ak, $sk)
    {
        $this->ak = $ak;
        $this->sk = $sk;
    }

    public function sign($data)
    {
        $sign = hash_hmac('sha1', $data, $this->sk, true);

        return $this->ak . ':' . Qiniu_Utils::encode($sign);
    }

    public function signWithData($data)
    {
        $data = Qiniu_Utils::encode($data);

        return $this->sign($data) . ':' . $data;
    }

    public function verifyCallback($auth, $url, $body)
    {
        $data = '';
        $parts = parse_url($url);

        if (isset($parts['path'])) {
            $data .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $data .= '?' . $parts['query'];
        }

        $data .= "\n";

        $data .= $body;

        $token = 'QBox' . $this->sign($data);

        return $token === $auth;
    }

    public function token($bucket, $policy = array())
    {
        $defaultPolicy = array(
            'scope'                 => $bucket,
            'deadline'              => time() + 3600,
            'callbackUrl'           => null,
            'callbackBody'          => null,
            'returnUrl'             => null,
            'returnBody'            => null,
            'asyncOps'              => null,
            'endUser'               => null,
            'exclusive'             => null,
            'detectMime'            => null,
            'fsizeLimit'            => null,
            'saveKey'               => null,
            'persistentOps'         => null,
            'persistentPipeline'    => null,
            'persistentNotifyUrl'   => null,
            'fopTimeout'            => null,
            'mimeLimit'             => null,
        );

        $policy = array_merge($defaultPolicy, $policy);

        $policy = array_filter($policy, function($val) {
            return $val !== null;
        });

        var_dump($policy);

        $policy = json_encode($policy);

        return $this->signWithData($policy);
    }
}