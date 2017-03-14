<?php
class MySQL_Card_TypeModel extends MySQL_BaseIDModel
{
    protected $table = 'card_type';

    protected $fields = array(
        'id',
        'title',
        'price',
        'game',
        'number',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'price',
        'game',
        'number',
    );

    public function incrNumber($id, $number = 1)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `number`=`number`+:number  WHERE `{$this->primary}`=:id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':id'     => $id,
            ':number' => $number,
        ));
    }

    public function getAll($columns = null)
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}