<?php
class Redis_Streaming_Channel_Online_GameModel extends Redis_BaseModel
{
    const KEY = 'streaming.channel.online.game';

    public function open($channel, $game)
    {
        return $this->db->zAdd(static::KEY, $game, $channel);
    }

    public function close($channel)
    {
        return $this->db->zRem(static::KEY, $channel);
    }

    public function getList()
    {
        return $this->db->zRange(static::KEY, 0, -1);
    }

    public function getListByGame($game)
    {
        return $this->db->zRangeByScore(static::KEY, $game, $game);
    }

    public function clear()
    {
        return $this->db->del(static::KEY);
    }
}