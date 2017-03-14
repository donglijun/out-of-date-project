<?php
class Redis_Video_Link_Vote_QueueModel extends Redis_BaseModel
{
    const KEY = 'video-link-vote-queue';
    const DELIMITER = ':';

    public function push($data)
    {
        if (is_array($data)) {
            $data = implode(static::DELIMITER, $data);
        }

        return $this->db->rPush(static::KEY, $data);
    }

    public function len()
    {
        return $this->db->lLen(static::KEY);
    }

    public function range($start, $end)
    {
        return $this->db->lRange(static::KEY, $start, $end);
    }

    public function trim($start, $stop)
    {
        return $this->db->lTrim(static::KEY, $start, $stop);
    }
}