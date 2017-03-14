<?php
class FeedbackController extends AdminController
{
    protected $authActions = array(
        'summary'   => MySQL_AdminAccountModel::GROUP_ADMIN,
        'list'      => MySQL_AdminAccountModel::GROUP_ADMIN,
        'translate' => MySQL_AdminAccountModel::GROUP_ADMIN,
        'geterrors' => MySQL_AdminAccountModel::GROUP_ADMIN,
        'download'  => MySQL_AdminAccountModel::GROUP_ADMIN,
        'viewlog'   => MySQL_AdminAccountModel::GROUP_ADMIN,
        'today'     => MySQL_AdminAccountModel::GROUP_ADMIN,
    );

    protected $mkjogoDb;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    public function summaryAction()
    {
        $data = $filter = $messages = $times = array();
        $request = $this->getRequest();

        if ($from = $request->get('from', '')) {
            $filter['from'] = $from;
        }
        if ($to = $request->get('to', '')) {
            $filter['to'] = $to;
        }
        if ($lang = $request->get('lang', '')) {
            $filter['lang'] = $lang;
        }
        if ($client = $request->get('client', '')) {
            $filter['client'] = $client;
        }

        $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());

        if ($from || $to || $lang) {
            $dateFrom = strtotime($from);
            $dateTo   = strtotime($to);

            $data = $feedbackModel->groupByMessage($dateFrom, $dateTo, $lang, $client);

            $urlPrefix = '/admin/feedback/list?' . http_build_query($filter);
            foreach ($data as $key => $val) {
                $data[$key]['url'] = $urlPrefix . '&message=' . rawurlencode($val['message']);
                $data[$key]['y'] = $val['times'];

                $messages[] = $val['message'];
            }
        }

        $this->_view->assign(array(
            'langs'         => $feedbackModel->getLangMap(),
            'clients'       => $feedbackModel->getClientMap(),
            'filter'        => $filter,
            'messagesCount' => count($data),
            'messages'      => json_encode($messages),
            'series'        => json_encode($data, JSON_NUMERIC_CHECK),
        ));
    }

    public function listAction()
    {
        $data = $filter = $messages = array();
        $request = $this->getRequest();

        if ($from = $request->get('from', '')) {
            $filter['from'] = $from;
        }
        if ($to = $request->get('to', '')) {
            $filter['to'] = $to;
        }
        if ($lang = $request->get('lang', '')) {
            $filter['lang'] = $lang;
        }
        if ($client = $request->get('client', '')) {
            $filter['client'] = $client;
        }
        if ($message = $request->get('message', '')) {
            $filter['message'] = $message;
        }

        $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());

        if ($from || $to || $lang || $message) {
            $dateFrom = strtotime($from);
            $dateTo   = strtotime($to);

            $data = $feedbackModel->query($dateFrom, $dateTo, $lang, $client, $message);

            $messages = $feedbackModel->groupByMessage($dateFrom, $dateTo, $lang, $client);
        }

        $this->_view->assign(array(
            'langs'     => $feedbackModel->getLangMap(),
            'clients'   => $feedbackModel->getClientMap(),
            'messages'  => $messages,
            'filter'    => $filter,
            'data'      => $data,
        ));
    }

    public function downloadAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id', 0)) {
            $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());

            $data = $feedbackModel->getRow($id, array('log_path'));

            Misc::httpOutputFile(array(
                'fileName'  => pathinfo($data['log_path'], PATHINFO_BASENAME),
                'file'      => Yaf_Registry::get('config')->feedback->{"log-path"} . $data['log_path'],
            ));
        }

        return false;
    }

    public function translateAction()
    {
        $result = array(
            'code'  => 500,
        );
        $request = $this->getRequest();

        if (($id = $request->get('id', 0)) && ($translation = $request->get('translation', ''))) {
            $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());
            $feedbackModel->translate($id, $translation, $this->session->admin['user_id']);

            $result['data'] = array(
                'translated'    => 'Translated by ' . $this->session->admin['user_id'] . ' @ ' . date('Y-m-d H:i'),
            );
            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function geterrorsAction()
    {
        $result = array(
            'code'  => 404,
        );
        $request = $this->getRequest();

        if ($id = $request->get('id', 0)) {
            $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());

            $data = $feedbackModel->getRow($id, array('errors'));

            $result['data'] = $data['errors'];
            $result['code'] = 200;
        }

        echo json_encode($result);

        return false;
    }

    public function viewlogAction()
    {
        $request = $this->getRequest();

        if ($id = $request->get('id', 0)) {
            $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());

            $data = $feedbackModel->getRow($id, array_merge($feedbackModel->getFields(), array('errors')));
            $data['logContent'] = implode("\n", gzfile(Yaf_Registry::get('config')->feedback->{"log-path"} . $data['log_path']));

            $this->_view->assign(array(
                'data'    => $data,
            ));
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }

    public function todayAction()
    {
        $result = array(
            'code'  => 404,
        );

        $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());

        $result['data'] = array(
            'today_total'   => $feedbackModel->getTodayTotal(),
            'untranslated'  => $feedbackModel->getTodayUntranslatedByLang(),
            'from'          => date('Y-m-d'),
            'to'            => date('Y-m-d', strtotime('+1 day')),
        );
        $result['code'] = 200;

        echo json_encode($result);

        return false;
    }
}