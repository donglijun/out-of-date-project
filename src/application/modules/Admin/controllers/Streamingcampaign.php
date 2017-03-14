<?php
class StreamingcampaignController extends AdminController
{
    protected $authActions = array(
        'query' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
        'award' => MySQL_AdminAccountModel::GROUP_ASSISTANT_ADMIN,
    );

    protected $streamingDb;

    protected function getStreamingDb()
    {
        if (empty($this->streamingDb)) {
            $this->streamingDb = Daemon::getDb('streaming-db', 'streaming-db');
        }

        return $this->streamingDb;
    }

    public function queryAction()
    {
        $request = $this->getRequest();
        $data = $ids = $channels = $onlines = $memos = $awardedChannels = array();
        $mark = '';
        $today = date('Y-m-d');

        $this->getStreamingDb();

        $times = $request->get('times', 5);
        $from = $request->get('from', $today);
        $to = $request->get('to', $today);

        if ($times && $from && $to) {
            $dateFrom = strtotime($from);
            $dateTo   = strtotime($to);
            $dateTo   = strtotime('+1 day', $dateTo);

            $mark = sprintf('%s-%s-%s', $from, $to, $times);

//            $streamingLiveLengthLogModel = new MySQL_Streaming_LiveLengthLogModel($this->streamingDb);
//            $campaignResult = $streamingLiveLengthLogModel->campaign($times, $dateFrom, $dateTo);

            $streamingBroadcastModel = new MySQL_Streaming_BroadcastModel($this->streamingDb);
            $campaignResult = $streamingBroadcastModel->campaign($times, $dateFrom, $dateTo);

            $ids = array_keys($campaignResult);

            $streamingCampaignMemberModel = new MySQL_Streaming_CampaignMemberModel($this->streamingDb);
            $channels = $streamingCampaignMemberModel->getRows($ids);

            $streamingChannelModel = new MySQL_Streaming_ChannelModel($this->streamingDb);
            if ($rows = $streamingChannelModel->getRows($ids, array('id', 'is_online', 'memo'))) {
                foreach ($rows as $row) {
                    $onlines[$row['id']] = $row['is_online'];
                    $memos[$row['id']] = $row['memo'];
                }
            }

//            $streamingTimeCardLogModel = new MySQL_Streaming_TimeCardLogModel($this->streamingDb);
//            $awardedChannels = $streamingTimeCardLogModel->getUsersByMark($mark);

            foreach ($channels as $key => $val) {
                if (isset($campaignResult[$val['id']])) {
                    $val['times'] = $campaignResult[$val['id']]['times'];
                    $val['lengths'] = $campaignResult[$val['id']]['lengths'];
                    $val['awarded'] = in_array($val['id'], $awardedChannels);

                    $val['memo'] = $memos[$val['id']];
                    $val['is_online'] = $onlines[$val['id']];

                    $data[] = $val;
                }
            }
        }
//
//        $data = array(
//            array(
//                'id'    => 11,
//                'name'  => 'haha',
//                'game_account'  => 'toto',
//                'facebook'  => 'f1',
//                'skype'     => 's1',
//                'times' => 9,
//                'awarded'   => false,
//            ),
//            array(
//                'id'    => 12,
//                'name'  => 'hoho',
//                'game_account' => 'zero',
//                'facebook'  => 'f2',
//                'skype'     => 's2',
//                'times' => 6,
//                'awarded'   => true,
//            ),
//        );
//
//        $mark = 'xxx';

        $this->getView()->assign(array(
            'times' => $times,
            'from'  => $from,
            'to'    => $to,
            'data'  => $data,
            'mark'  => $mark,
        ));
    }

//    public function awardAction()
//    {
//        $request = $this->getRequest();
//        $data = array();
//
//        $this->getStreamingDb();
//
//        $channel = $request->get('channel');
//        $mark = $request->get('mark');
//
//        //@todo valid mark
//
//        if ($channel && $mark) {
//            $streamingTimeCardModel = new MySQL_Streaming_TimeCardModel($this->streamingDb);
//            $streamingTimeCardLogModel = new MySQL_Streaming_TimeCardLogModel($this->streamingDb);
//            $timestamp = $request->getServer('REQUEST_TIME');
//
//            if (!$streamingTimeCardLogModel->check($mark, $channel)) {
//                if ($number = $streamingTimeCardModel->consume($timestamp)) {
//                    $streamingTimeCardLogModel->insert(array(
//                        'number'        => $number,
//                        'user'          => $channel,
//                        'mark'          => $mark,
//                        'operated_on'   => $timestamp,
//                        'operated_by'   => $this->session->admin['user'],
//                    ));
//
//                    $result['code'] = 200;
//                } else {
//                    $result['code'] = 409;
//                }
//            } else {
//                $result['code'] = 408;
//            }
//        } else {
//            $result['code'] = 404;
//        }
//
//
//        if ($request->isXmlHttpRequest()) {
//            header('Content-Type: application/json; charset=utf-8');
//
//            echo json_encode($result);
//
//            return false;
//        }
//
//        $this->redirect($_SERVER['HTTP_REFERER'] ?: '/admin/streamingcampaign/query');
//
//        return false;
//    }
}