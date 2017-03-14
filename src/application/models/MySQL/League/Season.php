<?php
class MySQL_League_SeasonModel extends MySQL_BaseIDModel
{
    const STATUS_PENDING = 0;

    const STATUS_OPENING = 1;

    const STATUS_LOCKING = 2;

    const STATUS_CLOSING = 3;

    protected $table = 'league_season';

    protected $fields = array(
        'id',
        'title',
        'status',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'status',
    );

    public static function getStatusMap()
    {
        return array(
            static::STATUS_PENDING => 'Pending',
            static::STATUS_OPENING => 'Opening',
            static::STATUS_LOCKING => 'Locking',
            static::STATUS_CLOSING => 'Closing',
        );
    }

    public function open($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=:opening_status WHERE `id`=:id AND `status`=:pending_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':opening_status' => static::STATUS_OPENING,
            ':pending_status' => static::STATUS_PENDING,
        ));
    }

    public function lock($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=:locking_status WHERE `id`=:id AND `status`=:opening_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':locking_status' => static::STATUS_LOCKING,
            ':opening_status' => static::STATUS_OPENING,
        ));
    }

    public function close($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=:closing_status WHERE `id`=:id AND `status`=:locking_status";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id' => $id,
            ':closing_status' => static::STATUS_CLOSING,
            ':locking_status' => static::STATUS_LOCKING,
        ));
    }

    public function validate($id)
    {
        $sql = "SELECT `status` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id' => $id,
        ));

        $status = $stmt->fetchColumn();

        return $status == static::STATUS_OPENING;
    }

    public function getAll()
    {
        $result = array();

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` DESC";
        $stmt = $this->db->query($sql);

        if ($rows = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($rows as $row) {
                $result[$row['id']] = $row;
            }
        }

        return $result;
    }
}