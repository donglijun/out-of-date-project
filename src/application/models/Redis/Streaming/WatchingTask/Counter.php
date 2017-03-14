<?php
class Redis_Streaming_WatchingTask_CounterModel extends Redis_BaseModel
{
    const TTL = 86400;

    public static function key($user, $timestamp = null)
    {
        return sprintf('watching.counter:%s:%s', $user, date('d', $timestamp ?: time()));
    }

    public function add($user, $token, $timer, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        $this->db->zAdd($key, $timer + time(), $token);

        $this->db->expire($key, static::TTL);
    }

    public function check($user, $token, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->zScore($key, $token) <= time();
    }

    public function invalidate($user, $token, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->zRem($key, $token);
    }
}