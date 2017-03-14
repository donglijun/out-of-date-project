<?php
class MySQL_Report_LOL_Champion_BaseModel extends MySQL_BaseModel
{
    protected $fields = array(
        'date',
        'champion',
        'mode',
        'total',
        'win',
        'win_rate',
        'lose',
        'ranked_total',
        'ranked_pick',
        'ranked_ban',
        'ranked_pick_rate',
        'ranked_ban_rate',
        'updated_on',
    );

    protected $defaultFields = array(
        'date',
        'champion',
        'mode',
        'total',
        'win',
        'win_rate',
        'lose',
        'ranked_total',
        'ranked_pick',
        'ranked_ban',
        'ranked_pick_rate',
        'ranked_ban_rate',
        'updated_on',
    );

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
            $this->db->exec($sql);

            $result = true;
        }

        return $result;
    }

    public function update($date, $champion, $mode, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['date']);
        unset($data['champion']);
        unset($data['mode']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET {$set} WHERE `date`=:date AND `champion`=:champion AND `mode`=:mode";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':date'     => $date,
                ':champion' => $champion,
                ':mode'     => $mode,
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

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = $decks = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`date`) FROM `{$this->schema}`.`{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->schema}`.`{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);

        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function between($from, $to, $champion = null, $mode = null)
    {
        $where = array(
            '`date`>=:from',
            '`date`<:to',
        );

        $parameters = array(
            ':from' => $from,
            ':to'   => $to,
        );

        if ($champion) {
            $where[] = '`champion`=:champion';
            $parameters[':champion'] = $champion;
        }

        if ($mode) {
            $where[] = '`mode`=:mode';
            $parameters[':mode'] = $mode;
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE {$where} ORDER BY `date` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctDates()
    {
        $sql = "SELECT DISTINCT `date` FROM `{$this->schema}`.`{$this->table}` ORDER BY `date` DESC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}