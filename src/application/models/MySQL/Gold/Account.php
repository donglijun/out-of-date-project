<?php
class MySQL_Gold_AccountModel extends MySQL_BaseIDModel
{
    protected $table = 'gold_account';

    protected $fields = array(
        'id',
        'recharge_num',
        'earn_num',
//        'remained_earn_num',
        'locked_recharge_num',
//        'remained_earn_money',
//        'withdraw_money',
        'recharge_times',
    );

    protected $defaultFields = array(
        'id',
        'recharge_num',
        'earn_num',
//        'remained_earn_num',
        'locked_recharge_num',
//        'remained_earn_money',
//        'withdraw_money',
        'recharge_times',
    );

    public static function numToMoney($num)
    {
        return $num / 100;
    }

    public function balance($id)
    {
        $result = false;

        if (!($result = $this->getRow($id))) {
            $this->insert(array(
                'id' => $id,
            ));

            $result = $this->getRow($id);
        }

        return $result;
    }

    public function recharge($id, $num)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `recharge_num`=`recharge_num`+:num, `recharge_times`=`recharge_times`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':num'  => $num,
            ':id'   => $id,
        ));

        return $stmt->rowCount() ? true : false;
    }

    public function consume($id, $num)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `recharge_num`=`recharge_num`-:num WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':num'  => $num,
            ':id'   => $id,
        ));

        return $stmt->rowCount() ? true : false;
    }

    public function earn($id, $num, $money = 0)
    {
//        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `earn_num`=`earn_num`+:num, `remained_earn_num`=`remained_earn_num`+:num, `remained_earn_money`=`remained_earn_money`+:money WHERE `id`=:id";
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `earn_num`=`earn_num`+:num WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':num'   => $num,
            ':id'    => $id,
//            ':money' => $money,
        ));

        return $stmt->rowCount() ? true : false;
    }

//    public function withdraw($id, $money, $golds)
//    {
////        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `withdraw_money`=`withdraw_money`+`remained_earn_money`, `remained_earn_num`=0, `remained_earn_money`=0 WHERE `id`=:id";
//        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `remained_earn_num`=`remained_earn_num`-:golds, `remained_earn_money`=`remained_earn_money`-:money WHERE `id`=:id";
//
//        $stmt = $this->db->prepare($sql);
//        $stmt->execute(array(
//            ':id'    => $id,
//            ':money' => $money,
//            ':golds' => $golds,
//        ));
//
//        return $stmt->rowCount() ? true : false;
//    }

//    public function completeWithdraw($id, $money)
//    {
//        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `withdraw_money`=`withdraw_money`+:money WHERE `id`=:id";
//
//        $stmt = $this->db->prepare($sql);
//        $stmt->execute(array(
//            ':money'  => $money,
//            ':id'     => $id,
//        ));
//
//        return $stmt->rowCount() ? true : false;
//    }

    public function lock($id, $num)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `locked_recharge_num`=`locked_recharge_num`+:num WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':num'  => $num,
            ':id'   => $id,
        ));

        return $stmt->rowCount() ? true : false;
    }
}