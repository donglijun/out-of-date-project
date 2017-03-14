<?php
class MySQL_Streaming_WatchingTaskModel extends MySQL_BaseIDModel
{
    protected $table = 'watching_task';

    protected $fields = array(
        'id',
        'level',
        'gifts',
        'points',
        'timer',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'level',
        'gifts',
        'points',
        'timer',
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` ORDER BY `level` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}