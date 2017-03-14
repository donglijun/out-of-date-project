<?php
class MySQL_Streaming_TimeCardModel extends MySQL_BaseIDModel
{
    protected $table = 'time_card';

    protected $fields = array(
        'id',
        'number',
        'status',
        'created_on',
        'created_by',
        'consumed_on',
    );

    protected $defaultFields = array(
        'id',
        'number',
        'status',
        'created_on',
        'created_by',
        'consumed_on',
    );

    public function hasCard()
    {
        $sql = "SELECT `status` FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->query($sql);

        return $stmt->fetchColumn(0) === '0';
    }

    public function consume($timestamp = null)
    {
        $result = false;

        $timestamp = $timestamp ?: time();

        $sql = "SELECT `number` FROM `{$this->schema}`.`{$this->table}` WHERE `status`=0 ORDER BY `id` ASC LIMIT 1";
        $stmt = $this->db->query($sql);

        if ($result = $stmt->fetchColumn(0)) {
            $sql2 = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=1, `consumed_on`=:timestamp WHERE `number`=:number";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute(array(
                ':timestamp'    => $timestamp,
                ':number'       => $result,
            ));
        }

        return $result;
    }
}