<?php
class Redis_Streaming_Channel_LogModel extends Redis_BaseModel
{
    const TTL = 5184000;

    public static function key($channel, $ip, $session)
    {
        return sprintf('live.log:%s_%s_%s', $channel, $ip, $session);
    }

    public function log($channel, $ip, $session, $content) {
        $key =  static::key($channel, $ip, $session);

        $this->db->hSet($key, time(), $content);

        if ($this->db->ttl($key) == -1) {
            $this->db->expire($key, static::TTL);
        }
    }

    public function get($channel, $ip, $session) {
        $key =  static::key($channel, $ip, $session);

        return $this->db->hGetAll($key);
    }

    public function clear()
    {
        return $this->db->del(static::KEY);
    }
}