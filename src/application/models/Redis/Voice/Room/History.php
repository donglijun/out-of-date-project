<?php
class Redis_Voice_Room_HistoryModel extends Redis_BaseModel
{
    protected $max = 10;

    public static function key($user)
    {
        return sprintf('voice:room:history:%d', $user);
    }

    public static function val($roomID, $roomTitle)
    {
        return sprintf("%s\t%s", $roomID, $roomTitle);
    }

    public static function parseVal($val)
    {
        return explode("\t", $val);
    }

    public function update($user, $roomID, $roomTitle)
    {
        $result = false;

        $key = static::key($user);
        $val = static::val($roomID, $roomTitle);

        $result = $this->db->zAdd($key, time(), $val);

        if (($total = $this->db->zCard($key)) > $this->max) {
            $this->db->zRemRangeByRank($key, 0, $total - $this->max - 1);
        }

        return $result;
    }

    public function getHistory($user)
    {
        $result = array();

        $key = static::key($user);

        $vals = $this->db->zRevRange($key, 0, -1, true);

        foreach ($vals as $val => $score) {
            list($roomID, $roomTitle, ) = static::parseVal($val);

            $result[] = array(
                'roomID'    => $roomID,
                'roomTitle' => $roomTitle,
                'timestamp' => (int) $score,
            );
        }

        return $result;
    }
}