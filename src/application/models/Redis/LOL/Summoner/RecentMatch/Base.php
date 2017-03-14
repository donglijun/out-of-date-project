<?php
class Redis_LOL_Summoner_RecentMatch_BaseModel extends Redis_BaseModel
{
    protected $platform = '';

    protected $maxLength = 100;

    // 30 days lifetime
    protected $lifetime = 2592000;

    public function key($summoner)
    {
        return sprintf('lol:summoner:recentmatch:%s:%s', $this->platform, $summoner);
    }

    public function update($summoner, $gameid)
    {
        $key = $this->key($summoner);

        $len = $this->db->lPush($key, $gameid);

        if ($len > $this->maxLength) {
            $this->db->lTrim($key, 0, $this->maxLength - 1);
        }

        $this->db->expire($key, $this->lifetime);

        return true;
    }

    public function range($summoner, $start, $stop)
    {
        $key = $this->key($summoner);

        return $this->db->lGetRange($key, $start, $stop);
    }

    public function len($summoner)
    {
        $key = $this->key($summoner);

        return $this->db->lLen($key);
    }
}