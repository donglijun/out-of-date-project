<?php
class Redis_User_Session_SetModel extends Redis_BaseModel
{
    const WEB    = 1;
    const PC     = 2;
    const MOBILE = 4;

    const MAX    = 10;

    const TTL    = 2592000; // 30 days

    public static function key($user)
    {
        return sprintf('user:session:set:%d', $user);
    }

    public function update($user, $sessionId, $type)
    {
        $key = static::key($user);
        $score = sprintf('%d%02d', time(), (int) $type);

        $result = $this->db->zAdd($key, $score, $sessionId);

        if (($total = $this->db->zCard($key)) > static::MAX) {
            $this->db->zRemRangeByRank($key, 0, $total - static::MAX - 1);
        }

        $this->db->expire($key, static::TTL);

        return $result;
    }

    public function mexists($user, $sessionId)
    {
        $key = static::key($user);

        return is_int($this->db->zRank($key, $sessionId));
    }

    public function rem($user, $sessionId)
    {
        $key = static::key($user);

        return $this->db->zRem($key, $sessionId);
    }

    public function all()
    {
        $key = static::key($user);

        return $this->db->zRange($key, 0, -1);
    }

    public function clear($user)
    {
        $key = static::key($user);

        return $this->db->zRemRangeByRank($key, 0, -1);
    }
}