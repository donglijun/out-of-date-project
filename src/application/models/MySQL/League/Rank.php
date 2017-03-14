<?php
class MySQL_League_RankModel extends MySQL_BaseIDModel
{
    protected $table = 'league_rank';

    protected $fields = array(
        'id',
        'season',
        'schedule',
        'group_tag',
        'team',
        'wins',
        'loses',
        'k',
        'd',
        'a',
        'points',
        'rank',
    );

    protected $defaultFields = array(
        'id',
        'season',
        'schedule',
        'group_tag',
        'team',
        'wins',
        'loses',
        'k',
        'd',
        'a',
        'points',
        'rank',
    );

    public function getRowsBySeason($season, $columns = null)
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `season`=:season ORDER BY `id` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':season' => $season,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryRows($season = null, $schedule = null, $columns = null)
    {
        $fields = $where = array();

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

        if ($season) {
            $where[] = '`season`=' . (int) $season;
        }

        if ($schedule) {
            $where[] = '`schedule`=' . (int) $schedule;
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` {$where} ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}