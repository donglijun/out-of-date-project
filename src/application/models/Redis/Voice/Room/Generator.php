<?php
class Redis_Voice_Room_GeneratorModel extends Redis_BaseModel
{
    const ID_RANGE_MIN = 4000000000;

    const ID_RANGE_MAX = 4294967296;

    const LUA_NEW_ID = <<<EOT
local min = tonumber(ARGV[1])
local max = tonumber(ARGV[2])
local current = tonumber(redis.call("incr", KEYS[1]))
if ((current > max) or (current < min)) then
    redis.call("set", KEYS[1], min)
    current = min
end
return tostring(current)
EOT;

    // Use standalone database
//    protected $dbindex = 1;

    public static function key()
    {
        return 'mk-voice-room-temporary-counter';
    }

    public function newID()
    {
        $result = 0;

        $key = static::key();

        $result = $this->db->eval(static::LUA_NEW_ID, array($key, static::ID_RANGE_MIN, static::ID_RANGE_MAX), 1);

        return $result;
    }
}