<?php
class FeedbackController extends CliController
{
    protected $feedbackClients = array('lol');

    protected $mkjogoDb;

    protected function getMkjogoDb()
    {
        if (empty($this->mkjogoDb)) {
            $this->mkjogoDb = Daemon::getDb('mkjogo-db', 'mkjogo-db');
        }

        return $this->mkjogoDb;
    }

    public function clearAction()
    {
        $month = $this->getRequest()->get('month', 3);
        $month = $month > 0 ? $month * -1 : $month;

        $current = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $from    = strtotime(sprintf('%d month', $month), $current);
        $to      = strtotime('+1 month', $from);

        $feedbackModel = new MySQL_FeedbackModel($this->getMkjogoDb());
        $feedbackModel->clear($from, $to);

        printf("Clear feedback data monthly: %s\n", date('Y-m-d', $from));

        foreach ($this->feedbackClients as $client) {
            $relativePath = Mkjogo_Feedback::getRelativePath($client, $from);
            $absolutePath = dirname(Yaf_Registry::get('config')->feedback->{"log-path"} . $relativePath);

            Misc::rmdir($absolutePath);

            printf("Clear feedback log monthly: %s\n", $absolutePath);
        }

        return false;
    }
}