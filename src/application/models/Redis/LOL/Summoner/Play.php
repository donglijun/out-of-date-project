<?php
class Redis_LOL_Summoner_PlayModel extends Redis_BaseModel
{
    protected $max = 10;

    public static function key($mkuser)
    {
        return sprintf('lol:summoner:play:%d', $mkuser);
    }

    public static function val($platform, $summonerID, $summonerName)
    {
        return sprintf("%s\t%s\t%s", $platform, $summonerID, $summonerName);
    }

    public static function parseVal($val)
    {
        return explode("\t", $val);
    }

    public function update($mkuser, $platform, $summonerID, $summonerName)
    {
        $result = false;

        $key = static::key($mkuser);
        $val = static::val($platform, $summonerID, $summonerName);

        $result = $this->db->zAdd($key, time(), $val);

        if (($total = $this->db->zCard($key)) > $this->max) {
            $this->db->zRemRangeByRank($key, 0, $total - $this->max - 1);
        }

        return $result;
    }

    public function getHistory($mkuser)
    {
        $result = array();

        $key = static::key($mkuser);

        $vals = $this->db->zRevRange($key, 0, -1, true);

        foreach ($vals as $val => $score) {
            list($platform, $summonerID, $summonerName, ) = static::parseVal($val);

            $result[] = array(
                'platform'      => $platform,
                'summonerID'    => $summonerID,
                'summonerName'  => $summonerName,
                'timestamp'     => (int) $score,
            );
        }

        return $result;
    }
}