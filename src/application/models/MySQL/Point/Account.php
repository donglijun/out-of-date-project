<?php
class MySQL_Point_AccountModel extends MySQL_BaseIDModel
{
    protected $table = 'point_account';

    protected $fields = array(
        'id',
        'number',
        'updated_on',
    );

    protected $defaultFields = array(
        'id',
        'number',
        'updated_on',
    );

    public function number($id)
    {
        $result = false;

        $sql = "SELECT `number` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id' => $id,
        ));

        if (($result = $stmt->fetchColumn(0)) === false) {
            $this->insert(array(
                'id'         => $id,
                'updated_on' => time(),
            ));

            $result = 0;
        }

        return $result;
    }

    public function incr($id, $number)
    {
        $this->number($id);

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `number`=`number`+:number, `updated_on`=:updated_on WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':number'     => $number,
            ':id'         => $id,
            ':updated_on' => time(),
        ));

        return $stmt->rowCount() ? true : false;
    }
}