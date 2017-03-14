<?php
class MySQL_Report_LOL_Champion_Monthly_BaseModel extends MySQL_Report_LOL_Champion_BaseModel
{
    public static function key($timestamp = null)
    {
        return date('Ym', $timestamp ?: time());
    }
}