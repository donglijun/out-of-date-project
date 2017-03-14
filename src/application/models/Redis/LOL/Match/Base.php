<?php
class Redis_LOL_Match_BaseModel extends Redis_BaseModel
{
    protected $platform = '';

    protected $expire = 60;

    protected $lifetime = 86400;

    public function matchKey($gameid, $platform = '')
    {
        return sprintf('lol:match:%s:%s', $platform ?: $this->platform, $gameid);
    }

    public function queueKey($platform = '')
    {
        return sprintf('lol:queue:match:%s', $platform ?: $this->platform);
    }

    public function update($gameid, $data, $platform = '')
    {
        $result = false;

        $matchKey = static::matchKey($gameid, $platform);
        $queueKey = static::queueKey($platform);

        // Cache match data
        if ($this->db->setnx($matchKey, $data)) {
            // Set lifetime
            $this->db->expire($matchKey, $this->lifetime);

            // Push queue
            $result = $this->db->sAdd($queueKey, $matchKey);
        }

        return $result;
    }

    public function remove($gameid, $platform = '')
    {
        $result = false;

        $matchKey = static::matchKey($gameid, $platform);
        $queueKey =  static::queueKey($platform);

        // Clear cached data
        $this->db->setex($matchKey, $this->expire, '');

        // Pop queue
        $this->db->sRem($queueKey, $matchKey);

        return $result;
    }

    public function exists($gameid, $platform = '')
    {
        $matchKey = static::matchKey($gameid, $platform);

        return $this->db->exists($matchKey);
    }

    public function getData($gameid, $platform = '')
    {
        $matchKey = static::matchKey($gameid, $platform);

        return $this->db->get($matchKey);
    }

    public function getQueue($platform = '')
    {
        $queueKey =  static::queueKey($platform);

        return $this->db->sMembers($queueKey);
    }
}