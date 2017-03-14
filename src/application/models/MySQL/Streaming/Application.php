<?php
class MySQL_Streaming_ApplicationModel extends MySQL_BaseIDModel
{
    const APP_TYPE_SIGNED = 1;

    const APP_TYPE_EXCLUSIVE = 2;

    const APP_STATUS_PENDING = 0;

    const APP_STATUS_APPROVED = 1;

    const APP_STATUS_DENIED = 2;

    protected $table = 'application';

    protected $fields = array(
        'id',
        'channel',
        'name',
        'id_photo_front',
        'id_photo_back',
        'phone',
        'skype',
        'twitch',
        'facebook',
        'app_type',
        'app_status',
        'created_on',
        'processed_on',
        'processed_by',
        'memo',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'name',
        'id_photo_front',
        'id_photo_back',
        'phone',
        'skype',
        'twitch',
        'facebook',
        'app_type',
        'app_status',
        'created_on',
    );

    public function getLastApp($channel, $type, $columns = null)
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `app_type`=:app_type ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel' => $channel,
            ':app_type' => $type,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approve($id, $by)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `app_status`=:approved_status, `processed_by`=:by, `processed_on`=:on WHERE `id`=:id AND `app_status`=:pending_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':approved_status' => static::APP_STATUS_APPROVED,
            ':pending_status' => static::APP_STATUS_PENDING,
            ':by' => $by,
            ':on' => time(),
        ));
    }

    public function deny($id, $by)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `app_status`=:denied_status, `processed_by`=:by, `processed_on`=:on WHERE `id`=:id AND `app_status`=:pending_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':denied_status' => static::APP_STATUS_DENIED,
            ':pending_status' => static::APP_STATUS_PENDING,
            ':by' => $by,
            ':on' => time(),
        ));
    }

    public static function getTypeMap()
    {
        return array(
            static::APP_TYPE_SIGNED    => 'Signed',
            static::APP_TYPE_EXCLUSIVE => 'Exclusive',
        );
    }

    public static function getStatusMap()
    {
        return array(
            static::APP_STATUS_PENDING  => 'Pending',
            static::APP_STATUS_APPROVED => 'Approved',
            static::APP_STATUS_DENIED   => 'Denied',
        );
    }
}