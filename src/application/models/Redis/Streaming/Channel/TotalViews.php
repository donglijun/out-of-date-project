<?php
class Redis_Streaming_Channel_TotalViewsModel extends Redis_BaseModel
{
    public static function key($channel)
    {
        return sprintf('streaming:channel:totalviews:%d', $channel);
    }

    public function incr($channel, $val = 1) {
        $key = static::key($channel);

        return $this->db->incrBy($key, $val);
    }

    public function get($channel) {
        $key = static::key($channel);

        return $this->db->get($key);
    }
}