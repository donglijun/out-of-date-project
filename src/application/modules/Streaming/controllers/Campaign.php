<?php
class CampaignController extends ApiController
{
    protected $authActions = array(
        'signup',
        'profile',
        'complain',
    );

    protected $streamingDb;

    protected $passportDb;

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

    protected function createChannel($channel, $data)
    {
        $timestamp = time();

        $this->getStreamingDb();
        $this->getPassportDb();

        $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
        if (!$streamingChannelModel->exists($channel)) {
            $userAccountModel = new MySQL_User_AccountModel($this->passportDb);

            $userInfo = $userAccountModel->getRow($channel, array('name'));

            $data = array(
                'id'            => $channel,
                'title'         => $data['title'],
                'owner_name'    => $userInfo['name'],
                'created_on'    => $timestamp,
            );

            if ($channel = $streamingChannelModel->insert($data)) {
                // Add owner as editor
                $data = array(
                    'channel'       => $channel,
                    'user'          => $channel,
                    'name'          => $userInfo['name'],
                    'created_on'    => $timestamp,
                );
                $streamingEditorModel = new MySQL_Streaming_EditorModel($this->streamingDb);
                $streamingEditorModel->insert($data);
            }
        }
    }

    public function signupAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();

            $streamingCampaignMemberModel = new MySQL_Streaming_CampaignMemberModel($this->streamingDb);

            if ($gameAccount = $request->get('game_account')) {
                if (!$streamingCampaignMemberModel->exists($userid)) {
                    $data = array(
                        'id'            => $userid,
                        'name'          => $currentUser['name'],
                        'game_account'  => $gameAccount,
                        'facebook'      => $request->get('facebook', ''),
                        'skype'         => $request->get('skype', ''),
                        'signed_on'     => $request->getServer('REQUEST_TIME'),
                    );

                    $streamingCampaignMemberModel->insert($data);

//                    $this->createChannel($userid, array(
//                        'title' => $gameAccount,
//                    ));
                }

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function profileAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();
            $streamingCampaignMemberModel = new MySQL_Streaming_CampaignMemberModel($this->streamingDb);

            if ($info = $streamingCampaignMemberModel->getRow($userid)) {
                $result['data'] = array(
                    'game_account'  => $info['game_account'],
                    'facebook'      => $info['facebook'],
                    'skype'         => $info['skype'],
                    'signed_on'     => $info['signed_on'],
                );

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function complainAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        if ($request->isPost()) {
            $this->getStreamingDb();
            $streamingCampaignComplainModel = new MySQL_Streaming_CampaignComplainModel($this->streamingDb);

            if ($reason = $request->get('reason')) {
                $data = array(
                    'user'          => $userid,
                    'reason'        => $reason,
                    'contact'       => $request->get('contact'),
                    'created_on'    => $request->getServer('REQUEST_TIME'),
                );

                $streamingCampaignComplainModel->insert($data);

                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}