<?php
class Redis_Voice_Room_OnlineModel extends Redis_BaseModel
{
    // 30 days lifetime
    protected $lifetime = 2592000;

    public static function key($room)
    {
        return sprintf('voice:room:online:%d', $room);
    }

    public static function val($timestamp, $name)
    {
        return sprintf("%s\t%s", $timestamp, $name);
    }

    public static function parseVal($val)
    {
        return explode("\t", $val);
    }

    public function enter($room, $user, $name)
    {
        $result = false;

        $key = static::key($room);
        $val = static::val(time(), $name);

        $result = $this->db->zAdd($key, $user, $val);

//        if (($total = $this->db->zCard($key)) > $this->max) {
//            $this->db->zRemRangeByRank($key, 0, $total - $this->max - 1);
//        }

        return $result;
    }

    public function quit($room, $user)
    {
        $key = static::key($room);

        return $this->db->zRemRangeByScore($key, $user, $user);
    }

    public function getList()
    {
        $result = array();

        $key = static::key($user);

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

    public function getTotal($room)
    {
        $key = static::key($room);

        return $this->db->zCard($key);
    }
}