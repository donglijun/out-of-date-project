<?php
class Redis_Open_Google_AccessTokenModel extends Redis_BaseModel
{
    const KEY = 'google.access_token';

    public function get()
    {
        return $this->db->get(static::KEY);
    }

    public function set($val, $ttl = 3000)
    {
        return $this->db->set(static::KEY, $val, $ttl);
    }
}