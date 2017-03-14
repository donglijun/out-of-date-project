<?php
class MySQL_LOL_User_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'user';

    protected $fields = array(
        'id',
        'name',
        'level',
        'icon_id',
        'point',
        'tunfwb',
        'metadata',
        'last_mk_user',
        'updated_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'level',
        'icon_id',
        'point',
        'tunfwb',
        'metadata',
        'last_mk_user',
        'updated_on',
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

    public function getNames($ids)
    {
        $result = array();

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            $sql = "SELECT `id`,`name` FROM `{$this->schema}`.`{$this->table}` WHERE `{$this->primary}` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[$row['id']] = $row['name'];
            }
        }

        return $result;
    }
}