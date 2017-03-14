<?php
class Redis_LOL_Summoner_AntiCheating_LogModel extends Redis_BaseModel
{
    const KEY_OFFSET = 100000;

    const TTL = 1209600;

    public static function key($timestamp = null)
    {
        return 'lol:anti.cheating:log:' . date('Ymd', $timestamp ?: time());
    }

    public function update($user)
    {
        $result = false;

        if ($user >= static::KEY_OFFSET && $user < static::MAX_OFFSET) {
            $key = static::key();

            $result = $this->db->sAdd($key, $user);

            $this->db->expire($key, static::TTL);
        }

        return $result;
    }

    public function count($timestamp = null)
    {
        $key = static::key($timestamp);

        return $this->db->sCard($key);
    }
}