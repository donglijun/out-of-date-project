<?php
final class Qiniu_Utils
{
    public static function encode($data)
    {
        $replace_pairs = array(
            '+' => '-',
            '/' => '_',
        );

        return strtr(base64_encode($data), $replace_pairs);
    }

    public static function decode($data)
    {
        $replace_pairs = array(
            '-' => '+',
            '_' => '/',
        );

        return base64_decode(strtr($data, $replace_pairs));
    }
}