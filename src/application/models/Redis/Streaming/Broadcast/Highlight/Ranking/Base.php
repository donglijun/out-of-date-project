<?php
class Redis_Streaming_Broadcast_Highlight_Ranking_BaseModel extends Redis_BaseModel
{
    protected $pattern = '';

    protected $ttl = 86400;

    public function key($timestamp = null)
    {
        return sprintf('highlight.ranking:%s', date($this->pattern, $timestamp ?: time()));
    }

    public function incr($highlight, $val = 1, $timestamp = null)
    {
        $result = false;

        $key = $this->key($timestamp);

        $result = $this->db->zIncrBy($key, $val, $highlight);

        $this->db->expire($key, $this->ttl);

        return $result;
    }

    public function top($number = 10, $timestamp = null)
    {
        $key = $this->key($timestamp);

        return $this->db->zRevRange($key, 0, $number - 1, true);
    }

    public function range($start, $end, $timestamp = null)
    {
        $key = $this->key($timestamp);

        return $this->db->zRevRange($key, $start, $end, true);
    }

    public function clear($timestamp = null)
    {
        $key = $this->key($timestamp);

        return $this->db->del($key);
    }

    public function len($timestamp = null)
    {
        $key = $this->key($timestamp);

        return $this->db->zCard($key);
    }
}