<?php
use Aws\S3\S3Client;

class ProfileController extends ApiController
{
    protected $authActions = array(
        'get',
        'update',
        'upload_avatar',
    );

    protected $passportDb;

    protected $s3;

    protected function getPassportDb()
    {
        if (empty($this->passportDb)) {
            $this->passportDb = Daemon::getDb('passport-db', 'passport-db');
        }

        return $this->passportDb;
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

    public function getAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');

        $userProfileModel = new MySQL_User_ProfileModel($this->getPassportDb());

        $result['data'] = $userProfileModel->getRow($currentUser['id'], array(
            'user',
            'email',
            'avatar',
        ));
        $result['code'] = 200;

        $this->callback($result);

        return false;
    }

    public function updateAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $currentUser = Yaf_Registry::get('user');

        if ($request->isPost()) {
            $data = array();

            if ($email = $request->get('email')) {
                $data['email'] = strtolower($email);
            }

            if ($nickname = $request->get('nickname')) {
                $data['nickname'] = $nickname;
            }

            if ($data) {
                $userProfileModel = new MySQL_User_ProfileModel($this->getPassportDb());

                $userProfileModel->update($currentUser['id'], $data);
            }

            $result['code'] = 200;
        } else {
            $result['code'] = 403;
        }

        $this->callback($result);

        return false;
    }

    public function upload_avatarAction()
    {
        $request = $this->getRequest();

        $result = array(
            'code'  => 500,
        );

        $config = Yaf_Registry::get('config')->toArray();

        $x = (int) $request->get('x', 0);
        $y = (int) $request->get('y', 0);
        $w = (int) $request->get('w', 0);
        $h = (int) $request->get('h', 0);

        if ($request->isPost() && $w && $h) {
            $userdata = Yaf_Registry::get('user');
            $userid = $userdata['id'];

            if ($_FILES && isset($_FILES['avatar_file']) && $_FILES['avatar_file']['tmp_name']) {
                $src = $dst = null;
                $dstW = $config['passport']['avatar']['width'];
                $dstH = $config['passport']['avatar']['height'];
                $finfo = $_FILES['avatar_file'];

//                $fext = strtolower(pathinfo($finfo['name'], PATHINFO_EXTENSION));
                $fname = sprintf('avatars/%s-%dx%d.png', $userid, $dstW, $dstH);
                $ftype = exif_imagetype($finfo['tmp_name']);

                if ($ftype == IMAGETYPE_PNG) {
                    $src = imagecreatefrompng($finfo['tmp_name']);
                } else if ($ftype == IMAGETYPE_JPEG) {
                    $src = imagecreatefromjpeg($finfo['tmp_name']);
                } else if ($ftype == IMAGETYPE_GIF) {
                    $src = imagecreatefromgif($finfo['tmp_name']);
                }

                if ($src) {
//                    $rect = array(
//                        'x'      => $x,
//                        'y'      => $y,
//                        'width'  => $w,
//                        'height' => $h,
//                    );
//                    $tmp = imagecrop($src, $rect);
//                    $dst = imagescale($tmp, 128, 128);

                    $dst = imagecreatetruecolor($dstW, $dstH);
                    imagecopyresampled($dst, $src, 0, 0, $x, $y, $dstW, $dstH, $w, $h);

                    imagepng($dst, $finfo['tmp_name']);

                    imagedestroy($src);
//                    imagedestroy($tmp);
                    imagedestroy($dst);

                    $s3 = $this->getS3();

                    $return = $this->s3->putObject(array(
                        'Bucket'        => $config['aws']['s3']['bucket']['streaming'],
                        'Key'           => $fname,
                        'SourceFile'    => $finfo['tmp_name'],
                        'ContentType'   => 'image/png',
                        'ACL'           => 'public-read',
                    ));

                    $this->getPassportDb();

                    $userProfileModel = new MySQL_User_ProfileModel($this->passportDb);

                    $userProfileModel->update($userid, array(
                        'avatar' => $fname,
                    ));

                    $result['code'] = 200;
                    $result['data'] = $fname;
                } else {
                    $result['error'][] = array(
                        'message' => 'Invalid image type',
                    );
                }
            } else {
                $result['error'][] = array(
                    'message' => 'No file uploaded',
                );
            }
        } else {
            $result['code'] = 404;
        }

        $this->callback($result);

        return false;
    }
}