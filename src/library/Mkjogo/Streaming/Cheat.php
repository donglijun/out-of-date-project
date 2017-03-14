<?php
final class Mkjogo_Streaming_Cheat
{
    public static function watchingNow($num)
    {
        $config = Yaf_Registry::get('config')->toArray();
        $ratio = $config['streaming']['cheat']['watching-now-ratio'];

        return round($num * $ratio);
    }
}