<?php
class MySQL_Streaming_ChannelClassModel extends MySQL_BaseIDModel
{
    protected $table = 'channel_class';

    protected $fields = array(
        'id',
        'level',
        'title',
        'hourly_pay',
        'exclusive_bonus',
        'withdraw_rate',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'level',
        'title',
        'hourly_pay',
        'exclusive_bonus',
        'withdraw_rate',
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

    public function getDefaultClassID()
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` ORDER BY `level` ASC LIMIT 1";
        $stmt = $this->db->query($sql);

        return $stmt->fetchColumn();
    }

    public function getLevelMap()
    {
        $result = array();

        $sql = "SELECT `id`, `level` FROM `{$this->schema}`.`{$this->table}` ORDER BY `level` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = (int) $row['level'];
        }

        return $result;
    }
}