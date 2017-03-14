<?php
class MySQL_AdminAccountModel extends MySQL_BaseModel
{
    const GROUP_SUPER_ADMIN = 1;

    const GROUP_ADMIN = 2;

    const GROUP_ASSISTANT_ADMIN = 4;

    const GROUP_DIRECTOR = 8;

    protected $table = 'admin_account';

    protected $fields = array(
        'user',
        'name',
        'email',
        'created_on',
        'created_by',
        'last_login_on',
        'last_login_ip',
        'is_immovable',
        'group',
    );

    protected $defaultFields = array(
        'user',
        'name',
        'email',
        'created_on',
        'created_by',
        'last_login_on',
        'last_login_ip',
        'group',
    );

    public function getRow($user, $columns = null)
    {
        $result = array();
        $fields = $data = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        if ($user) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `user`=:user";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':user' => $user,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function delete($users)
    {
        $result = false;

        if ($users) {
            $placeHolders = implode(',', array_fill(0, count($users), '?'));

            $sql = "DELETE FROM `{$this->table}` WHERE `user` IN ({$placeHolders}) AND `is_immovable`=0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($users);

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function insert($data)
    {
        $result = false;
        $fields = $values = array();

        // These fields should not be inserted

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        if ($fields) {
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES({$values})";
            $this->db->exec($sql);

            $result = true;
        }

        return $result;
    }

    public function update($user, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['user']);
        unset($data['is_immovable']);
        unset($data['created_on']);
        unset($data['created_by']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->table}` SET {$set} WHERE `user`=:user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':user'   => $user,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`user`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['user'])) {
                $data[$row['user']] = $row;
            } else {
                $data[] = $row;
            }
        }
        $result['data'] = $data;

        return $result;
    }

    public function authenticate($user)
    {
        $user = $this->getRow($user, array('user'));

        return !empty($user);
    }

    public function login($user, $ip)
    {
        $data = array(
            'last_login_on' => time(),
            'last_login_ip' => $ip,
        );

        return $this->update($user, $data);
    }

    public function getGroupMap()
    {
        return array(
            self::GROUP_SUPER_ADMIN     => 'SUPER_ADMIN',
            self::GROUP_ADMIN           => 'ADMIN',
            self::GROUP_ASSISTANT_ADMIN => 'ASSISTANT_ADMIN',
            self::GROUP_DIRECTOR        => 'DIRECTOR',
        );
    }
}