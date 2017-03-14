<?php
class MySQL_Goods_GroupModel extends MySQL_BaseIDModel
{
    protected $table = 'goods_group';

    protected $fields = array(
        'id',
        'number',
        'title',
        'description',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'number',
        'title',
        'description',
    );

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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` ORDER BY `number` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}