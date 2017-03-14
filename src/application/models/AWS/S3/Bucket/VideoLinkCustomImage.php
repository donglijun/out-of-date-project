<?php
class AWS_S3_Bucket_VideoLinkCustomImageModel
{
    const PREFIX = 'vlci/';

    public static function getName($id, $ext)
    {
        return sprintf('%s%d.%s', static::PREFIX, $id, $ext);
    }
}