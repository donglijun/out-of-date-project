<?php
class MySQL_BaseIDModel extends MySQL_BaseModel
{
    protected $primary = 'id';

    /**
     * Get item by primary key
     *
     * @param int $pk
     * @param array $columns
     * @return array
     */
    public function getRow($pk, $columns = null)
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

        $fields = implode(',', $fields);

        if ($pk) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `{$this->primary}`=:pk";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':pk'   => $pk,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    /**
     * Get a group of items by primary keys
     *
     * @param array $pks
     * @param array $columns
     * @return array
     */
    public function getRows($pks, $columns = null)
    {
        $result = $rowset = array();
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

        if ($pks) {
            $placeHolders = implode(',', array_fill(0, count($pks), '?'));

            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `{$this->primary}` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($pks);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rowset[$row[$this->primary]] = $row;
                }
            }

            foreach ($pks as $pk) {
                if (array_key_exists($pk, $rowset)) {
//                    $result[$pk] = $rowset[$pk];
                    $result[] = $rowset[$pk];
                }
            }
        }

        return $result;
    }

    public function insert($data)
    {
        $result = false;
        $fields = $values = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        if ($fields) {
            $sql = "INSERT INTO `{$this->schema}`.`{$this->table}` ({$fields}) VALUES({$values})";
            if ($this->db->exec($sql)) {
                $result = isset($data[$this->primary]) ? $data[$this->primary] : $this->db->lastInsertId();
            }
        }

        return $result;
    }

    public function update($pk, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data[$this->primary]);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET {$set} WHERE `{$this->primary}`=:pk";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':pk'   => $pk,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function replace($data)
    {
        $result = false;
        $fields = $values = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        if ($fields) {
            $sql = "REPLACE INTO `{$this->schema}`.`{$this->table}` ({$fields}) VALUES({$values})";
            $this->db->exec($sql);

            $result = $this->db->lastInsertId();
        }

        return $result;
    }

    public function delete($pks)
    {
        $result = false;

        if ($pks) {
            $placeHolders = implode(',', array_fill(0, count($pks), '?'));

            $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `{$this->primary}` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($pks);

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`{$this->primary}`) FROM `{$this->schema}`.`{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->schema}`.`{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);

//        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
//            if (isset($row[$this->primary])) {
//                $data[$row[$this->primary]] = $row;
//            } else {
//                $data[] = $row;
//            }
//        }

        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function exists($pk)
    {
        $sql = "SELECT `{$this->primary}` FROM `{$this->schema}`.`{$this->table}` WHERE `{$this->primary}`=:pk";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':pk'   => $pk,
        ));

        return $stmt->fetch(PDO::FETCH_COLUMN, 0) ? true : false;
    }
}