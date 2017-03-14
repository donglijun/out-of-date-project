<?php
class Redis_Streaming_Channel_Online_ClientModel extends Redis_BaseModel
{
    const TTL = 86400;

    public static function key($channel)
    {
        return sprintf('streaming.channel.online.client:%s', $channel);
    }

    public static function val($ip, $session)
    {
        return sprintf("%s\t%s", $ip, $session);
    }

    public function join($channel, $ip, $session)
    {
        $key = static::key($channel);
        $val = static::val($ip, $session);

        return $this->db->zAdd($key, time(), $val);
    }

    public function leave($channel, $ip, $session)
    {
        $key = static::key($channel);
        $val = static::val($ip, $session);

        return $this->db->zRem($key, $val);
    }

    public function total($channel)
    {
        $key = static::key($channel);

        if (rand(0, 99) == 99) {
            $this->clean($channel);
        }

        return $this->db->zCard($key);
    }

    public function clean($channel)
    {
        $key = static::key($channel);

        return $this->db->zRemRangeByScore($key, 0, time() - static::TTL);
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