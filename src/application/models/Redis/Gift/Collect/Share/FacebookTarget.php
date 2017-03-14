<?php
class Redis_Gift_Collect_Share_FacebookTargetModel extends Redis_BaseModel
{
    const TTL = 86400;

    public static function key($user, $timestamp = null)
    {
        $date = date('Ymd', $timestamp ?: time());

        return sprintf('gift:collect:share:target:%d:%d', $user, $date);
    }

    public function add($user, $target, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        $result = $this->db->sAdd($key, $target);
        $this->db->expire($key, static::TTL);

        return $result;
    }

    public function isMember($user, $target, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->sIsMember($key, $target);
    }

}