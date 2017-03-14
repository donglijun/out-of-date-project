<?php
use Aws\S3\S3Client;

class ProxyController extends FrontController
{
    protected $authActions = array();

    protected $streamingDb;

    protected $passportDb;

    protected $redisStreaming;

    protected $redisChat;

    protected $s3;

    public function init()
    {
        parent::init();

        Yaf_Registry::get('layout')->disableLayout();
    }

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getRedisStreaming()
    {
        if (empty($this->redisStreaming)) {
            $this->redisStreaming = Daemon::getRedis('redis-streaming', 'redis-streaming');
        }

        return $this->redisStreaming;
    }

    protected function getRedisChat()
    {
        if (empty($this->redisChat)) {
            $this->redisChat = Daemon::getRedis('redis-chat', 'redis-chat');
        }

        return $this->redisChat;
    }

    protected function getS3()
    {
        if (empty($this->s3)) {
            $config = Yaf_Registry::get('config')->toArray();

            $this->s3 = S3Client::factory(array(
                'key' => $config['aws']['s3']['key'],
                'secret' => $config['aws']['s3']['secret'],
                'region' => $config['aws']['s3']['region'],
            ));
        }

        return $this->s3;
    }

    public function liveAction()
    {
        $request = $this->getRequest();

        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $channel = $request->get('channel');
//        $subject = $request->get('subject');

        $this->getStreamingDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);

        if (!preg_match('|^\d+$|', $channel)) {
            $channel = $streamingChannelModel->aliasToID($channel);
        }

//        if ($alias = $request->get('alias')) {
//            $channel = $streamingChannelModel->aliasToID($alias);
//        } else {
//            $channel = $request->get('channel');
//        }

        if ($channel && ($row = $streamingChannelModel->getRow($channel))) {
            $this->getPassportDb();
            $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);
            $profile = $userProfileModel->getRow($channel, array('avatar'));

            $this->_view->assign('channelInfo', array(
                'channel'       => $row['id'],
                'title'         => $row['title'],
                'owner_name'    => $row['owner_name'],
                'alias'         => $row['alias'],
                'playing_game'  => $row['playing_game'],
                'avatar'        => $profile['avatar'],
            ));

            if ($row['special']) {
                $this->_view->display("proxy/live-{$row['special']}.phtml", array());

                return false;
            }
        } else {
            $this->goto404();
        }
    }

    public function facebook_adAction()
    {
        $request = $this->getRequest();
        $url = '/';

        $this->getRedisStreaming();

        $redisStreamingChannelOnlineChannelModel = new Redis_Streaming_Channel_Online_ChannelModel($this->redisStreaming);
        $lives = $redisStreamingChannelOnlineChannelModel->getList();
        $redisStreamingChannelOnlineClientByChannelModel = new Redis_Streaming_Channel_Online_ClientByChannelModel($this->redisStreaming);
        if ($lives = $redisStreamingChannelOnlineClientByChannelModel->mget($lives)) {
            arsort($lives, SORT_NUMERIC);

            $lives = array_slice($lives, 0, 5, true);
            $one = array_rand($lives, 1);

            $this->getStreamingDb();
            $streamingChannel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            $info = $streamingChannel->getRow($one, array('alias'));

            $url = '/' . ($info['alias'] ?: $one);
        }

        $this->redirect($url);

        return false;
    }
}