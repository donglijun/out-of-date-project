<?php
class Redis_Streaming_Panel_ChannelModel extends Redis_BaseModel
{
    public static function key($channel)
    {
        return 'panel.' . $channel;
    }

    public function set($channel, $data)
    {
        $key = static::key($channel);

        return $this->db->set($key, $data);
    }

    public function get($channel)
    {
        $key = static::key($channel);

        return $this->db->get($key);
    }
}