<?php
class MySQL_Video_TagModel extends MySQL_BaseIDModel
{
    protected $table = 'tag';

    protected $fields = array(
        'id',
        'name',
        'group',
        'description',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'group',
    );

    public function getIds()
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getIdsByGroup($group)
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `group`=:group";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':group' => $group,
        ));

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getRowsByGroup($group = null, $columns = null)
    {
        $result = array();
        $fields = array();

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

        if ($fields = implode(',', $fields)) {
            if ($group !== null) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `group`=:group ORDER BY `name`";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':group' => $group,
                ));

                if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            } else {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` ORDER BY `name`";
                $stmt = $this->db->query($sql);

                if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function getMap()
    {
        $result = array();

        $sql = "SELECT `id`,`name` FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row['name'];
        }

        return $result;
    }
}