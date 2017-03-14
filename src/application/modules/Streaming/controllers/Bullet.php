<?php
class BulletController extends ApiController
{
    protected $authActions = array(
        'submit',
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

    public function submitAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
            $currentUser = Yaf_Registry::get('user');
            $userid = $currentUser['id'];

            $this->getStreamingDb();
            $this->getPassportDb();

            $streamingBroadcastHighlightModel = new MySQL_Streaming_BroadcastHighlightModel($this->streamingDb);
            $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);

            if (($highlight = $request->get('cid')) && ($highlightInfo = $streamingBroadcastHighlightModel->getRow($highlight, array('id')))) {
                $message = array(
                    'text'      => $request->get('message', ''),
                    'stime'     => $request->get('stime', 0),
                    'mode'      => $request->get('mode', '1'),
                    'type'      => $request->get('type', 'normal'),
                    'msg'       => $request->get('msg', '1'),
                    'size'      => $request->get('size', 25),
                    'color'     => $request->get('color', 16777215),
                    'user'      => $currentUser['name'],
                    'userid'    => $userid,
                    'date'      => $request->getServer('REQUEST_TIME'),
                );

                $style = array(
                    'version'   => 1,
                );

                if (isset($message['mode'])) {
                    $style['mode'] = $message['mode'];
                }
                if (isset($message['type'])) {
                    $style['type'] = $message['type'];
                }
                if (isset($message['msg'])) {
                    $style['msg'] = $message['msg'];
                }
                if (isset($message['size'])) {
                    $style['font-size'] = $message['size'];
                }
                if (isset($message['color'])) {
                    $style['font-color'] = $message['color'];
                }

                $data = array(
                    'highlight'     => $highlight,
                    'body'          => $message['text'],
                    'author'        => $userid,
                    'author_name'   => $message['user'],
                    'track'         => $message['stime'],
                    'style'         => json_encode($style),
                    'ip'            => Misc::getClientIp(),
                    'created_on'    => $message['date'],
                );

                $bulletId = $streamingBulletModel->insert($data);

                $streamingBroadcastHighlightModel->bullet($highlight);

                $result['data'] = array(
                    'bullet'        => $bulletId,
                    'created_on'    => $data['created_on'],
                );
                $result['code'] = 200;
            } else {
                $result['code'] = 404;
            }
        }

        $this->callback($result);

        return false;
    }

    public function listAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $this->getStreamingDb();

        if ($highlight = intval($request->get('highlight'))) {
            $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);
            $data = $streamingBulletModel->getRowsByHighlight($highlight);

            foreach ($data as $key => $val) {
                $data[$key]['style'] = json_decode($val['style'], true);
            }

            $result['data'] = $data;
            $result['code'] = 200;
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }

    public function toxmlAction()
    {
        $request = $this->getRequest();
        $this->getStreamingDb();

        if ($highlight = intval($request->get('highlight'))) {
            $streamingBulletModel = new MySQL_Streaming_BulletModel($this->streamingDb);
            $data = $streamingBulletModel->getRowsByHighlight($highlight);

            header('Content-Type: text/xml; charset=UTF-8');

            $xml = new XMLWriter();
            $xml->openMemory();
//            $xml->setIndent(true);
//            $xml->setIndentString('    ');
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('i');

            foreach ($data as $val) {
                $style = json_decode($val['style'], true);
                $attributes = array(
                    'track'         => $val['track'],
                    'mode'          => isset($style['mode']) ? $style['mode'] : 1,
                    'font-size'     => isset($style['font-size']) ? $style['font-size'] : 25,
                    'font-color'    => isset($style['font-color']) ? $style['font-color'] : 16777215,
                    'timestamp'     => $val['created_on'],
                    'dmchi'         => 0,
                    'username'      => $val['author'],
                    'bulletid'      => $val['id'],
                );

                $xml->startElement('d');
                $xml->writeAttribute('p', implode(',', $attributes));
                $xml->text($val['body']);
                $xml->endElement();
            }

            // end i
            $xml->endElement();
            $xml->endDocument();

            echo $xml->outputMemory(true);
        }

        return false;
    }
}