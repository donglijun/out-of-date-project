<?php
class MySQL_Voice_ManagerModel extends MySQL_BaseIDModel
{
    protected $table = 'manager';

    protected $fields = array(
        'id',
        'room',
        'user',
        'granted_on',
        'granted_by',
    );

    protected $defaultFields = array(
        'id',
        'room',
        'user',
        'granted_on',
        'granted_by',
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

    function remove($room, $user)
    {
        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `room`=:room AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':room' => $room,
            ':user' => $user,
        ));

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