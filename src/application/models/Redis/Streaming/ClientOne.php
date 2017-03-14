<?php
class Redis_Streaming_ClientOneModel extends Redis_BaseModel
{
    const KEY = 'streaming.client.one';

    public function get()
    {
        return $this->db->get(static::KEY);
    }

    public function set($val)
    {
        return $this->db->set(static::KEY, $val);
    }
}