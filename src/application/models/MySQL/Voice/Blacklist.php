<?php
class MySQL_Voice_BlacklistModel extends MySQL_BaseIDModel
{
    protected $table = 'blacklist';

    protected $fields = array(
        'id',
        'room',
        'user',
        'created_on',
        'created_by',
    );

    protected $defaultFields = array(
        'id',
        'room',
        'user',
        'created_on',
        'created_by',
    );

    public function isMember($room, $user)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `room`=:room AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':room' => $room,
            ':user' => $user,
        ));

        return $stmt->fetchColumn() > 0;
    }

    function remove($room, $users)
    {
        $placeHolders = implode(',', array_fill(0, count($users), '?'));
        array_unshift($users, $room);

        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `room`=? AND `user` IN ({$placeHolders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($users);

        return $stmt->rowCount();
    }

    function users($room)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `room`=:room";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':room' => $room,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}