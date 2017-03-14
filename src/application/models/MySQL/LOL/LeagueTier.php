<?php
class MySQL_LOL_LeagueTierModel extends MySQL_BaseIDModel
{
    protected $table = 'league_tier';

    protected $fields = array(
        'id',
        'name',
        'abbr',
        'description',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'abbr',
    );

    public function getLeagueTierMap()
    {
        $result = array();

        $sql = "SELECT `id`, `name`, `abbr` FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }
}