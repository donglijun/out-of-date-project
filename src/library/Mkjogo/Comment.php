<?php
final class Mkjogo_Comment
{
    const URL_PATTERN_COUNT = 'http://hs.mkjogo.com/?id=%d';
    const SALT = 'mkcomment';

    public static function getCount($decks)
    {
        $result = $data = array();

        if (!is_array($decks)) {
            $decks = array($decks);
        }
        $decks = array_flip($decks);

        $config = Yaf_Registry::get('config');
        foreach ($decks as $deck => $val) {
            $decks[$deck] = rawurlencode(sprintf(static::URL_PATTERN_COUNT, $deck));
        }

        $data['urls'] = implode(',', $decks);
        $data['sign'] = md5($data['urls'] . static::SALT);

        $response = Misc::curlPost($config->url->comment->count, $data);
        $response = json_decode($response, true);
        if (isset($response['result']) && is_array($response['result']) && ($data = $response['result'])) {
            foreach ($decks as $deck => $url) {
                $result[$deck] = (isset($data[$url]) && is_array($data[$url])) ? (int) $data[$url]['cc'] : 0;
            }
        }

        return $result;
    }
}