<?php
class Redis_Streaming_Channel_Online_ClientByServerModel extends Redis_BaseModel
{
    const TTL = 43200;

    public static function key($channel)
    {
        return sprintf('streaming.channel.online.by.server:%s', $channel);
    }

    public function incr($channel, $identifier, $val = 1) {
        $key = static::key($channel);

        return $this->db->hIncrBy($key, $identifier, $val);
    }

    public function decr($channel, $identifier, $val = 1) {
        $key = static::key($channel);

        return $this->db->hIncrBy($key, $identifier, $val * -1);
    }

    public function reset($channel, $identifier)
    {
        $key = static::key($channel);

        return $this->db->hDel($key, $identifier);
    }

    public function set($channel, $identifier, $val)
    {
        $key = static::key($channel);

        $this->db->expire($key, static::TTL);

        return $this->db->hSet($key, $identifier, $val);
    }

    public function get($channel, $identifier) {
        $key = static::key($channel);

        return $this->db->hGet($key, $identifier);
    }

    public function getChannel($channel) {
        $key = static::key($channel);

        return array_sum($this->db->hVals($key) ?: array());
    }

    public function getAll($channel)
    {
        $key = static::key($channel);

        return $this->db->hGetAll($key);
    }

    public function clear($channel)
    {
        $key = static::key($channel);

        return $this->db->del($key);
    }

    public function enumKeys()
    {
        $key = static::key('*');

        return $this->db->keys($key);
    }
}