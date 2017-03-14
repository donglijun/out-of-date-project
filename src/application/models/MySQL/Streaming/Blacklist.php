<?php
class MySQL_Streaming_BlacklistModel extends MySQL_BaseIDModel
{
    protected $table = 'blacklist';

    protected $fields = array(
        'id',
        'channel',
        'user',
        'name',
        'created_on',
        'created_by',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'user',
        'name',
        'created_on',
        'created_by',
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

    function remove($channel, $users)
    {
        $placeHolders = implode(',', array_fill(0, count($users), '?'));
        array_unshift($users, $channel);

        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=? AND `user` IN ({$placeHolders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($users);

        return $stmt->rowCount();
    }

    function users($channel)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'  => $channel,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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