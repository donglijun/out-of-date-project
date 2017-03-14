<?php
class Redis_Voice_Server_RoundRobinModel extends Redis_BaseModel
{
    const TTL = 300;

    // Use standalone database
//    protected $dbindex = 1;

    public static function key()
    {
        return 'mk-voice-server-round-robin';
    }

    public function update($servers)
    {
        $key = static::key();

        array_unshift($servers, $key);

        $result = call_user_func_array(array($this->db, 'lpush'), $servers);

        $this->db->expire($key, static::TTL);

        return $result;
    }

    public function alloc()
    {
        $key = static::key();

        return $this->db->rpoplpush($key, $key);
    }

    public function invalid($server)
    {
        $key = static::key();

        return $this->db->lRem($key, $server, 0);
    }
}