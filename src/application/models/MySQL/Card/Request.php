<?php
class MySQL_Card_RequestModel extends MySQL_BaseIDModel
{
    protected $table = 'card_request';

    protected $fields = array(
        'id',
        'user',
        'type',
        'title',
        'price',
        'code',
        'created_on',
        'processed_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'type',
        'title',
        'price',
        'code',
        'created_on',
        'processed_on',
    );

    public function getNotProcessed($hoursAgo)
    {
        $ago = time() - $hoursAgo * 3600;

        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `created_on`<:ago AND `processed_on`=0 ORDER BY `id` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':ago' => $ago,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}