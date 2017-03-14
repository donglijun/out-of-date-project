<?php
class Redis_Streaming_BadWordModel extends Redis_BaseModel
{
    const KEY = 'streaming.badword';

    public function get()
    {
        return $this->db->get(static::KEY);
    }

    public function set($val)
    {
        return $this->db->set(static::KEY, $val);
    }
}