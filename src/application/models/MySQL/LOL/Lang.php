<?php
class MySQL_LOL_LangModel extends MySQL_BaseIDModel
{
    protected $table = 'lang';

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

    public function getLangMap()
    {
        $result = array();

        $sql = "SELECT `id`, `name`, `abbr` FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function getLangEnum()
    {
        $sql = "SELECT `name` FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}