<?php
class Redis_Streaming_Chat_GagModel extends Redis_BaseModel
{
    const TTL_MIN = 600;

    const TTL_MAX = 86400;

    public static function key($channel)
    {
        return 'channel:gag.' . $channel;
    }

    public function add($channel, $user, $ttl = 600)
    {
        $key = static::key($channel);

        $this->clean($channel);

        $ttl = ($ttl > static::TTL_MAX) ? static::TTL_MAX : (($ttl < static::TTL_MIN) ? static::TTL_MIN : $ttl);

        $this->db->zAdd($key, time() + $ttl, $user);

        return $ttl;
    }

    public function remove($channel, $user)
    {
        $key = static::key($channel);

        return $this->db->zRem($key, $user);
    }

    public function check($channel, $user)
    {
        $key = static::key($channel);

        return $this->db->zScore($key, $user) >= time();
    }

    public function members($channel)
    {
        $key = static::key($channel);
        $now = time();

        return $this->db->zRangeByScore($key, $now, $now + static::TTL_MAX, array(
            'withscores' => true,
        ));
    }

    public function clean($channel)
    {
        $key = static::key($channel);

        return $this->db->zRemRangeByScore($key, 0, time());
    }
}