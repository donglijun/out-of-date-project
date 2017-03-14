<?php
class MySQL_Video_LinkModel extends MySQL_BaseIDModel
{
    protected $table = 'link';

    protected $fields = array(
        'id',
        'url',
        'title',
        'text',
        'source',
        'thumbnail_url',
        'custom_image',
        'author',
        'author_name',
        'tags',
        'comments_count',
        'bullets_count',
        'lang',
        'ip',
        'ups',
        'downs',
        'views_count',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'url',
        'title',
        'text',
        'source',
        'thumbnail_url',
        'custom_image',
        'author',
        'author_name',
        'tags',
        'comments_count',
        'bullets_count',
        'lang',
        'ip',
        'ups',
        'downs',
        'views_count',
        'created_on',
    );

    protected $sensitiveFields = array(
        'title',
    );

    public function getRowByUrl($url, $columns = null)
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

        if ($url && $fields) {
            $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `url`=:url";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':url' => $url,
            ));

            if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                $result = array();
            }
        }

        return $result;
    }

    public function vote($id, $up)
    {
        $field = $up ? 'ups' : 'downs';
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `{$field}`=`{$field}`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function view($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `views_count`=`views_count`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function comment($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `comments_count`=`comments_count`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function bullet($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `bullets_count`=`bullets_count`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function listByNew($offset = 0, $limit = 20)
    {
        $result = $where = array();

        if ($offset) {
            $where[] = '`id`<' . $offset;
        }

        $where = $where ? implode(' AND ', $where) : '';

        $result = $this->search('*', $where, '`id` DESC', 0, $limit);

        return $result;
    }

    public function getSensitiveFields()
    {
        return $this->sensitiveFields;
    }
}