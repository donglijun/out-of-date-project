<?php
class MySQL_Video_LinkShareHistoryModel extends MySQL_BaseModel
{
    protected $table = 'link_share_history';

    protected $fields = array(
        'link',
        'user',
        'created_on',
    );

    protected $defaultFields = array(
        'link',
        'user',
        'created_on',
    );

    public function insert($data)
    {
        $result = false;
        $fields = $values = array();

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
            $result = $this->db->exec($sql);
        }

        return $result;
    }

    public function delete($link, $user)
    {
        $result = false;

        if ($link && $user) {
            $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `link`=:link AND `user`=:user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':link' => $link,
                ':user' => $user,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function deleteByLinks($links)
    {
        $result = false;

        if ($links) {
            $placeHolders = implode(',', array_fill(0, count($links), '?'));

            $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `link` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($links);

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function exists($link, $user)
    {
        $sql = "SELECT `link` FROM `{$this->schema}`.`{$this->table}` WHERE `link`=:link AND `user`=:user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':link' => $link,
            ':user' => $user,
        ));

        return $stmt->fetchColumn() ? true : false;
    }

    public function getUserHistoryByLink($link)
    {
        $sql = "SELECT `user`,`created_on` FROM `{$this->schema}`.`{$this->table}` WHERE `link`=:link ORDER BY `created_on` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':link' => $link,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLinkHistoryByUser($user)
    {
        $sql = "SELECT `link`,`created_on` FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user ORDER BY `created_on` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user' => $user,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}