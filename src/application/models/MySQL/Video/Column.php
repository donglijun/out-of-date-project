<?php
class MySQL_Video_ColumnModel extends MySQL_BaseIDModel
{
    protected $table = 'column';

    protected $fields = array(
        'id',
        'name',
        'description',
    );

    protected $defaultFields = array(
        'id',
        'name',
    );


    public function getAllRows($columns = null)
    {
        $result = array();
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

        if ($fields = implode(',', $fields)) {
            $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` ORDER BY `name` ASC";
            $stmt = $this->db->query($sql);

            if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
                $result = array();
            }
        }

        return $result;
    }
}