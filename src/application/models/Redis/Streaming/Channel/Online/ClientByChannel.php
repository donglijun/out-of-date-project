<?php
class Redis_Streaming_Channel_Online_ClientByChannelModel extends Redis_BaseModel
{
    const KEY = 'streaming.channel.online.by.channel';

    public function incr($channel, $val = 1)
    {
        return $this->db->hIncrBy(static::KEY, $channel, $val);
    }

    public function decr($channel, $val = 1)
    {
        return $this->db->hIncrBy(static::KEY, $channel, $val * -1);
    }

    public function reset($channel)
    {
        return $this->db->hDel(static::KEY, $channel);
    }

    public function set($channel, $val)
    {
        return $this->db->hSet(static::KEY, $channel, $val);
    }

    public function get($channel)
    {
        return $this->db->hGet(static::KEY, $channel);
    }

    public function mget($channels)
    {
        return $this->db->hMGet(static::KEY, $channels);
    }

    public function getAll()
    {
        return $this->db->hGetAll(static::KEY);
    }

    public function clear()
    {
        return $this->db->del(static::KEY);
    }
}