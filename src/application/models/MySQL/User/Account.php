<?php
class MySQL_User_AccountModel extends MySQL_BaseIDModel
{
    protected $table = 'account';

    protected $fields = array(
        'id',
        'name',
        'password',
        'old_password',
        'status',
        'ban_until',
        'freeze_until',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'status',
        'ban_until',
        'freeze_until',
    );

    public function getUnique($v, $k = 'name')
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

    public function getIdByName($name)
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `name`=:name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':name' => $name,
        ));

        return $stmt->fetchColumn();
    }

    public function upgradePassword($user, $rawPassword)
    {
        $input_parameters = array(
            ':id'       => $user,
            ':password' => password_hash($rawPassword, PASSWORD_DEFAULT),
        );

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `password`=:password WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($input_parameters);
    }

    public function password($plainPassword)
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function activate($user)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=1 WHERE `id`=:id AND `status`=0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $user,
        ));

        return $stmt->rowCount();
    }

    /**
     * Set banned status
     *
     * @param string $ids A group of user ids
     * @param int $days
     * @return mixed
     */
    public function ban($ids, $days = 14)
    {
        $until = strtotime(sprintf('+%d day', (int) $days));

        $ids = is_string($ids) ? array($ids) : $ids;
        $placeHolders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `ban_until`={$until} WHERE `id` IN ({$placeHolders})";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
    }
}