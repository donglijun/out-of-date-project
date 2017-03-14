<?php
class Redis_Streaming_Channel_PausedModel extends Redis_BaseModel
{
    public static function key($channel)
    {
        return sprintf('streaming:channel:paused:%s', $channel);
    }

    public static function val($ip, $instanceID)
    {
        return sprintf("%s\t%s", $ip, $instanceID);
    }

    public function add($channel, $ip, $session)
    {
        $key = static::key($channel);
        $val = static::val($ip, $session);

        return $this->db->sAdd($key, $val);
    }

    public function remove($channel, $ip, $session)
    {
        $key = static::key($channel);
        $val = static::val($ip, $session);

        return $this->db->sRem($key, $val);
    }

    public function count($channel)
    {
        $key = static::key($channel);

        return $this->db->sCard($key);
    }

    public function enumKeys()
    {
        $key = static::key('*');

        return $this->db->keys($key);
    }
}