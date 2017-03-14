<?php
class MySQL_RecommendedModel extends MySQL_BaseModel
{
    protected $table = 'recommended';

    protected $fields = array(
        'id',
        'lang',
        'class',
        'category',
        'deck',
        'ranking',
        'summary',
        'created_on',
        'modified_on',
    );

    protected $defaultFields = array(
        'id',
        'lang',
        'class',
        'category',
        'deck',
        'ranking',
        'summary',
    );

    public function getRow($id, $columns = null)
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

        if ($id) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id'   => $id,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function delete($ids)
    {
        $result = false;

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            try {
                $this->db->beginTransaction();

                $sql = "DELETE FROM `{$this->table}` WHERE `id` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                $this->db->commit();

                $result = $stmt->rowCount();
            } catch (Exception $e) {
                $this->db->rollBack();
                // @todo have a log
            }
        }

        return $result;
    }

    public function insert($data)
    {
        $result = false;
        $fields = $values = array();

        // These fields should not be inserted
        unset($data['id']);

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        if ($fields) {
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES({$values})";
            $this->db->exec($sql);

            $result = $this->db->lastInsertId();
        }

        return $result;
    }

    public function update($id, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['id']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->table}` SET {$set} WHERE `id`=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':id'   => $id,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = $decks = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`id`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['id'])) {
                $data[$row['id']] = $row;
            } else {
                $data[] = $row;
            }

            $decks[] = $row['deck'];
        }

        // Get deck title and user
        $decks = MySQL_DeckModel::getModel($this->getDb())->getRows($decks, array(
            'title',
            'user',
        ));
        foreach ($data as $key => $val) {
            if (array_key_exists($val['deck'], $decks)) {
                $data[$key]['title'] = $decks[$val['deck']]['title'];
                $data[$key]['user'] = $decks[$val['deck']]['user'];
            } else {
                $data[$key]['title'] = '';
                $data[$key]['user'] = 0;
            }
        }

        $result['data'] = $data;

        return $result;
    }

    public function getTotalCount()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    public function getTodayTotal()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `created_on`>=:created_on";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':created_on'   => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
        ));

        return (int) $stmt->fetchColumn();
    }
}