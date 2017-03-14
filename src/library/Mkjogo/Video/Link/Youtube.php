<?php
class Mkjogo_Video_Link_Youtube
{
    const PATTERN_URL = '|^https://www\.youtube\.com/watch\?v=(.+)$|i';

    const FORMAT_IMAGE_URL = 'https://i1.ytimg.com/vi/%s/mqdefault.jpg';

    public static function getFromVideoUrl($url)
    {
        $result = '';

        $matches = array();

        if (preg_match(self::PATTERN_URL, $url, $matches) !== false) {
            $result = sprintf(static::FORMAT_IMAGE_URL, $matches[1]);
        }

        return $result;
    }
}