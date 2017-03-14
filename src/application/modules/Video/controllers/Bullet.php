<?php
class BulletController extends ApiController
{
    protected $authActions = array(
        'submit',
    );

    protected $videoDb;

    protected $passportDb;

    protected $redisBullet;

    protected function getVideoDb()
    {
        if (empty($this->videoDb)) {
            $this->videoDb = Daemon::getDb('video-db', 'video-db');
        }

        return $this->videoDb;
    }

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
    }

    protected function getRedisBullet()
    {
        if (empty($this->redisBullet)) {
            $this->redisBullet = Daemon::getRedis('redis-bullet', 'redis-bullet');
        }

        return $this->redisBullet;
    }

    public function submitAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        if ($request->isPost()) {
//            $mkuser = Yaf_Registry::get('mkuser');
//            $userid = $mkuser['userid'];
            $currentUser = Yaf_Registry::get('user');
            $userid = $currentUser['id'];

            $this->getVideoDb();
            $this->getPassportDb();

            $videoLinkModel = new MySQL_Video_LinkModel($this->videoDb);
            $videoBulletModel = new MySQL_Video_BulletModel($this->videoDb);
            $mkjogoUser = new Mkjogo_User($this->passportDb);

            if (($link = $request->get('cid')) && ($linkInfo = $videoLinkModel->getRow($link, array('id')))) {
                $message = array(
                    'text'      => $request->get('message', ''),
                    'stime'     => $request->get('stime', 0),
                    'mode'      => $request->get('mode', '1'),
                    'type'      => $request->get('type', 'normal'),
                    'msg'       => $request->get('msg', '1'),
                    'size'      => $request->get('size', 25),
                    'color'     => $request->get('color', 16777215),
                    'user'      => $mkjogoUser->getDetail($userid),
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
                    'link'          => $link,
                    'body'          => $message['text'],
                    'author'        => $userid,
                    'author_name'   => $message['user'],
                    'track'         => $message['stime'],
                    'style'         => json_encode($style),
                    'ip'            => Misc::getClientIp(),
                    'created_on'    => $message['date'],
                );

                $bulletId = $videoBulletModel->insert($data);

                $videoLinkModel->bullet($link);

                $redisVideoBulletChannelModel = new Redis_Video_Bullet_ChannelModel($this->getRedisBullet());
                $redisVideoBulletChannelModel->publish($link, json_encode($message));

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

        $this->getVideoDb();

        if ($link = intval($request->get('link'))) {
            $videoBulletModel = new MySQL_Video_BulletModel($this->videoDb);
            $data = $videoBulletModel->getRowsByLink($link);

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
        $this->getVideoDb();

        if ($link = intval($request->get('link'))) {
            $videoBulletModel = new MySQL_Video_BulletModel($this->videoDb);
            $data = $videoBulletModel->getRowsByLink($link);

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