<?php
final class Mkjogo_Feedback
{
    public static function getRelativePath($client, $timestamp = null)
    {
        $timestamp = $timestamp ?: time();

        return sprintf('%s/%s/%s/', $client, date('Ym', $timestamp), date('d', $timestamp));
    }
}