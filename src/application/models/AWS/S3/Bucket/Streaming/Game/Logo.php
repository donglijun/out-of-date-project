<?php
class AWS_S3_Bucket_Streaming_Game_LogoModel
{
    const PREFIX = 'games/logo_';

    public static function getName($id, $ext)
    {
        return sprintf('%s%d.%s', static::PREFIX, $id, $ext);
    }
}