<?php
class MySQL_Card_CardModel extends MySQL_BaseIDModel
{
    protected $table = 'card';

    protected $fields = array(
        'id',
        'type',
        'code',
        'status',
        'created_on',
        'created_by',
        'consumed_on',
        'consumed_by',
    );

    protected $defaultFields = array(
        'id',
        'type',
        'code',
        'status',
        'created_on',
        'created_by',
        'consumed_on',
        'consumed_by',
    );

    public function consume($type, $user, $timestamp = null)
    {
        $result = false;

        $timestamp = $timestamp ?: time();

        $sql = "SELECT `id`, `code` FROM `{$this->schema}`.`{$this->table}` WHERE `type`=:type AND `status`=0 ORDER BY `id` ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':type' => $type,
        ));

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sql2 = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=1, `consumed_on`=:timestamp, `consumed_by`=:user WHERE `id`=:id AND `status`=0";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute(array(
                ':timestamp' => $timestamp,
                ':user'      => $user,
                ':id'        => $row['id'],
            ));

            if ($stmt2->rowCount()) {
                $result = $row['code'];
            }
        }

        return $result;
    }
}