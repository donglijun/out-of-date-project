<?php
class Redis_User_Session_DataModel extends Redis_BaseModel
{
//    const TTL = 2592000; // 30 days

    public static function key($user)
    {
        return sprintf('user:session:data:%d', $user);
    }

    public function get($user, $hashKey)
    {
        $key = static::key($user);

        return $this->db->hGet($key, $hashKey);
    }

    public function set($user, $hashKey, $value)
    {
        $key = static::key($user);

        return $this->db->hSet($key, $hashKey, $value);
    }

    public function mset($user, $data)
    {
        $key = static::key($user);

        return $this->db->hMset($key, $data);
    }

    public function mget($user, $hashKeys)
    {
        $key = static::key($user);

        return $this->db->hMGet($key, $hashKeys);
    }

    public function getall($user)
    {
        $key = static::key($user);

        return $this->db->hGetAll($key);
    }

    public function del($user, $hashKeys)
    {
        $key = static::key($user);

        array_unshift($hashKeys, $key);

        return call_user_func_array(array($this->db, 'hdel'), $hashKeys);
    }

    public function fexists($user, $hashKey)
    {
        $key = static::key($user);

        return $this->db->hExists($key, $hashKey);
    }

    public function incrby($user, $hashKey, $val = 1)
    {
        $key = static::key($user);

        return $this->db->hIncrBy($key, $hashKey, $val);
    }
}