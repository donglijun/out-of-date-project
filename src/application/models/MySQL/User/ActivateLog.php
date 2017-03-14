<?php
class MySQL_User_ActivateLogModel extends MySQL_BaseIDModel
{
    protected $table = 'activate_log';

    protected $fields = array(
        'id',
        'user',
        'email',
        'code',
        'created_on',
        'status',
        'passed_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'email',
        'code',
        'created_on',
        'status',
        'passed_on',
    );

    public function getUserByCode($code)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `code`=:code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':code' => $code,
        ));

        return $stmt->fetchColumn();
    }

    public function pass($code)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=1, `passed_on`=UNIX_TIMESTAMP() WHERE `code`=:code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':code' => $code,
        ));

        return $stmt->rowCount();
    }
}