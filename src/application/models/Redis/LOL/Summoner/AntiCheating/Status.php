<?php
class Redis_LOL_Summoner_AntiCheating_StatusModel extends Redis_BaseModel
{
    const KEY = 'lol:anti.cheating:status';

    const AC_RATE_MIN = 5;

    const AC_RATE_MAX = 15;

    const KEY_OFFSET = 100000;

    public function update($user)
    {
        $result = false;

        $user = $user - static::KEY_OFFSET;

        if ($user >= 0 || $user < self::MAX_OFFSET) {
            $result = $this->db->setBit(static::KEY, $user, 1);
        }

        return $result;
    }

    public function count()
    {
        return $this->db->bitCount(static::KEY);
    }

    public function get($user)
    {
        $user = $user - static::KEY_OFFSET;

        return $this->db->getBit(static::KEY, $user);
    }

    public function randomUpdate($user)
    {
        $result = false;

        if (!$this->get($user)) {
            $rand = mt_rand(0, 100);
            $rate = mt_rand(static::AC_RATE_MIN, static::AC_RATE_MAX);

            if ($rand <= $rate) {
                $this->update($user);

                $result = true;
            }
        }

        return $result;
    }
}