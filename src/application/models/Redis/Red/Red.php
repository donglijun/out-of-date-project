<?php
class Redis_Red_RedModel extends Redis_BaseModel
{
//    const TTL = 1209600;
    const TTL = 86400; // 24 hours

    const LUA_CONSUME_RED = <<<EOT
if redis.call('hexists', KEYS[2], ARGV[1]) ~= 0 then
    return '-1'
else
    local val = redis.call('lpop', KEYS[1])
    if val then
        redis.call('hset', KEYS[2], ARGV[1], val)
        return val
    end
end
return nil
EOT;


    public static function readyKey($id)
    {
        return sprintf('red.ready:%s', $id);
    }

    public static function consumedKey($id)
    {
        return sprintf('red.consumed:%s', $id);
    }

    public function push($id, $data)
    {
        $readyKey = static::readyKey($id);

        array_unshift($data, $readyKey);

        $result = call_user_func_array(array($this->db, 'rpush'), $data);

        $this->db->expire($readyKey, static::TTL);

        return $result;
    }

    public function pop($id, $user)
    {
        $readyKey = static::readyKey($id);
        $consumedKey = static::consumedKey($id);

        return $this->db->eval(static::LUA_CONSUME_RED, array($readyKey, $consumedKey, $user), 2);
    }

    public function consumedPoints($id)
    {
        $result = 0;

        $consumedKey = static::consumedKey($id);

        if ($vals = $this->db->hVals($consumedKey)) {
            $result = array_sum($vals);
        }

        return $result;
    }

    public function consumedNumber($id)
    {
        $consumedKey = static::consumedKey($id);

        return $this->db->hLen($consumedKey);
    }

    public function consumedAll($id)
    {
        $consumedKey = static::consumedKey($id);

        return $this->db->hGetAll($consumedKey);
    }

    public function retire($id)
    {
        $readyKey = static::readyKey($id);

        return $this->db->del($readyKey);
    }
}