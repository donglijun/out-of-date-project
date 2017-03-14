<?php
class MySQL_LOL_MapModel extends MySQL_BaseIDModel
{
    protected $table = 'map';

    protected $fields = array(
        'id',
        'name',
        'alias',
        'abbr',
        'description',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'alias',
        'abbr',
    );

    public function getMapMap()
    {
        $result = array();

        $sql = "SELECT `id`, `name`, `alias`, `abbr` FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }
}