<?php
class MySQL_Gift_Report_ChannelDailyModel extends MySQL_BaseModel
{
    protected $table = 'gift_report_channel_daily';

    protected $fields = array(
        'date',
        'channel',
        'receiving',
        'updated_on',
    );

    protected $defaultFields = array(
        'date',
        'channel',
        'receiving',
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
            if ($this->db->exec($sql)) {
                $result = isset($data[$this->primary]) ? $data[$this->primary] : $this->db->lastInsertId();
            }
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
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`{$this->primary}`) FROM `{$this->schema}`.`{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->schema}`.`{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);

        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function between($from, $to, $channel = null)
    {
        $where = array();

        $where[] = '`date`>=' . (int) $from;
        $where[] = '`date`<' . (int) $to;
        if ($channel) {
            $where[] = '`channel`=' . (int) $channel;
        }
        $where = implode(' AND ', $where);

        $sql = "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY `date` ASC, `receiving` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':from'    => $from,
            ':to'      => $to,
            ':channel' => $channel,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}