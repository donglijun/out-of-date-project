<?php
class MySQL_Gold_RechargeOrderModel extends MySQL_BaseIDModel
{
    const PROCESSED_STATUS_PURCHASED = 1;

    const PROCESSED_STATUS_CANCELLED = 2;

    protected $table = 'gold_recharge_order';

    protected $fields = array(
        'id',
        'user',
        'foreign_id',
        'product',
        'golds',
        'cost',
        'cost_unit',
        'foreign_timestamp',
        'is_processed',
        'is_bad',
        'created_on',
        'processed_on',
        'bad_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'foreign_id',
        'product',
        'golds',
        'cost',
        'cost_unit',
        'foreign_timestamp',
        'is_processed',
        'is_bad',
        'created_on',
        'processed_on',
        'bad_on',
    );

    public function bad($foreignId)
    {
        $result = false;

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `is_bad`=1, `bad_on`=:bad_on WHERE `foreign_id`=:foreign_id AND `is_bad`=0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            'foreign_id' => $foreignId,
            'bad_on'     => time(),
        ));

        $result = $stmt->rowCount();

        return $result;
    }

    public function findForeign($foreignId, $columns = null)
    {
        $fields = array();

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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `foreign_id`=:foreign_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':foreign_id' => $foreignId,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}