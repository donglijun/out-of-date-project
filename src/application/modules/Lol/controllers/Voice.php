<?php
class VoiceController extends ApiController
{
    protected $authActions = array('temporary');

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

    public function temporaryAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $params = array(
                'app'       => 'LOL',
                'platform'  => strtoupper($request->get('platform', '')),
                'roomName'  => $request->get('roomName', ''),
                'team'      => $request->get('team', ''),
            );
            $unique = implode(':', $params);

            $this->getRedisVoice();

            $redisVoiceRoomTemporaryModel = new Redis_Voice_Room_TemporaryModel($this->redisVoice);
            if (!($data = $redisVoiceRoomTemporaryModel->get($unique))) {
                $data = array();

                $redisVoiceRoomGeneratorModel = new Redis_Voice_Room_GeneratorModel($this->redisVoice);
                $data['room'] = $redisVoiceRoomGeneratorModel->newID();

                $redisVoiceServerRoundRobinModel = new Redis_Voice_Server_RoundRobinModel($this->redisVoice);
                if (!($server = $redisVoiceServerRoundRobinModel->alloc())) {
                    $servers = array();

                    $voiceServerModel = new MySQL_Voice_ServerModel($this->getVoiceDb());
                    $rowset = $voiceServerModel->getActiveServers();

                    foreach ($rowset as $row) {
                        $server     = sprintf('%s:%s', $row['ip'], $row['port']);
                        $servers[]  = $server;
                    }

                    $redisVoiceServerRoundRobinModel->update($servers);
                }

                if (($server = explode(':', $server)) && (count($server) >= 2)) {
                    $data['ip']   = $server[0];
                    $data['port'] = $server[1];

                    if ($redisVoiceRoomTemporaryModel->set($unique, $data['room'], $data['ip'], $data['port'])) {
                        $data['ttl'] = $redisVoiceRoomTemporaryModel->ttl($unique);
                    } else {
                        $data = $redisVoiceRoomTemporaryModel->get($unique);
                    }
                }
            }
//            $result['data'] = $redisVoiceRoomTemporaryModel->alloc(implode(':', $params));

            $result['code'] = 200;
            $result['data'] = $data;
        }

        $this->callback($result);

        return false;
    }

    public function reconnecttemporaryAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $params = array(
                'app'       => 'LOL',
                'platform'  => strtoupper($request->get('platform', '')),
                'roomName'  => $request->get('roomName', ''),
                'team'      => $request->get('team', ''),
            );
            $unique = implode(':', $params);

            $this->getRedisVoice();

            $redisVoiceRoomTemporaryModel = new Redis_Voice_Room_TemporaryModel($this->redisVoice);

            if ($data = $redisVoiceRoomTemporaryModel->get($unique)) {
                $voiceServerModel = new MySQL_Voice_ServerModel($this->getVoiceDb());

                if (!$voiceServerModel->validate($data['ip'], $data['port'])) {
                    $redisVoiceServerRoundRobinModel = new Redis_Voice_Server_RoundRobinModel($this->redisVoice);
                    $redisVoiceServerRoundRobinModel->invalid(sprintf('%s:%s', $data['ip'], $data['port']));

                    if (($server = $redisVoiceServerRoundRobinModel->alloc()) && ($server = explode(':', $server)) && (count($server) >= 2)) {
                        if ($redisVoiceRoomTemporaryModel->update($unique, $server[0], $server[1])) {
                            $data['ip']   = $server[0];
                            $data['port'] = $server[1];
                        }
                    }
                }

                $result['data'] = $data;
            }

        }

        $this->callback($result);

        return false;
    }

//    public function serverAction()
//    {
//        $result = array(
//            'code'  => 500,
//        );
//        $request = $this->getRequest();
//
//        $redisVoiceServerRoundRobinModel = new Redis_Voice_Server_RoundRobinModel();
//
//        if (!($server = $redisVoiceServerRoundRobinModel->alloc())) {
//            $servers = array();
//
//            $voiceServerModel = new MySQL_Voice_ServerModel($this->getMkjogoDb());
//            $rowset = $voiceServerModel->getActiveServers();
//
//            foreach ($rowset as $row) {
//                $server     = sprintf('%s:%s', $row['ip'], $row['port']);
//                $servers[]  = $server;
//            }
//
//            $redisVoiceServerRoundRobinModel->update($servers);
//        }
//
//        if ($server && ($server = explode(':', $server)) && (count($server) >= 2)) {
//            $result['code'] = 200;
//            $result['data'] = array(
//                'ip'    => $server[0],
//                'port'  => $server[1],
//            );
//        }
//
//        $this->callback($result);
//
//        return false;
//    }
}