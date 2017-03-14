<?php
class Redis_Streaming_Channel_WatchingNowModel extends Redis_BaseModel
{
    const KEY = 'streaming.channel.watchingnow';

    public function incr($channel, $val = 1) {
        return $this->db->hIncrBy(static::KEY, $channel, $val);
    }

    public function decr($channel, $val = -1) {
        return $this->db->hIncrBy(static::KEY, $channel, $val);
    }

    public function get($channel) {
        return $this->db->hGet(static::KEY, $channel);
    }

    public function clear()
    {
        return $this->db->del(static::KEY);
    }
}