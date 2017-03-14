<?php
class MySQL_Video_LinkVoteHistoryModel extends MySQL_BaseModel
{
    protected $table = 'link_vote_history';

    protected $fields = array(
        'link',
        'user',
        'score',
        'updated_on',
    );

    protected $defaultFields = array(
        'link',
        'user',
        'score',
        'updated_on',
    );

    public function replace($data)
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
            $sql = "REPLACE INTO `{$this->schema}`.`{$this->table}` ({$fields}) VALUES({$values})";
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

    public function getHistory($link)
    {
        $sql = "SELECT `user`,`score` FROM `{$this->schema}`.`{$this->table}` WHERE `link`=:link";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':link' => $link,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}