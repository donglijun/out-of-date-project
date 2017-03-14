<?php
class MySQL_User_ForgotUsernameLogModel extends MySQL_BaseIDModel
{
    const MAX_RESET_TIMES = 5;

    protected $table = 'forgot_username_log';

    protected $fields = array(
        'id',
        'email',
        'created_on',
        'send_times',
    );

    protected $defaultFields = array(
        'id',
        'email',
        'created_on',
        'send_times',
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
}