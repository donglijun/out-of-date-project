<?php
class Redis_User_Login_MarkModel extends Redis_BaseModel
{
    const TTL = 600; // 10 minutes

    public static function key($identity)
    {
        return sprintf('login.mark:%s', strtolower($identity));
    }

    public function setMark($identity)
    {
        $key = static::key($identity);

        return $this->db->setex($key, static::TTL, 1);
    }

    public function hasMark($identity)
    {
        $key = static::key($identity);

        return $this->db->exists($key);
    }

    public function removeMark($identity)
    {
        $key = static::key($identity);

        return $this->db->del($key);
    }
}