<?php
class Redis_OuModel extends Redis_BaseModel
{
    protected $keyOffset = 100000;
    protected $expire = 43200;

    public static function key($lang, $timestamp = null)
    {
        $timestamp = $timestamp ?: time();

        return sprintf('ou:%s:%s%02d', $lang, date('YmdH', $timestamp), floor(date('i', $timestamp) / 5));
    }

    public function update($user, $lang)
    {
        $result = false;

        if ($user >= $this->keyOffset && $user < self::MAX_OFFSET) {
            $key = static::key($lang);

            $result = $this->db->sAdd($key, $user);

            $this->db->expire($key, $this->expire);
        }

        return $result;
    }

    public function count($timestamp = null)
    {
        $result = array();

        $keysPattern = static::key('*', $timestamp);

        $keys = $this->db->keys($keysPattern);

        foreach ($keys as $key) {
            $result[$key] = $this->db->sCard($key);
        }

        return $result;
    }
}