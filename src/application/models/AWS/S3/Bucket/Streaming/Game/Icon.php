<?php
class AWS_S3_Bucket_Streaming_Game_IconModel
{
    const PREFIX = 'games/icon_';

    public static function getName($id, $ext)
    {
        return sprintf('%s%d.%s', static::PREFIX, $id, $ext);
    }
}