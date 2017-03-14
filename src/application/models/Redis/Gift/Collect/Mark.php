<?php
class Redis_Gift_Collect_MarkModel extends Redis_BaseModel
{
    const TTL = 86400; // 24 hours
    const MAX = 3;

    public static function key($identity, $timestamp = null)
    {
        return sprintf('collect.mark:%s:%s', date('Ymd', $timestamp ?: time()), strtolower($identity));
    }

    public function mark($identity, $count = 1, $timestamp = null)
    {
        $key = static::key($identity, $timestamp);

        $this->db->incrBy($key, $count);

        return $this->db->expire($key, static::TTL);
    }

    public function valid($identity, $timestamp = null)
    {
        $key = static::key($identity, $timestamp);

        $val = $this->db->get($key);

        return (int) $val < static::MAX;
    }

    public function remove($identity, $timestamp = null)
    {
        $key = static::key($identity, $timestamp);

        return $this->db->del($key);
    }
}