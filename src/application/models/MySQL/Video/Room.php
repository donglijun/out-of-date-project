<?php
class MySQL_Video_RoomModel extends MySQL_BaseIDModel
{
    protected $table = 'room';

    protected $fields = array(
        'id',
        'title',
        'stream_key',
        'bio',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'stream_key',
        'bio',
    );

    public function authenticate($room, $password)
    {
        $sql = "SELECT `password` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $room,
        ));

        $passwordHash = $stmt->fetchColumn();

        return $password && password_verify($password, $passwordHash);
    }

    public function authenticateStream($room, $key)
    {
        $sql = "SELECT `stream_key` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $room,
        ));

        $streamKey = $stmt->fetchColumn();

        return $streamKey == $key;
    }

    public static function makeStreamKey($room, $hash)
    {
        return sprintf('live_%d_%s', $room, $hash);
    }

    public function resetStreamKey($room)
    {
        $streamKey = md5($room . microtime());

        $this->update($room, array(
            'stream_key' => $streamKey,
        ));

        return static::makeStreamKey($room, $streamKey);
    }
}