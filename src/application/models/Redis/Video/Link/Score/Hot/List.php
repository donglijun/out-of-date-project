<?php
class Redis_Video_Link_Score_Hot_ListModel extends Redis_BaseModel
{
    const KEY = 'video-link-score-hot-list';

    public function update($link, $score)
    {
        return $this->db->zAdd(static::KEY, $score, $link);
    }

    public function len()
    {
        return $this->db->zCard(static::KEY);
    }

    public function range($start, $end)
    {
        return $this->db->zRevRange(static::KEY, $start, $end);
    }

    public function random($limit = 100)
    {
        $pool = $this->range(0, $limit);
        $index = array_rand($pool);

        return ($index !== null) ? $pool[$index] : 0;
    }

    public function rem($links)
    {
        $param_arr = array(
            static::KEY,
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