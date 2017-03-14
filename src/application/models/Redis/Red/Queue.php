<?php
class Redis_Red_QueueModel extends Redis_BaseModel
{
    const KEY = 'red.queue';

    const TTL = 86400; // 24 hours

    public function add($id, $timestamp = null)
    {
        $timestamp = $timestamp ?: time();

        return $this->db->zAdd(static::KEY, $timestamp, $id);
    }

    public function retire()
    {
        return $this->db->zRangeByScore(static::KEY, 0, time() - static::TTL);
    }

    public function rem($val)
    {
        return $this->db->zRem(static::KEY, $val);
    }
}