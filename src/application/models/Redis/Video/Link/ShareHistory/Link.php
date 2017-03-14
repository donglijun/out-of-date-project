<?php
class Redis_Video_Link_ShareHistory_LinkModel extends Redis_BaseModel
{
    public static function key($link)
    {
        return sprintf('video:link:share.history:link:%d', $link);
    }

    public function update($link, $user, $score)
    {
        $key = static::key($link);

        $result = $this->db->zAdd($key, $score, $user);

        return $result;
    }

    public function len($link)
    {
        $key = static::key($link);

        return $this->db->zCard($key);
    }

    public function users($link)
    {
        $key = static::key($link);

        return $this->db->zRange($key, 0, -1);
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