<?php
class MySQL_Gift_AccountModel extends MySQL_BaseIDModel
{
    const UNIT_COLLECT_CHECKIN = 1; //5;

    const UNIT_COLLECT_SHARE_FACEBOOK = 1; //5;

    const UNIT_CONSUME_GIVE = 1; //10;

    protected $table = 'gift_account';

    protected $fields = array(
        'id',
        'collecting',
        'giving',
        'remaining',
        'growth_level',
        'growth_points',
        'num6',
        'num7',
        'num8',
        'num9',
    );

    protected $defaultFields = array(
        'id',
        'collecting',
        'giving',
        'remaining',
        'growth_level',
        'growth_points',
    );

    public function bill($id)
    {
        $sql = "SELECT `collecting`, `giving`, `growth_level`, `growth_points` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $id,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function remain($id)
    {
        $sql = "SELECT `giving` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $id,
        ));

        return (int) $stmt->fetchColumn(0);
    }

    public function collect($id, $number)
    {
        $result = false;

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `collecting`=`collecting`+:number WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':number'   => $number,
            ':id'       => $id,
        ));

        $result = $stmt->rowCount() ? true : false;

        return $result;
    }

    public function give($from, $to, $number)
    {
        $result = false;

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `collecting`=`collecting`-:number WHERE `id`=:id AND `collecting`>=:number";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':number'   => $number,
            ':id'       => $from,
        ));

        if ($stmt->rowCount()) {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `giving`=`giving`+:number, `remaining`=`remaining`+:number WHERE `id`=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':number'   => $number,
                ':id'       => $to,
            ));

            $result = $stmt->rowCount() ? true : false;
        }

        return $result;
    }
}