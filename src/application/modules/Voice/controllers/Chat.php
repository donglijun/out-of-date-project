<?php
use Aws\S3\S3Client;

class ChatController extends ApiController
{
    protected $authActions = array(
        'uploadimage',
    );

    protected $voiceDb;

    protected $s3;

    protected function getVoiceDb()
    {
        if (empty($this->voiceDb)) {
            $this->voiceDb = Daemon::getDb('voice-db', 'voice-db');
        }

        return $this->voiceDb;
    }

    protected function getS3()
    {
        if (empty($this->s3)) {
            $config = Yaf_Registry::get('config')->toArray();

            $this->s3 = S3Client::factory(array(
                'key'       => $config['aws']['s3']['key'],
                'secret'    => $config['aws']['s3']['secret'],
                'region'    => $config['aws']['s3']['region'],
            ));
        }

        return $this->s3;
    }

    public function uploadimageAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

//        $voiceRoomModel = new MySQL_Voice_RoomModel($this->voiceDb);

        if ($room = $request->get('room', 0)) {
            $room = (int) $room;
            $mkuser = Yaf_Registry::get('mkuser');
            $userid = $mkuser['userid'];

            try {
                $s3 = $this->getS3();

                foreach ($_FILES as $key => $val) {
                    if (exif_imagetype($val['tmp_name']) !== false) {
                        $fext = strtolower(pathinfo($val['name'], PATHINFO_EXTENSION));
                        $fname = sprintf('room-%d/%s.%s', $room, md5($userid . microtime(true)), $fext);

                        $return = $s3->putObject(array(
                            'Bucket' => AWS_S3_Bucket_VoiceChatImageModel::BUCKET,
                            'Key' => $fname,
                            'SourceFile' => $val['tmp_name'],
                            'ContentType' => $val['type'],
                            'Metadata' => array(
                                'original-name' => $val['name'],
                            ),
                            'ACL' => 'public-read',
                        ));

                        $s3->waitUntilObjectExists(array(
                            'Bucket' => AWS_S3_Bucket_VoiceChatImageModel::BUCKET,
                            'Key' => $fname,
                        ));

                        $result['data'][$key] = $s3->getObjectUrl(AWS_S3_Bucket_VoiceChatImageModel::BUCKET, $fname, null, array(
                            'Scheme' => 'http',
                        ));
                    }
                }

                $result['code'] = 200;
            } catch (Exception $e) {
//                $result['code'] = 500;
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}