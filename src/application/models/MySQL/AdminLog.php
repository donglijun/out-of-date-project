<?php
class MySQL_AdminLogModel extends MySQL_BaseModel
{
    const OP_LOGIN  = 'login';
    const OP_LOGOUT = 'logout';

    const OP_ADD_ADMIN_ACCOUNT      = 'add-admin-account';
    const OP_REMOVE_ADMIN_ACCOUNT   = 'remove-admin-account';

    const OP_MODIFY_USER    = 'modify-user';
    const OP_MUTE_USER      = 'mute-user';
    const OP_BAN_USER       = 'ban-user';

    const OP_ADD_GAME       = 'add-game';
    const OP_MODIFY_GAME    = 'modify-game';
    const OP_DELETE_GAME    = 'delete-game';

    const OP_RECHARGE_POINT = 'recharge-point';

    const OP_RECHARGE_GOLD = 'recharge-gold';

    protected $table = 'admin_log';

    protected $fields = array(
        'id',
        'user',
        'action',
        'content',
        'logged_on',
        'logged_ip',
    );

    public function getRow($id, $columns = null)
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

        if ($id) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id' => $id,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function delete($ids)
    {
        return false;
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

            $result = $this->db->lastInsertId();
        }

        return $result;
    }

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`id`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['id'])) {
                $data[$row['id']] = $row;
            } else {
                $data[] = $row;
            }
        }
        $result['data'] = $data;

        return $result;
    }
}