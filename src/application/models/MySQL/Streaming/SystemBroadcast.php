<?php
class MySQL_Streaming_SystemBroadcastModel extends MySQL_BaseIDModel
{
    protected $table = 'system_broadcast';

    protected $fields = array(
        'id',
        'body',
        'target_channel',
        'created_on',
        'created_by',
    );

    protected $defaultFields = array(
        'id',
        'body',
        'target_channel',
        'created_on',
        'created_by',
    );

    public function getLast()
    {
        $sql = "SELECT `id`, `body`, `created_on` FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->query($sql);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}