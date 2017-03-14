<?php
class MySQL_Gift_GrowthSchemeModel extends MySQL_BaseIDModel
{
    protected $table = 'gift_growth_scheme';

    protected $fields = array(
        'id',
        'title',
        'level',
        'points',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'level',
        'points',
        'created_on',
    );

    public function getRowByLevel($level, $columns = null)
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

        if ($level) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `level`=:level";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':level'   => $level,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function getLevelPointsMap()
    {
        $result = array();

        $sql = "SELECT `level`, `points` FROM `{$this->schema}`.`{$this->table}` ORDER BY `level` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['level']] = $row['points'];
        }

        return $result;
    }
}