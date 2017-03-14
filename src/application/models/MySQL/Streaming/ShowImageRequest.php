<?php
class MySQL_Streaming_ShowImageRequestModel extends MySQL_BaseIDModel
{
    const REQ_STATUS_PENDING = 0;

    const REQ_STATUS_APPROVED = 1;

    const REQ_STATUS_DENIED = 2;

    protected $table = 'show_image_request';

    protected $fields = array(
        'id',
        'channel',
        'small_show_image',
        'large_show_image',
        'req_status',
        'created_on',
        'processed_on',
        'processed_by',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'small_show_image',
        'large_show_image',
        'req_status',
        'created_on',
    );

    public function getLastReq($channel, $columns = null)
    {
        $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel' => $channel,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approve($id, $by)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `req_status`=:approved_status, `processed_by`=:by, `processed_on`=:on WHERE `id`=:id AND `req_status`=:pending_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':approved_status' => static::REQ_STATUS_APPROVED,
            ':pending_status' => static::REQ_STATUS_PENDING,
            ':by' => $by,
            ':on' => time(),
        ));
    }

    public function deny($id, $by)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `req_status`=:denied_status, `processed_by`=:by, `processed_on`=:on WHERE `id`=:id AND `req_status`=:pending_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':denied_status' => static::REQ_STATUS_DENIED,
            ':pending_status' => static::REQ_STATUS_PENDING,
            ':by' => $by,
            ':on' => time(),
        ));
    }

    public static function getStatusMap()
    {
        return array(
            static::REQ_STATUS_PENDING  => 'Pending',
            static::REQ_STATUS_APPROVED => 'Approved',
            static::REQ_STATUS_DENIED   => 'Denied',
        );
    }
}