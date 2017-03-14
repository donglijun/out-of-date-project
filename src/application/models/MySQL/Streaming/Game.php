<?php
class MySQL_Streaming_GameModel extends MySQL_BaseIDModel
{
    protected $table = 'game';

    protected $fields = array(
        'id',
        'name',
        'abbr',
        'icon',
        'logo',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'abbr',
        'icon',
        'logo',
    );

    public function getGameMap()
    {
        $result = array();

        $sql = "SELECT `id`, `name`, `icon`, `logo` FROM `{$this->table}` ORDER BY `name` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}