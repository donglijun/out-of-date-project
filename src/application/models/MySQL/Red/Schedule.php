<?php
class MySQL_Red_ScheduleModel extends MySQL_BaseIDModel
{
    const PUBLISH_STATUS_READY = 0;

    const PUBLISH_STATUS_SUCCESSFUL = 1;

    const PUBLISH_STATUS_FAILED = 2;

    const PUBLISH_STATUS_CANCELED = 4;

    protected $table = 'red_schedule';

    protected $fields = array(
        'id',
        'points',
        'number',
        'memo',
        'target_channel',
        'target_client',
        'publish_on',
        'publish_status',
        'created_on',
        'created_by',
        'created_name',
    );

    protected $defaultFields = array(
        'id',
        'points',
        'number',
        'memo',
        'target_channel',
        'target_client',
        'publish_on',
        'publish_status',
        'created_on',
        'created_by',
        'created_name',
    );

    public function getFuture()
    {
        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `publish_on`>=:publish_on ORDER BY `publish_on` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':publish_on' => time(),
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRightOne()
    {
        $timestamp = time();
        $min = $timestamp - 60;
        $max = $timestamp + 60;
        $sql =  "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `publish_on`>:min AND `publish_on`<:max AND `publish_status`=:status LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':min'    => $min,
            ':max'    => $max,
            ':status' => static::PUBLISH_STATUS_READY,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function publishStatusMessages()
    {
        return array(
            static::PUBLISH_STATUS_READY      => 'Ready',
            static::PUBLISH_STATUS_SUCCESSFUL => 'Successful',
            static::PUBLISH_STATUS_FAILED     => 'Failed',
            static::PUBLISH_STATUS_CANCELED   => 'Canceled',
        );
    }
}