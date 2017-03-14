<?php
class MySQL_Video_BulletModel extends MySQL_BaseIDModel
{
    protected $table = 'bullet';

    protected $fields = array(
        'id',
        'link',
        'body',
        'author',
        'author_name',
        'style',
        'track',
        'ip',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'link',
        'body',
        'author',
        'author_name',
        'style',
        'track',
        'created_on',
    );

    protected $sensitiveFields = array(
        'body',
    );

    public function getRowsByLink($link, $columns = null)
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

        if ($link && $fields) {
            $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `link`=:link ORDER BY `id` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':link' => $link,
            ));

            if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
                $result = array();
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

    public function deleteByAuthor($author)
    {
        $result = false;

        if ($author) {
            $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `author`=:author";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':author'   => $author,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function getSensitiveFields()
    {
        return $this->sensitiveFields;
    }
}