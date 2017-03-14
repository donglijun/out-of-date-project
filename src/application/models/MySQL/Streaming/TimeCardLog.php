<?php
class MySQL_Streaming_TimeCardLogModel extends MySQL_BaseIDModel
{
    protected $table = 'time_card_log';

    protected $fields = array(
        'id',
        'number',
        'user',
        'mark',
        'operated_on',
        'operated_by',
    );

    protected $defaultFields = array(
        'id',
        'number',
        'user',
        'mark',
        'operated_on',
        'operated_by',
    );

    public function getUsersByMark($mark)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `mark`=:mark";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':mark' => $mark,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function check($mark, $user)
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `mark`=:mark AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':mark' => $mark,
            ':user' => $user,
        ));

        return $stmt->fetchColumn(0) > 0;
    }
}