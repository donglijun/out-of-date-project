<?php
class MySQL_Streaming_EditorModel extends MySQL_BaseIDModel
{
    protected $table = 'editor';

    protected $fields = array(
        'id',
        'channel',
        'user',
        'name',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'user',
        'name',
        'created_on',
    );

    public function isMember($channel, $user)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'  => $channel,
            ':user'     => $user,
        ));

        return $stmt->fetchColumn() > 0;
    }

    function remove($channel, $user)
    {
        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'  => $channel,
            ':user'     => $user,
        ));

        return $stmt->rowCount();
    }

    function users($channel)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'  => $channel,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    function channels($user)
    {
        $sql = "SELECT `channel` FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user'  => $user,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}