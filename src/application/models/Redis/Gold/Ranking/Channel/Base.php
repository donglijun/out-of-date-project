<?php
class Redis_Gold_Ranking_Channel_BaseModel extends Redis_BaseModel
{
    protected $pattern = '';

    protected $ttl = 86400;

    public function key($channel, $timestamp = null)
    {
        return sprintf('gold.channel.ranking:%s:%s', $channel, date($this->pattern, $timestamp ?: time()));
    }

    public function incr($channel, $user, $val = 1, $timestamp = null)
    {
        $result = false;

        $key = $this->key($channel, $timestamp);

        $result = $this->db->zIncrBy($key, $val, $user);

        $this->db->expire($key, $this->ttl);

        return $result;
    }

    public function top($channel, $number = 10, $timestamp = null)
    {
        $key = $this->key($channel, $timestamp);

        return $this->db->zRevRange($key, 0, $number - 1, true);
    }

    public function range($channel, $start, $end, $timestamp = null)
    {
        $key = $this->key($channel, $timestamp);

        return $this->db->zRevRange($key, $start, $end, true);
    }

    public function clear($channel, $timestamp = null)
    {
        $key = $this->key($channel, $timestamp);

        return $this->db->del($key);
    }
}