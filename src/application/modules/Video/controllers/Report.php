<?php
class ReportController extends ApiController
{
    protected $authActions = array(
        'submit',
    );

    protected $videoDb;

    protected $mkjogoDb;

    protected $passportDb;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    public function submitAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

//        $mkuser = Yaf_Registry::get('mkuser');
//        $userid = $mkuser['userid'];
        $currentUser = Yaf_Registry::get('user');
        $userid = $currentUser['id'];

        $this->getVideoDb();
        $this->getMkjogoDb();
        $this->getPassportDb();

        $mkjogoReportedModel = new MySQL_Mkjogo_ReportedModel($this->mkjogoDb);
        $mkjogoUser = new Mkjogo_User($this->passportDb);

        $data = array(
            'module'        => $request->getModuleName(),
            'type'          => $request->get('type'),
            'target'        => $request->get('target'),
            'reporter'      => $userid,
            'reporter_name' => $mkjogoUser->getDetail($userid),
            'status'        => MySQL_Video_ReportModel::STATUS_NEW,
            'ip'            => Misc::getClientIp(),
            'created_on'    => $request->getServer('REQUEST_TIME'),
        );

        if ($reason = $request->get('reason')) {
            $data['reason'] = $reason;
        }

        if ($mkjogoReportedModel->validateType($data['type'])) {
            $targetClass = sprintf('MySQL_Video_%sModel', ucfirst($data['type']));
            $targetModel = new $targetClass($this->videoDb);

            if ($targetInfo = $targetModel->getRow($data['target'])) {
                $data['user'] = $targetInfo['author'];
                $data['user_name'] = $targetInfo['author_name'];

                if ($sensitiveFields = $targetModel->getSensitiveFields()) {
                    foreach ($sensitiveFields as $field) {
                        if (isset($targetInfo[$field])) {
                            $data['content'] = $targetInfo[$field];
                            break;
                        }
                    }
                }

                $mkjogoReportedModel->insert($data);

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