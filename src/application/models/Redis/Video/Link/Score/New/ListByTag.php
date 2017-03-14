<?php
class Redis_Video_Link_Score_New_ListByTagModel extends Redis_BaseModel
{
    const KEY_PREFIX = 'video-link-score-new-list-by-tag:';

    public static function key($tag)
    {
        return static::KEY_PREFIX . $tag;
    }

    public function update($tag, $link, $score)
    {
        return $this->db->zAdd(static::key($tag), $score, $link);
    }

    public function len($tag)
    {
        return $this->db->zCard(static::key($tag));
    }

    public function range($tag, $start, $end)
    {
        return $this->db->zRevRange(static::key($tag), $start, $end);
    }

    public function random($tag, $limit = 100)
    {
        $pool = $this->range($tag, 0, $limit);
        $index = array_rand($pool);

        return ($index !== null) ? $pool[$index] : 0;
    }

    public function rem($tag, $links)
    {
        $param_arr = array(
            static::key($tag),
        );

        if (!is_array($links)) {
            $param_arr[] = $links;
        } else {
            $param_arr = array_merge($param_arr, $links);
        }

        call_user_func_array(array($this->db, 'zRem'), $param_arr);

        return count($links);
    }
}