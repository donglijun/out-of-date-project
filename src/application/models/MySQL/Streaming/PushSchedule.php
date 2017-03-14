<?php
class MySQL_Streaming_PushScheduleModel extends MySQL_BaseIDModel
{
    protected $table = 'push_schedule';

    protected $fields = array(
        'id',
        'channel',
        'push_on',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'push_on',
    );

    public function getFuture()
    {
        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `push_on`>=:push_on ORDER BY `push_on` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':push_on'  => time(),
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRightOne()
    {
        $timestamp = time();
        $min = $timestamp - 60;
        $max = $timestamp + 60;
        $sql =  "SELECT `channel` FROM `{$this->schema}`.`{$this->table}` WHERE `push_on`>:min AND `push_on`<:max LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':min'  => $min,
            ':max'  => $max,
        ));

        return $stmt->fetchColumn();
    }
}