<?php
class MySQL_Connection_FacebookModel extends MySQL_BaseIDModel
{
    protected $table = 'connection_facebook';

    protected $fields = array(
        'id',
        'user',
        'name',
        'foreign_user',
        'data',
        'access_token',
        'expires_in',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'name',
        'foreign_user',
        'access_token',
        'expires_in',
    );

    public function getRowByUser($val, $columns = null)
    {
        $result = array();
        $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns = array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        if ($val && $fields) {
            $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':user' => $val,
            ));

            if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $result = array();
            }
        }

        return $result;
    }

    public function getRowByForeignUser($val, $columns = null)
    {
        $result = array();
        $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns = array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        if ($val && $fields) {
            $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `foreign_user`=:foreign_user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':foreign_user' => $val,
            ));

            if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $result = array();
            }
        }

        return $result;
    }

    public function updateByUser($user, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data[$this->primary]);
        unset($data['user']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET {$set} WHERE `user`=:user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':user' => $user,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }
}