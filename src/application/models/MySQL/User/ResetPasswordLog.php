<?php
class MySQL_User_ResetPasswordLogModel extends MySQL_BaseIDModel
{
    const MAX_RESET_TIMES = 5;

    protected $table = 'reset_password_log';

    protected $fields = array(
        'id',
        'user',
        'name',
        'email',
        'code',
        'created_on',
        'send_times',
//        'status',
//        'passed_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'name',
        'email',
        'code',
        'created_on',
        'send_times',
//        'status',
//        'passed_on',
    );

    public function getUnique($v, $k)
    {
        $result = array();

        if (in_array($k, $this->fields)) {
            $k = $this->quoteIdentifier($k);

            $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE {$k}=:v";
            $stmt = $this->db->prepare($sql);

            $stmt->execute(array(
                ':v'    => $v,
            ));

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function send($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `send_times`=`send_times`+1 WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $id,
        ));

        return $stmt->rowCount();
    }

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