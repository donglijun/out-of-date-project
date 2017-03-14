<?php
class Redis_Voice_Room_TemporaryModel extends Redis_BaseModel
{
    const TTL = 86400;

    // Use standalone database
//    protected $dbindex = 1;

    public static function key($unique)
    {
        return base64_encode($unique);
    }

    public function get($unique)
    {
        $result = array();

        $key = static::key($unique);

        if ($data = $this->db->get($key)) {
            list($room, $ip, $port, ) = explode(',', $data);

            $result = array(
                'room'  => $room,
                'ip'    => $ip,
                'port'  => $port,
                'ttl'   => $this->db->ttl($key),
            );
        }

        return $result;
    }

    public function set($unique, $room, $ip, $port)
    {
        $key  = static::key($unique);
        $data = array($room, $ip, $port);

        if ($result = $this->db->setnx($key, implode(',', $data))) {
            $this->db->expire($key, static::TTL);
        }

        return $result;
    }

    public function update($unique, $ip, $port)
    {
        $result = false;

        $key = static::key($unique);

        if ($data = $this->db->get($key)) {
            list($room, ) = explode(',', $data);

            $data = array($room, $ip, $port);

            $result = $this->db->setex($key, $this->db->ttl($key), implode(',', $data));
        }

        return $result;
    }

    public function ttl($unique)
    {
        $key = static::key($unique);

        return $this->db->ttl($key);
    }

//    public function alloc($unique)
//    {
//        $result = array();
//
//        $roomKey = static::roomKey($unique);
//
//        $roomID = $this->db->get($roomKey);
//
//        if ($roomID) {
//            $result = array(
//                'room'  => $roomID,
//                'ttl'   => $this->db->ttl($roomKey),
//            );
//        } else {
//            $roomID = $this->newID();
//
//            if ($roomID) {
//                $this->db->setex($roomKey, static::TTL, $roomID);
//
//                $result = array(
//                    'room'  => $roomID,
//                    'ttl'   => static::TTL,
//                );
//            }
//        }
//
//        return $result;
//    }
}