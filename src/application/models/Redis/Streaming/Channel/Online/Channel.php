<?php
class Redis_Streaming_Channel_Online_ChannelModel extends Redis_BaseModel
{
    const KEY = 'streaming.channel.online.channel';

    public function open($channel)
    {
        return $this->db->zAdd(static::KEY, time(), $channel);
    }

    public function close($channel)
    {
        return $this->db->zRem(static::KEY, $channel);
    }

    public function isLive($channel)
    {
        return is_int($this->db->zRank(static::KEY, $channel));
    }

    public function getList()
    {
        return $this->db->zRange(static::KEY, 0, -1);
    }

    public function getRevList()
    {
        return $this->db->zRevRange(static::KEY, 0, -1);
    }

    public function clear()
    {
        return $this->db->del(static::KEY);
    }
}