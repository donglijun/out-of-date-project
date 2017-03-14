<?php
class Redis_Streaming_WatchingTask_ProgressModel extends Redis_BaseModel
{
    const TTL = 86400;

    const STATUS_PENDING = 0;

    const STATUS_COMPLETED = 1;

    const STATUS_AWARDED = 2;

    public static function key($user, $timestamp = null)
    {
        return sprintf('watching.task:%s:%s', $user, date('d', $timestamp ?: time()));
    }

    public function exists($user, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->exists($key);
    }

    public function completed($user, $timestamp = null)
    {
        $result = true;

        $key = static::key($user, $timestamp);

        if ($this->exists($user, $timestamp)) {
            $tasks = $this->db->hGetAll($key);

            foreach ($tasks as $task => $status) {
                if ($status == static::STATUS_PENDING) {
                    $result = false;

                    break;
                }
            }
        } else {
            $result = false;
        }

        return $result;
    }

    public function dailyInit($user, $tasks, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        if (!$this->db->exists($key)) {
            foreach ($tasks as $task) {
                $this->db->hSet($key, $task, 0);
            }

            $this->db->expire($key, static::TTL);
        }
    }

    public function complete($user, $task, $timestamp = null)
    {
        $result = false;

        $key = static::key($user, $timestamp);

//        if (!is_int($this->db->zRank($key, $task))) {
//            $this->db->zAdd($key, 1, $task);
//        }

        if ($this->db->hExists($key, $task) && ($this->db->hGet($key, $task) == static::STATUS_PENDING)) {
            $this->db->hSet($key, $task, static::STATUS_COMPLETED);

            $result = $task;
        }

//        $tasks = $this->db->hGetAll($key);
//
//        foreach ($tasks as $task => $status) {
//            if ($status == static::STATUS_PENDING) {
//                $this->db->hSet($key, $task, static::STATUS_COMPLETED);
//                $result = $task;
//
//                break;
//            }
//        }

        return $result;
    }

    public function award($user, $task, $timestamp = null)
    {
        $result = false;

        $key = static::key($user, $timestamp);

        if ($this->db->hExists($key, $task) && ($this->db->hGet($key, $task) == static::STATUS_COMPLETED)) {
            $this->db->hSet($key, $task, static::STATUS_AWARDED);

            $result = $task;
        }

//        $tasks = $this->db->hGetAll($key, $timestamp);
//
//        foreach ($tasks as $task => $status) {
//            if ($status == static::STATUS_COMPLETED) {
//                $this->db->hSet($key, $task, static::STATUS_AWARDED);
//                $result = $task;
//
//                break;
//            }
//        }

        return $result;
    }

    public function currentPending($user, $timestamp = null)
    {
        $result = null;

        $key = static::key($user, $timestamp);

        $tasks = $this->db->hGetAll($key);

        foreach ($tasks as $task => $status) {
            if ($status == static::STATUS_PENDING) {
                $result = $task;

                break;
            }
        }

        return $result;
    }

    public function currentCompleted($user, $timestamp = null)
    {
        $result = null;

        $key = static::key($user, $timestamp);

        $tasks = $this->db->hGetAll($key);

        foreach ($tasks as $task => $status) {
            if ($status == static::STATUS_COMPLETED) {
                $result = $task;

                break;
            }
        }

        return $result;
    }

    public function all($user, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->hGetAll($key);
    }

    public function status($user, $task, $timestamp = null)
    {
        $key = static::key($user, $timestamp);

        return $this->db->hGet($key, $task);
    }
}