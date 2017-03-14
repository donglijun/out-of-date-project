<?php
class FeedbackController extends ApiController
{
    protected $authActions = array();

    protected $mkjogoDb;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    protected function save()
    {
        $request = $this->getRequest();
        $action = $request->getActionName();
        $timestamp = $request->getServer('REQUEST_TIME');
        $mkuser = Yaf_Registry::get('mkuser');
        $userid = isset($mkuser['userid']) ? $mkuser['userid'] : 0;

        $data = array(
            'user'          => $userid,
            'lang'          => $request->get('lang', ''),
            'os'            => $request->get('os', ''),
            'description'   => $request->get('description', ''),
            'created_on'    => $timestamp,
            'contact_way'   => $request->get('contact_way', ''),
            'contact_info'  => $request->get('contact_info', ''),
            'client'        => $action,
            'ip'            => Misc::getClientIp(),
            'errors'        => $request->get('errors'),
        );

        /**
         * Process upload
         */
        if (isset($_FILES['log'])) {
            $logName = uniqid() . '.log.gz';
            $relativePath = Mkjogo_Feedback::getRelativePath($action);
            $absolutePath = Yaf_Registry::get('config')->feedback->{"log-path"} . $relativePath;

            /**
             * Make sure directory exists
             */
            Misc::mkdir($absolutePath);

            if (move_uploaded_file($_FILES['log']['tmp_name'], $absolutePath . $logName)) {
                $data['log_path'] = $relativePath . $logName;
            }
        }

        $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());
        return $feedbackModel->insert($data);
    }

    public function lolAction()
    {
        $result = array(
            'code'  => 500,
        );

        if ($this->getRequest()->isPost()) {
            $feedback = $this->save();

            if ($feedback !== false) {
                $result['code'] = 200;
                $result['id'] = (int) $feedback;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function hsAction()
    {
        $result = array(
            'code'  => 500,
        );

        if ($this->getRequest()->isPost()) {
            $feedback = $this->save();

            if ($feedback !== false) {
                $result['code'] = 200;
                $result['id'] = (int) $feedback;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function lolv2Action()
    {
        $result = array(
            'code'  => 500,
        );

        if ($this->getRequest()->isPost()) {
            $feedback = $this->save();

            if ($feedback !== false) {
                $result['code'] = 200;
                $result['id'] = (int) $feedback;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function nikksy_androidAction()
    {
        $result = array(
            'code'  => 500,
        );

        if ($this->getRequest()->isPost()) {
            $feedback = $this->save();

            if ($feedback !== false) {
                $result['code'] = 200;
                $result['id'] = (int) $feedback;
            }
        }

        echo json_encode($result);

        return false;
    }

    public function nikksy_iosAction()
    {
        $result = array(
            'code'  => 500,
        );

        if ($this->getRequest()->isPost()) {
            $feedback = $this->save();

            if ($feedback !== false) {
                $result['code'] = 200;
                $result['id'] = (int) $feedback;
            }
        }

        echo json_encode($result);

        return false;
    }
}