<?php
class MySQL_Streaming_ServerModel extends MySQL_BaseIDModel
{
    const ACTIVE_WINDOW = 120;

    protected $table = 'server';

    protected $fields = array(
        'id',
        'name',
        'ip',
        'port',
        'weight',
        'description',
        'created_on',
        'updated_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'ip',
        'port',
        'weight',
        'description',
        'created_on',
        'updated_on',
    );

    public function getWeightings()
    {
        $sql = "SELECT `ip`, `port`, `weight` FROM `{$this->schema}`.`{$this->table}` WHERE `weight`>0";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}