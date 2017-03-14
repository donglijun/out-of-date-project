<?php
class MySQL_Mkjogo_BadwordModel extends MySQL_BaseIDModel
{
    protected $table = 'badword';

    protected $fields = array(
        'id',
        'content',
    );

    protected $defaultFields = array(
        'id',
        'content',
    );

    public function getAllWords()
    {
        $sql = "SELECT `content` FROM `{$this->schema}`.`{$this->table}` ORDER BY `content` ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}