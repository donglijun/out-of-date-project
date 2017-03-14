<?php
class MySQL_Red_LogModel extends MySQL_BaseIDModel
{
    protected $table = 'red_log';

    protected $fields = array(
        'id',
        'red',
        'user',
        'name',
        'points',
        'title',
        'memo',
        'dealt_on',
    );

    protected $defaultFields = array(
        'id',
        'red',
        'user',
        'name',
        'points',
        'title',
        'memo',
        'dealt_on',
    );

    public function checkOpen($red, $user)
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `red`=:red AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':red'  => $red,
            ':user' => $user,
        ));

        return $stmt->fetchColumn(0) ? true : false;
    }

    public function members($red)
    {
        $sql = "SELECT `id`,`user`,`name`,`points`,`title`,`memo`,`dealt_on` FROM `{$this->schema}`.`{$this->table}` WHERE `red`=:red ORDER BY `id` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':red'  => $red,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}