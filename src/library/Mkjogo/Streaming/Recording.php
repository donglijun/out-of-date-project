<?php
class Mkjogo_Streaming_Recording
{
    public static function getAvailableResolutions($height)
    {
        $result = array();

        $config = Yaf_Registry::get('config')->toArray();

        $resolutions = $config['streaming']['recording']['resolutions'];
        foreach ($resolutions as $resolution) {
            if ($resolution['h'] < $height) {
                $result[] = $resolution;
            }
        }

        return $result;
    }
}