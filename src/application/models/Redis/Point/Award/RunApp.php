<?php
class Redis_Point_Award_RunAppModel extends Redis_BaseModel
{
    const KEY = 'point.award.runapp';

    const ID_OFFSET = 100000;

    public function mark($user)
    {
        $result = false;

        $user = $user - static::ID_OFFSET;

        if ($user >= 0 && $user < self::MAX_OFFSET) {
            $result = $this->db->setBit(static::KEY, $user, 1);
        }

        return $result;
    }

    public function check($user)
    {
        $user = $user - static::ID_OFFSET;

        $val = $this->db->getBit(static::KEY, $user);

        return (int) $val > 0;
    }

    public function count()
    {
        return $this->db->bitCount(static::KEY);
    }
}