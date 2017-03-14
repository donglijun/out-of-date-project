<?php
class Redis_Goods_Channel_TotalModel extends Redis_BaseModel
{
    public static function key($channel)
    {
        return sprintf('goods.channel.total:%s', $channel);
    }

    public function incrBy($channel, $goods, $amount = 1)
    {
        $key = static::key($channel);

        return $this->db->hIncrBy($key, $goods, $amount);
    }

    public function getAll($channel)
    {
        $key = static::key($channel);

        return $this->db->hGetAll($key);
    }
}