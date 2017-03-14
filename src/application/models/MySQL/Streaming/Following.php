<?php
class MySQL_Streaming_FollowingModel extends MySQL_BaseIDModel
{
    protected $table = 'following';

    protected $fields = array(
        'id',
        'user',
        'channel',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'channel',
        'created_on',
    );

    public function add($user, $channel)
    {
        $data = array(
            'user'          => $user,
            'channel'       => $channel,
            'created_on'    => time(),
        );

        return $this->replace($data);
    }

    public function remove($user, $channel)
    {
        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user AND `channel`=:channel";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':user'     => $user,
            ':channel'  => $channel,
        ));
    }

    public function exists($user, $channel)
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user AND `channel`=:channel";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user'     => $user,
            ':channel'  => $channel,
        ));

        return (int) $stmt->fetchColumn(0) > 0;
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