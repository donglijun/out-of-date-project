<?php
class MySQL_LOL_PlatformModel extends MySQL_BaseIDModel
{
    protected $table = 'platform';

    protected $fields = array(
        'id',
        'region',
        'name',
        'abbr',
        'description',
    );

    protected $defaultFields = array(
        'id',
        'region',
        'name',
        'abbr',
    );

    public function getPlatformMap()
    {
        $result = array();

        $sql = "SELECT `id`, `region`, `name`, `abbr` FROM `{$this->schema}`.`{$this->table}` ORDER BY `abbr` ASC";

        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function getAvailablePlatforms()
    {
        $sql = "SELECT `abbr` FROM `{$this->schema}`.`{$this->table}` ORDER BY `abbr` ASC";

        $stmt = $this->db->query($sql);


        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}