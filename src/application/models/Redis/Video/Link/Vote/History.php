<?php
class Redis_Video_Link_Vote_HistoryModel extends Redis_BaseModel
{
    const TTL = 604800;

    public static function key($link)
    {
        return sprintf('video:link:vote:history:%d', $link);
    }

    public function update($link, $user, $score)
    {
        $key = static::key($link);

        $result = $this->db->zAdd($key, $score, $user);

        $this->db->expire($key, static::TTL);

        return $result;
    }

    public function get($link, $user)
    {
        $key = static::key($link);

        return $this->db->zScore($key, $user);
    }

    public function totalUps($link)
    {
        $key = static::key($link);

        return $this->db->zCount($key, 1, 1);
    }

    public function totalDowns($link)
    {
        $key = static::key($link);

        return $this->db->zCount($key, -1, -1);
    }

    public function isAvailable($link)
    {
        $key = static::key($link);

        return $this->db->ttl($key) != -2;
    }

    public function del($links)
    {
        if (!is_array($links)) {
            $links = array($links);
        }

        foreach ($links as $link) {
            $key = static::key($link);

            $this->db->del($key);
        }

        return count($links);
    }
}