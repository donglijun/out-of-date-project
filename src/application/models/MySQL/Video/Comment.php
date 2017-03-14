<?php
class MySQL_Video_CommentModel extends MySQL_BaseIDModel
{
    protected $table = 'comment';

    protected $fields = array(
        'id',
        'body',
        'link',
        'author',
        'author_name',
        'ip',
        'ups',
        'downs',
        'hot_point',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'body',
        'link',
        'author',
        'author_name',
        'ip',
        'ups',
        'downs',
        'hot_point',
        'created_on',
    );

    protected $sensitiveFields = array(
        'body',
    );

    public function vote($id, $up)
    {
        $field = $up ? 'ups' : 'downs';
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `{$field}`=`{$field}`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function getFirst($link, $columns = null)
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

        $fields = implode(',', $fields);

        if ($link) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `link`=:link ORDER BY `created_on` ASC LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':link' => $link,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
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

    public function getSensitiveFields()
    {
        return $this->sensitiveFields;
    }
}