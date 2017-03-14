<?php
class ReportController extends CliController
{
    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    public function gift_total_dailyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        $to     = mktime(0, 0, 0, $month, $day, $year);
        $from   = strtotime('-1 day', $to);
        $from2  = strtotime('-1 day', $from);

        $date   = date('Ymd', $from);
        $date2  = date('Ymd', $from2);

        $this->getStreamingDb();

        $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);

        $data = array(
            'date'       => $date,
            'collecting' => $giftUserLogModel->sumCollecting($from, $to),
            'giving'     => $giftChannelLogModel->sum($from, $to),
            'updated_on' => time(),
        );

//        $giftReportTotalDailyModel = new MySQL_Gift_Report_TotalDailyModel($this->streamingDb);
        $giftReportTotalDailyModel = new MySQL_Gift_Report_Total_DailyModel($this->streamingDb);

        $giftReportTotalDailyModel->replace($data);

        printf("Report gift total daily: %d\n", $date);

        return false;
    }

    public function gift_channel_dailyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);
        $day        = date('d', $timestamp);

        $to     = mktime(0, 0, 0, $month, $day, $year);
        $from   = strtotime('-1 day', $to);
        $from2  = strtotime('-1 day', $from);

        $date   = date('Ymd', $from);
        $date2  = date('Ymd', $from2);

        $this->getStreamingDb();

        $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);

        if ($rows = $giftChannelLogModel->sumByChannel($from, $to)) {
            foreach ($rows as $row) {
                $data = array(
                    'date'       => $date,
                    'channel'    => $row['channel'],
                    'receiving'  => $row['sum'],
                    'updated_on' => time(),
                );

//                $giftReportChannelDailyModel = new MySQL_Gift_Report_ChannelDailyModel($this->streamingDb);
                $giftReportChannelDailyModel = new MySQL_Gift_Report_Channel_DailyModel($this->streamingDb);

                $giftReportChannelDailyModel->replace($data);
            }
        }

        printf("Report gift channel daily: %d\n", $date);

        return false;
    }

    public function gift_channel_monthlyAction()
    {
        $data = $data2 = array();

        $timestamp  = $this->getRequest()->get('at', 0) ?: time();
        $year       = date('Y', $timestamp);
        $month      = date('m', $timestamp);

        $to     = mktime(0, 0, 0, $month, 1, $year);
        $from   = strtotime('-1 day', $to);
        $from2  = strtotime('-1 day', $from);

        $date   = date('Ym', $from);
        $date2  = date('Ym', $from2);

        $this->getStreamingDb();

        $giftUserLogModel = new MySQL_Gift_UserLogModel($this->streamingDb);
        $giftChannelLogModel = new MySQL_Gift_ChannelLogModel($this->streamingDb);

        if ($rows = $giftChannelLogModel->sumByChannel($from, $to)) {
            foreach ($rows as $row) {
                $data = array(
                    'date'       => $date,
                    'channel'    => $row['channel'],
                    'receiving'  => $row['sum'],
                    'updated_on' => time(),
                );

                $giftReportChannelMonthlyModel = new MySQL_Gift_Report_Channel_MonthlyModel($this->streamingDb);

                $giftReportChannelMonthlyModel->replace($data);
            }
        }

        printf("Report gift channel monthly: %d\n", $date);

        return false;
    }
}