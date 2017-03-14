<?php
class Redis_Streaming_Channel_Ranking_GameModel extends Redis_BaseModel
{
    const TTL = 2678400;

    public static function key($game, $timestamp = null)
    {
        return sprintf('streaming.channel.ranking.game:%s:%s', $game, date('Ymd', $timestamp ?: time()));
    }

    public function update($game, $channel, $number, $timestamp = null)
    {
        $key = static::key($game, $timestamp);

        $score = $this->db->zScore($key, $channel);

        if ($score < $number) {
            $this->db->zAdd($key, $number, $channel);
            $this->db->expire($key, static::TTL);
        }

        return true;
    }

    public function top($game, $number = 3, $timestamp = null)
    {
        $key = $this->key($game, $timestamp);

        return $this->db->zRevRange($key, 0, $number - 1, true);
    }

    public function range($game, $start, $end, $timestamp = null)
    {
        $key = $this->key($game, $timestamp);

        return $this->db->zRevRange($key, $start, $end, true);
    }

    public function clear($game, $timestamp = null)
    {
        $key = $this->key($game, $timestamp);

        return $this->db->del($key);
    }
}