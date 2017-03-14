<?php
class MySQL_ReportBaseModel extends MySQL_BaseModel
{
    protected $fields = array(
        'date',
        'total',
        'increment',
        'growth_rate',
        'updated_on',
    );

    protected $defaultFields = array(
        'date',
        'total',
        'increment',
        'growth_rate',
        'updated_on',
    );

    /**
     * Get a report by date
     *
     * @param int $date
     * @param array $columns
     * @return array
     */
    public function getRow($date, $columns = null)
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

        if ($date) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `date`=:date";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':date'   => $date,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    /**
     * Get a group of report by date
     *
     * @param array $dates
     * @param array $columns
     * @return array
     */
    public function getRows($dates, $columns = null)
    {
        $result = $rowset = array();
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

        if ($fields && !isset($fields['date'])) {
            $fields[] = 'date';
        }
        $fields = implode(',', $fields);

        if ($dates) {
            $placeHolders = implode(',', array_fill(0, count($dates), '?'));

            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->table}` WHERE `date` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($dates);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rowset[$row['date']] = $row;
                }
            }

            foreach ($dates as $date) {
                if (array_key_exists($date, $rowset)) {
                    $result[$date] = $rowset[$date];
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
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES({$values})";
            $this->db->exec($sql);

            $result = true;
        }

        return $result;
    }

    public function update($date, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['date']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->table}` SET {$set} WHERE `date`=:date";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':date'   => $date,
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

        $sql = "SELECT count(`date`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['date'])) {
                $data[$row['date']] = $row;
            } else {
                $data[] = $row;
            }
        }

        $result['data'] = $data;

        return $result;
    }

    public function between($from, $to)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `date`>=:from AND `date`<:to ORDER BY `date` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':from' => $from,
            ':to'   => $to,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY `date` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}