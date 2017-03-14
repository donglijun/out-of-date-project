<?php
class MySQL_Streaming_BulletModel extends MySQL_BaseIDModel
{
    protected $table = 'bullet';

    protected $fields = array(
        'id',
        'highlight',
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
        'highlight',
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

    public function getRowsByHighlight($highlight, $columns = null)
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

        if ($highlight && $fields) {
            $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `highlight`=:highlight ORDER BY `id` ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':highlight' => $highlight,
            ));

            if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
                $result = array();
            }

        }

        return $result;
    }

    public function deleteByHighlights($highlights)
    {
        $result = false;

        if ($highlights) {
            $placeHolders = implode(',', array_fill(0, count($highlights), '?'));

            $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `highlight` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($highlights);

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