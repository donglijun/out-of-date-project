<?php
class VoiceController extends ServiceController
{
    protected $authActions = array();

    protected $mkjogoDb;

    protected $voiceDb;

    protected $redisVoice;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getVoiceDb()
    {
        if (empty($this->voiceDb)) {
            $this->voiceDb = Daemon::getDb('voice-db', 'voice-db');
        }

        return $this->voiceDb;
    }

    protected function getRedisVoice()
    {
        if (empty($this->redisVoice)) {
            $this->redisVoice = Daemon::getRedis('redis-voice', 'redis-voice');
        }

        return $this->redisVoice;
    }

    public function serverapiAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        $timestamp = time();

        if ($parameters = $_REQUEST['1']) {
            Misc::log($parameters, Zend_Log::WARN);
            $parameters = json_decode($parameters, true);
        }

        if ($callback = $_REQUEST['0']) {
            Misc::log($callback, Zend_Log::WARN);
            switch ($callback) {
                case 'MediaServerAPI.SetServerInfo':
                    // Update server status
                    $data = array(
                        'name'  => $parameters['ServiceID'],
                        'ip'    => $parameters['ip'],
                        'port'  => $parameters['port'],
                        'description'   => $parameters['desc'],
                        'created_on'    => $timestamp,
                        'updated_on'    => $timestamp,
                    );
                    $voiceServerModel = new MySQL_Voice_ServerModel($this->getVoiceDb());
                    $voiceServerModel->heartbeat($data);

                    $result['code'] = 200;
                    break;
                case 'MediaServerAPI.RecordSignInfo':
                    if (isset($parameters['UserInfo']['RoomID'])) {
                        $room = (int) $parameters['UserInfo']['RoomID'];
                    }
                    if (isset($parameters['UserInfo']['UserMKID'])) {
                        $userid = (int) $parameters['UserInfo']['UserMKID'];
                    }
                    if (isset($parameters['UserInfo']['UserShowName'])) {
                        $username = $parameters['UserInfo']['UserShowName'];
                    }
                    if (isset($parameters['EventType'])) {
                        $event = (int) $parameters['EventType']; // 0 for logout; 1 for login
                    }


                    if (isset($event) && isset($room) && isset($userid) && isset($username)) {
                        $redisVoiceRoomOnlineModel = new Redis_Voice_Room_OnlineModel($this->getRedisVoice());

                        if ($event == 1) {
                            $redisVoiceRoomOnlineModel->enter($room, $userid, $username);
                        } else {
                            $redisVoiceRoomOnlineModel->quit($room, $userid);
                        }
                    }

                    break;
                default:
                    break;
            }
        }

        echo json_encode($result);

        return false;
    }
}