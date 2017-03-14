<?php
class Mkjogo_Video_Link_Twitch
{
    const PATTERN_URL_C = '|^http://www\.twitch\.tv/([^/]+)/c/(\d+)|i';

    const PATTERN_URL_B = '|^http://www\.twitch\.tv/([^/]+)/b/(\d+)|i';

    const PATTERN_URL_OGIMAGE = '|^http://static-cdn\.jtvnw\.net/jtv\.thumbs/archive-(\d+)-(.+).jpg|';

    const PATTERN_CDATA_ARCHIVE_ID = '|PP\.archive_id(\s*)=(\s*)\"(\d+)\"|i';

    const FORMAT_THUMB_URL = 'http://static-cdn.jtvnw.net/jtv.thumbs/archive-%d-320x240.jpg';

    public function __construct()
    {
        Yaf_Loader::import('simple_html_dom.php');
    }

    public function getFromVideoUrl($url)
    {
        $result = '';

        $matches = array();

        if (preg_match(self::PATTERN_URL_C, $url, $matches)) {
//            $numC = $matches[2];

            if ($html = file_get_html($url)) {
                // Try to locate in meta
                foreach ($html->find('meta') as $meta) {
                    if (($meta->property == 'og:image') && (preg_match(self::PATTERN_URL_OGIMAGE, $meta->content, $matches))) {
                        $numB = $matches[1];

                        $result = sprintf(self::FORMAT_THUMB_URL, $numB);

                        break;
                    }
                }

                // Try again to locate in cdata
                if (!$result) {
                    foreach ($html->find('script') as $script) {
                        if (preg_match(self::PATTERN_CDATA_ARCHIVE_ID, $script->xmltext, $matches)) {
                            $numB = $matches[3];

                            $result = sprintf(self::FORMAT_THUMB_URL, $numB);

                            break;
                        }
                    }
                }
            }
        } else if (preg_match(self::PATTERN_URL_B, $url, $matches)) {
            $numB = $matches[2];

            $result = sprintf(self::FORMAT_THUMB_URL, $numB);
        }

        return $result;
    }
}