<?php
class Redis_Streaming_Channel_OnlineModel extends Redis_BaseModel
{
    // 7 days lifetime
    protected $lifetime = 604800;

    public static function key($channel)
    {
        return sprintf('streaming:channel:online:%d', $channel);
    }

    public static function val($timestamp, $name)
    {
        return sprintf("%s\t%s", $timestamp, $name);
    }

    public static function parseVal($val)
    {
        return explode("\t", $val);
    }

    public function enter($channel, $user, $name)
    {
        $result = false;

        $key = static::key($channel);
        $val = static::val(time(), $name);

        $result = $this->db->zAdd($key, $user, $val);

        return $result;
    }

    public function quit($channel, $user)
    {
        $key = static::key($channel);

        return $this->db->zRemRangeByScore($key, $user, $user);
    }

    public function getList($channel)
    {
        $result = array();

        $key = static::key($channel);

        $vals = $this->db->zRevRange($key, 0, -1, true);

        foreach ($vals as $val => $score) {
            list($timestamp, $name, ) = static::parseVal($val);

            $result[] = array(
                'user'      => (int) $score,
                'name'      => $name,
                'timestamp' => $timestamp,
            );
        }

        return $result;
    }

    public function getTotal($channel)
    {
        $key = static::key($channel);

        return $this->db->zCard($key);
    }
}