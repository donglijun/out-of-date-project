<?php
class Redis_Streaming_Chat_ChannelModel extends Redis_BaseModel
{
    const CHAT_MESSAGE_TYPE_PUBLIC = 'public';

    const CHAT_MESSAGE_TYPE_PRIVATE = 'private';

    const CHAT_MESSAGE_TYPE_SYSTEM = 'system';

    const CHAT_MESSAGE_TYPE_GIFT = 'gift';

    const CHAT_MESSAGE_TYPE_RED = 'red';

    const CHAT_MESSAGE_TYPE_GET_RED = 'get-red';

    const CHAT_MESSAGE_TYPE_GOODS = 'goods';

    const OP_MESSAGE_TYPE_BAN = 'ban';

    const OP_MESSAGE_TYPE_GRANT = 'grant';

    const OP_MESSAGE_TYPE_UPDATE_STATUS = 'update-status';

    const OP_MESSAGE_TYPE_GAG = 'gag';

    public static function key($channel)
    {
        return 'channel.' . $channel;
    }

    public function publish($channel, $data, $type = self::CHAT_MESSAGE_TYPE_PUBLIC)
    {
//        $key = static::key($channel);

        if (!is_array($data)) {
            $data = array(
                'body'  => $data,
            );
        }
        $data['type'] = $type;

        $data = json_encode($data);

        if (!is_array($channel)) {
            $channel = array($channel);
        }

        foreach ($channel as $val) {
            $key = static::key($val);

            $this->db->publish($key, $data);
        }

        return true;
    }

    public function publishSystem($channel, $data)
    {
        return $this->publish($channel, $data, static::CHAT_MESSAGE_TYPE_SYSTEM);
    }

    public function publishPublic($channel, $data)
    {
        return $this->publish($channel, $data, static::CHAT_MESSAGE_TYPE_PUBLIC);
    }

    public function publishGift($channel, $data)
    {
        return $this->publish($channel, $data, static::CHAT_MESSAGE_TYPE_GIFT);
    }

    public function publishRed($channel, $data)
    {
        return $this->publish($channel, $data, static::CHAT_MESSAGE_TYPE_RED);
    }

    public function publishGetRed($channel, $data)
    {
        return $this->publish($channel, $data, static::CHAT_MESSAGE_TYPE_GET_RED);
    }

    public function publishGoods($channel, $data)
    {
        return $this->publish($channel, $data, static::CHAT_MESSAGE_TYPE_GOODS);
    }

    public function operate($channel, $data, $type)
    {
        $key = static::key($channel);

        $data['type'] = $type;

        $data = json_encode($data);

        return $this->db->publish($key, $data);
    }

    public function operateUpdateStatus($channel, $data)
    {
        return $this->operate($channel, $data, static::OP_MESSAGE_TYPE_UPDATE_STATUS);
    }

    public function operateBan($channel, $data)
    {
        return $this->operate($channel, $data, static::OP_MESSAGE_TYPE_BAN);
    }

    public function operateGrant($channel, $data)
    {
        return $this->operate($channel, $data, static::OP_MESSAGE_TYPE_GRANT);
    }

    public function operateGag($channel, $data)
    {
        return $this->operate($channel, $data, static::OP_MESSAGE_TYPE_GAG);
    }
}