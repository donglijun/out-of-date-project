<?php
class MySQL_Red_TypeModel extends MySQL_BaseIDModel
{
    protected $table = 'red_type';

    protected $fields = array(
        'id',
        'title',
        'points',
        'number',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'points',
        'number',
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}