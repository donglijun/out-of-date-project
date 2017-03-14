<?php
class Redis_Streaming_Server_WeightingModel extends Redis_BaseModel
{
    const KEY = 'streaming.server.weighting';

    public function update($data)
    {
        array_unshift($data, static::KEY);

        $this->db->del(static::KEY);

        return call_user_func_array(array($this->db, 'lpush'), $data);
    }

    public function retrieve()
    {
        return $this->db->rpoplpush(static::KEY, static::KEY);
    }
}