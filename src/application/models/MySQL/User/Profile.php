<?php
class MySQL_User_ProfileModel extends MySQL_BaseIDModel
{
    protected $table = 'profile';

    protected $primary = 'user';

    protected $fields = array(
        'user',
        'email',
        'avatar',
        'nickname',
        'gender',
        'birthday',
        'lang',
        'country',
        'registered_ip',
        'registered_on',
    );

    protected $defaultFields = array(
        'user',
        'email',
        'avatar',
        'nickname',
        'gender',
        'birthday',
        'lang',
        'country',
        'registered_ip',
        'registered_on',
    );

    public function getUsersByEmail($email)
    {
        $sql = "SELECT `user` FROM `{$this->schema}`.`{$this->table}` WHERE `email`=:email";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array(
            ':email'    => $email,
        ));

        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        return $result;
    }

    public function getRegistrationCount($from, $to)
    {
        $result = 0;
        $where = array();

        if ($from) {
            $where[] = "`registered_on` >= {$from}";
        }

        if ($to) {
            $where[] = "`registered_on` < {$to}";
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM `{$this->schema}`.`{$this->table}` WHERE {$where}";
        $stmt = $this->db->query($sql);
        $result = (int) $stmt->fetchColumn();

        return $result;
    }

    public function getTotalCount()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    public function getTodayTotal()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->schema}`.`{$this->table}` WHERE `registered_on`>=:today";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':today' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
        ));

        return (int) $stmt->fetchColumn();
    }
}