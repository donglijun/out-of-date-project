<?php
class MySQL_LOL_ModeModel extends MySQL_BaseIDModel
{
    protected $table = 'mode';

    protected $fields = array(
        'id',
        'name',
        'alias',
        'description',
        'is_index',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'alias',
        'is_index',
    );

    public function getRowByName($name, $columns = null)
    {
        $result = array();
        $fields = $data = array();

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

        $fields = implode(',', $fields);

        if ($name) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `name`=:name";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':name' => $name,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function getRowsByName($names, $columns = null)
    {
        $result = $rowset = array();
        $fields = $data = array();

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

        if ($fields && !isset($fields['name'])) {
            $fields[] = $this->quoteIdentifier('name');
        }
        $fields = implode(',', $fields);

        if ($names) {
            $placeHolders = implode(',', array_fill(0, count($names), '?'));

            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `name` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($names);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rowset[$row['name']] = $row;
                }
            }

            foreach ($names as $name) {
                if (array_key_exists($name, $rowset)) {
                    $result[$name] = $rowset[$name];
                }
            }
        }

        return $result;
    }

    public function getModeMap()
    {
        $result = array();

        $sql = "SELECT `id`,`name`,`alias`,`is_index` FROM `{$this->schema}`.`{$this->table}`";

        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }
}