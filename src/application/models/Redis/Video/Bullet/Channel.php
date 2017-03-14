<?php
class Redis_Video_Bullet_ChannelModel extends Redis_BaseModel
{
    public static function channel($link)
    {
        return 'link.' . $link;
    }

    public function publish($link, $message)
    {
        $key = static::channel($link);

        return $this->db->publish($key, $message);
    }
}