<?php
class Redis_DauModel extends Redis_BaseModel
{
    protected $keyOffset = 100000;

    public static function key($timestamp = null)
    {
        return 'dau:' . date('Ymd', $timestamp ?: time());
    }

    public function update($user)
    {
        $result = false;

        $user = $user - $this->keyOffset;

        if ($user >= 0 && $user < self::MAX_OFFSET) {
            $result = $this->db->setBit(static::key(), $user, 1);
        }

        return $result;
    }

    public function count($timestamp = null)
    {
        $key = static::key($timestamp);
        $score = (int) array_pop(explode(':', $key));
        /**
         * Save history
         */
        $this->db->zAdd('dau:history', $score, $key);

        return $this->db->bitCount($key);
    }

    public function clear($from, $to)
    {
        $result = 0;

        $scoreFrom  = date('Ymd', $from);
        $scoreTo    = date('Ymd', $to);
        $keys = $this->db->zRangeByScore('dau:history', (int) $scoreFrom, (int) $scoreTo);

        /**
         * Remove dau keys
         */
        foreach ($keys as $key) {
            $this->db->del($key);
        }

        /**
         * Update history
         */
        $result = $this->db->zRemRangeByScore('dau:history', (int) $scoreFrom, (int) $scoreTo);

        return $result;
    }
}