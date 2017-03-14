<?php
class MySQL_League_ApplicationModel extends MySQL_BaseIDModel
{
    const APP_STATUS_PENDING = 0;

    const APP_STATUS_APPROVED = 1;

    const APP_STATUS_DENIED = 2;

    protected $table = 'league_application';

    protected $fields = array(
        'id',
        'season',
        'title',
        'leader_name',
        'leader_phone',
        'leader_phone2',
        'leader_email',
        'teams',
        'logo',
        'video',
        'description',
        'app_status',
        'created_on',
        'created_by',
        'processed_on',
        'processed_by',
        'memo',
        'reason',
    );

    protected $defaultFields = array(
        'id',
        'season',
        'title',
        'leader_name',
        'leader_phone',
        'leader_phone2',
        'leader_email',
        'teams',
        'logo',
        'video',
        'description',
        'app_status',
        'created_on',
        'created_by',
        'reason',
    );

    public function getLastApp($season, $user, $columns = null)
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `season`=:season AND `created_by`=:created_by ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':season' => $season,
            ':created_by' => $user,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSeasonTeams($season, $columns = null)
    {
        $result = $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        if (!in_array($this->primary, $columns)) {
            $columns[] = $this->primary;
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        if ($fields) {
            $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE`season`=:season AND `app_status`=:app_status ORDER BY `title` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':season' => $season,
                ':app_status' => static::APP_STATUS_APPROVED,
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
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

    public function deny($id, $by, $reason = '')
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `app_status`=:denied_status, `processed_by`=:by, `processed_on`=:on, `reason`=:reason WHERE `id`=:id AND `app_status`=:pending_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':denied_status' => static::APP_STATUS_DENIED,
            ':pending_status' => static::APP_STATUS_PENDING,
            ':by' => $by,
            ':on' => time(),
            ':reason' => $reason,
        ));
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