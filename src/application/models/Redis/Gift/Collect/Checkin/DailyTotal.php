<?php
class Redis_Gift_Collect_Checkin_DailyTotalModel extends Redis_BaseModel
{
    const TTL = 86400;

    const MAX = 4; //100;

    public static function key($user, $timestamp = null)
    {
        $date = date('Ymd', $timestamp ?: time());

        return sprintf('gift:collect:checkin:total:%d:%d', $user, $date);
    }

    public function get($user, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->get($key);
    }

    public function add($user, $count, $timestamp = null)
    {
        $result = false;
        $key = static::key($user, $timestamp);

        $current = $this->db->get($key);

        if ($current + $count <= static::MAX) {
            $result = $this->db->incrBy($key, $count);
            $this->db->expire($key, static::TTL);
        }

        return $result;
    }

    public function isFull($user, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->get($key) >= static::MAX;
    }
}