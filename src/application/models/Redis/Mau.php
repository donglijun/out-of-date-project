<?php
class Redis_MauModel extends Redis_BaseModel
{
    public static function key($timestamp = null)
    {
        return 'mau:' . date('Ym', $timestamp ?: time());
    }

    public function count($timestamp = null)
    {
        $key = static::key($timestamp);
        $score = (int) array_pop(explode(':', $key));
        /**
         * Save history
         */
        $this->db->zAdd('mau:history', $score, $key);

        $dauKeyPrefix = substr(Redis_DauModel::key($timestamp), 0, -2);

        /**
         * Calculate dau union in the month
         */
        $this->db->bitOp('OR', $key, $dauKeyPrefix . '01', $dauKeyPrefix . '02', $dauKeyPrefix . '03', $dauKeyPrefix . '04',
            $dauKeyPrefix . '05', $dauKeyPrefix . '06', $dauKeyPrefix . '07', $dauKeyPrefix . '08', $dauKeyPrefix . '09',
            $dauKeyPrefix . '10', $dauKeyPrefix . '11', $dauKeyPrefix . '12', $dauKeyPrefix . '13', $dauKeyPrefix . '14',
            $dauKeyPrefix . '15', $dauKeyPrefix . '16', $dauKeyPrefix . '17', $dauKeyPrefix . '18', $dauKeyPrefix . '19',
            $dauKeyPrefix . '20', $dauKeyPrefix . '21', $dauKeyPrefix . '22', $dauKeyPrefix . '23', $dauKeyPrefix . '24',
            $dauKeyPrefix . '25', $dauKeyPrefix . '26', $dauKeyPrefix . '27', $dauKeyPrefix . '28', $dauKeyPrefix . '29',
            $dauKeyPrefix . '30', $dauKeyPrefix . '31');

        return $this->db->bitCount($key);
    }

    public function clear($from ,$to)
    {
        $result = 0;

        $scoreFrom  = date('Ym', $from);
        $scoreTo    = date('Ym', $to);
        $keys = $this->db->zRangeByScore('mau:history', (int) $scoreFrom, (int) $scoreTo);

        /**
         * Remove mau keys
         */
        foreach ($keys as $key) {
            $this->db->del($key);
        }

        /**
         * Update history
         */
        $result = $this->db->zRemRangeByScore('mau:history', (int) $scoreFrom, (int) $scoreTo);

        return $result;
    }
}