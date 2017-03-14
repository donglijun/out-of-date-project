<?php
class MySQL_Voice_ServerModel extends MySQL_BaseIDModel
{
    const ACTIVE_WINDOW = 120;

    protected $table = 'server';

    protected $fields = array(
        'id',
        'name',
        'ip',
        'port',
        'weight',
        'description',
        'created_on',
        'updated_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'ip',
        'port',
        'weight',
        'description',
        'created_on',
        'updated_on',
    );

    public function heartbeat($data)
    {
        $result = false;
        $fields = $values = array();

        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `ip`=:ip AND `port`=:port";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':ip'   => $data['ip'],
            ':port' => $data['port'],
        ));

        if ($id = $stmt->fetchColumn()) {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `updated_on`=UNIX_TIMESTAMP() WHERE `id`=:id";
            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute(array(
                ':id'   => $id,
            ));
        } else {
            foreach ($data as $key => $val) {
                if (in_array($key, $this->fields)) {
                    $fields[] = $this->quoteIdentifier($key);
                    $values[] = $this->db->quote($val);
                }
            }

            $fields = implode(',', $fields);
            $values = implode(',', $values);

            if ($fields) {
                $sql = "INSERT INTO `{$this->schema}`.`{$this->table}` ({$fields}) VALUES({$values})";
                $stmt = $this->db->prepare($sql);

                $stmt->execute(array(
                    ':ip'   => $data['ip'],
                    ':port' => $data['port'],
                ));

                $result = $this->db->lastInsertId();
            }
        }

        return $result;
    }

    public function getActiveServers()
    {
        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE UNIX_TIMESTAMP() - `updated_on`<:active_window";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array(
            ':active_window'    => static::ACTIVE_WINDOW,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validate($ip, $port)
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `ip`=:ip AND `port`=:port AND UNIX_TIMESTAMP() - `updated_on`<:active_window";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array(
            ':ip'               => $ip,
            ':port'             => $port,
            ':active_window'    => static::ACTIVE_WINDOW,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ? true : false;
    }
}