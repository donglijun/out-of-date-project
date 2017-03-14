<?php
class MySQL_LOL_RegionModel extends MySQL_BaseIDModel
{
    protected $table = 'region';

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

    public function getRegionMap()
    {
        $result = array();

        $sql = "SELECT `id`, `name`, `abbr` FROM `{$this->schema}`.`{$this->table}` ORDER BY `abbr` ASC";

        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }
}