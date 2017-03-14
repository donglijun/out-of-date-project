<?php
class Captcha
{
    public static $V  = array("a", "e", "i", "o", "u", "y");
    public static $VN = array("a", "e", "i", "o", "u", "y","2","3","4","5","6","7","8","9");
    public static $C  = array("b","c","d","f","g","h","j","k","m","n","p","q","r","s","t","u","v","w","x","z");
    public static $CN = array("b","c","d","f","g","h","j","k","m","n","p","q","r","s","t","u","v","w","x","z","2","3","4","5","6","7","8","9");

    protected $_wordlen = 8;
    protected $_timeout = 300;
    protected $_imageDir = "";
    protected $_imageUrl = "";
    protected $_imageAlt = '';
    protected $_suffix = '.png';
    protected $_width = 200;
    protected $_height = 50;
    protected $_fsize = 24;
    protected $_font = "";
    protected $_gcFreq = 10;
    protected $_expiration = 600;
    protected $_dotNoiseLevel = 100;
    protected $_lineNoiseLevel = 5;
    protected $_useNumbers = true;

    protected $word;
    protected $id;
    protected $timeout;

    protected $_identity = 'captcha';

    protected $_sessionKey = 'captcha';

    public function __construct($config)
    {
        if (!extension_loaded("gd")) {
            throw new Exception("Image CAPTCHA requires GD extension");
        }

        if (!function_exists("imagepng")) {
            throw new Exception("Image CAPTCHA requires PNG support");
        }

        if (!function_exists("imageftbbox")) {
            throw new Exception("Image CAPTCHA requires FT fonts support");
        }

        foreach ($config as $key => $val) {
            $_key = '_' . $key;

            if (isset($this->{$_key})) {
                $this->{$_key} = $val;
            }
        }
    }

    protected function generateWord()
    {
        $word       = '';
        $vowels     = $this->_useNumbers ? static::$VN : static::$V;
        $consonants = $this->_useNumbers ? static::$CN : static::$C;

        for ($i=0; $i < $this->_wordlen; $i = $i + 2) {
            // generate word with mix of vowels and consonants
            $consonant = $consonants[array_rand($consonants)];
            $vowel     = $vowels[array_rand($vowels)];
            $word     .= $consonant . $vowel;
        }

        if (strlen($word) > $this->_wordlen) {
            $word = substr($word, 0, $this->_wordlen);
        }

        return $word;
    }

    protected function randomFreq()
    {
        return mt_rand(700000, 1000000) / 15000000;
    }

    protected function randomPhase()
    {
        // random phase from 0 to pi
        return mt_rand(0, 3141592) / 1000000;
    }

    protected function randomSize()
    {
        return mt_rand(300, 700) / 100;
    }

    protected function generateImage($id, $word)
    {
        if (empty($this->_font)) {
            throw new Exception('Image CAPTCHA requires font');
        }

//        $img_file = $this->_imageDir . $id . $this->_suffix;
        $img = imagecreatetruecolor($this->_width, $this->_height);

        $text_color = imagecolorallocate($img, 0, 0, 0);
        $bg_color   = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $this->_width-1, $this->_height-1, $bg_color);
        $textbox = imageftbbox($this->_fsize, 0, $this->_font, $word);
        $x = ($this->_width - ($textbox[2] - $textbox[0])) / 2;
        $y = ($this->_height - ($textbox[7] - $textbox[1])) / 2;
        imagefttext($img, $this->_fsize, 0, $x, $y, $text_color, $this->_font, $word);

        // generate noise
        for ($i=0; $i < $this->_dotNoiseLevel; $i++) {
            imagefilledellipse($img, mt_rand(0, $this->_width), mt_rand(0, $this->_height), 2, 2, $text_color);
        }
        for ($i=0; $i < $this->_lineNoiseLevel; $i++) {
            imageline($img, mt_rand(0, $this->_width), mt_rand(0, $this->_height), mt_rand(0, $this->_width), mt_rand(0, $this->_height), $text_color);
        }

        // transformed image
        $img2     = imagecreatetruecolor($this->_width, $this->_height);
        $bg_color = imagecolorallocate($img2, 255, 255, 255);
        imagefilledrectangle($img2, 0, 0, $this->_width-1, $this->_height-1, $bg_color);

        // apply wave transforms
        $freq1 = $this->randomFreq();
        $freq2 = $this->randomFreq();
        $freq3 = $this->randomFreq();
        $freq4 = $this->randomFreq();

        $ph1 = $this->randomPhase();
        $ph2 = $this->randomPhase();
        $ph3 = $this->randomPhase();
        $ph4 = $this->randomPhase();

        $szx = $this->randomSize();
        $szy = $this->randomSize();

        for ($x = 0; $x < $this->_width; $x++) {
            for ($y = 0; $y < $this->_height; $y++) {
                $sx = $x + (sin($x*$freq1 + $ph1) + sin($y*$freq3 + $ph3)) * $szx;
                $sy = $y + (sin($x*$freq2 + $ph2) + sin($y*$freq4 + $ph4)) * $szy;

                if ($sx < 0 || $sy < 0 || $sx >= $this->_width - 1 || $sy >= $this->_height - 1) {
                    continue;
                } else {
                    $color    = (imagecolorat($img, $sx, $sy) >> 16)         & 0xFF;
                    $color_x  = (imagecolorat($img, $sx + 1, $sy) >> 16)     & 0xFF;
                    $color_y  = (imagecolorat($img, $sx, $sy + 1) >> 16)     & 0xFF;
                    $color_xy = (imagecolorat($img, $sx + 1, $sy + 1) >> 16) & 0xFF;
                }

                if ($color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255) {
                    // ignore background
                    continue;
                } elseif ($color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0) {
                    // transfer inside of the image as-is
                    $newcolor = 0;
                } else {
                    // do antialiasing for border items
                    $frac_x  = $sx-floor($sx);
                    $frac_y  = $sy-floor($sy);
                    $frac_x1 = 1-$frac_x;
                    $frac_y1 = 1-$frac_y;

                    $newcolor = $color    * $frac_x1 * $frac_y1
                        + $color_x  * $frac_x  * $frac_y1
                        + $color_y  * $frac_x1 * $frac_y
                        + $color_xy * $frac_x  * $frac_y;
                }

                imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newcolor, $newcolor, $newcolor));
            }
        }

        // generate noise
        for ($i=0; $i<$this->_dotNoiseLevel; $i++) {
            imagefilledellipse($img2, mt_rand(0, $this->_width), mt_rand(0, $this->_height), 2, 2, $text_color);
        }

        for ($i=0; $i<$this->_lineNoiseLevel; $i++) {
            imageline($img2, mt_rand(0, $this->_width), mt_rand(0, $this->_height), mt_rand(0, $this->_width), mt_rand(0, $this->_height), $text_color);
        }

//        imagepng($img2, $img_file);
        imagedestroy($img);
//        imagedestroy($img2);

        return $img2;
    }

    protected function gc()
    {
        $expire = time() - $this->_expiration;
        $imgdir = $this->_imageDir;
        if (!$imgdir || strlen($imgdir) < 2) {
            // safety guard
            return;
        }

        $suffixLength = strlen($this->_suffix);
        foreach (new DirectoryIterator($imgdir) as $file) {
            if (!$file->isDot() && !$file->isDir()) {
                if (file_exists($file->getPathname()) && $file->getMTime() < $expire) {
                    // only deletes files ending with $this->suffix
                    if (substr($file->getFilename(), -($suffixLength)) == $this->_suffix) {
                        unlink($file->getPathname());
                    }
                }
            }
        }
    }

    public function generate()
    {
        $this->word    = $this->generateWord();
        $this->id      = uniqid();
        $this->timeout = time() + $this->_timeout;
//
//        $_SESSION['login.captcha.word']     = $this->word;
//        $_SESSION['login.captcha.id']       = $this->id;
//        $_SESSION['login.captcha.timeout']  = $this->timeout;

//        if (mt_rand(1, $this->_gcFreq) == 1) {
//            $this->gc();
//        }

        return $this->generateImage($this->id, $this->word);
    }

    public function getWord()
    {
        return $this->word;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public static function cleanup()
    {
//        if (isset($_SESSION['login.captcha.word'])) {
//            unset($_SESSION['login.captcha.word']);
//        }
//
//        if (isset($_SESSION['login.captcha.id'])) {
//            unset($_SESSION['login.captcha.id']);
//        }
//
//        if (isset($_SESSION['login.captcha.timeout'])) {
//            unset($_SESSION['login.captcha.timeout']);
//        }
//
//        if (isset($_SESSION['first'])) {
//            unset($_SESSION['first']);
//        }
    }
}