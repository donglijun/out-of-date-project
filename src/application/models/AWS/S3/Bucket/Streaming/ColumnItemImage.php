<?php
class AWS_S3_Bucket_Streaming_ColumnItemImageModel
{
    const PREFIX = 'columns/';

    public static function getSmallName($id, $ext)
    {
        return sprintf('%s%d-small.%s', static::PREFIX, $id, $ext);
    }

    public static function getLargeName($id, $ext)
    {
        return sprintf('%s%d-large.%s', static::PREFIX, $id, $ext);
    }
}