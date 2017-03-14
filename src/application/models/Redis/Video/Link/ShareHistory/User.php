<?php
class Redis_Video_Link_ShareHistory_UserModel extends Redis_BaseModel
{
    public static function key($user)
    {
        return sprintf('video:link:share.history:user:%d', $user);
    }

    public function update($user, $link, $score)
    {
        $key = static::key($user);

        $result = $this->db->zAdd($key, $score, $link);

        return $result;
    }

    public function len($user)
    {
        $key = static::key($user);

        return $this->db->zCard($key);
    }

    public function links($user, $start = 0, $stop = -1)
    {
        $key = static::key($user);

        return $this->db->zRange($key, $start, $stop);
    }

    public function revlinks($user, $start = 0, $stop = -1)
    {
        $key = static::key($user);

        return $this->db->zRevRange($key, $start, $stop);
    }

    public function linksByScore($user, $min, $max)
    {
        $key = static::key($user);

        return $this->db->zRevRangeByScore($key, $max, $min);
    }

    public function rem($user, $links)
    {
        $param_arr = array(
            static::key($user),
        );

        if (!is_array($links)) {
            $param_arr[] = $links;
        } else {
            $param_arr = array_merge($param_arr, $links);
        }

        call_user_func_array(array($this->db, 'zRem'), $param_arr);

        return count($links);
    }

    public function del($users)
    {
        if (!is_array($users)) {
            $users = array($users);
        }

        foreach ($users as $user) {
            $key = static::key($user);

            $this->db->del($key);
        }

        return count($users);
    }
}