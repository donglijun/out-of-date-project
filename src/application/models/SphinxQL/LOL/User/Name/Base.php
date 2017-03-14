<?php
class SphinxQL_LOL_User_Name_BaseModel extends SphinxQL_BaseModel
{
    protected $index = 'rt_lol_user_name';

    protected $sort = 'WEIGHT() DESC, name ASC';

    protected $fields = array(
        'id',
        'name',
    );

    public function simpleSearch($query, $offset = 0, $limit = 20, $max_matches = 1000, $sort = null)
    {
        $result = array();

        $sql = <<<EOT
SELECT *
FROM `%s`
WHERE MATCH('%s')
ORDER BY %s
LIMIT %d, %d
OPTION `max_matches` = %d;

SHOW META;
EOT;

        $sql = sprintf($sql, $this->index, $query, $sort ?: $this->sort, $offset, $limit, $max_matches);

        $stmt = $this->db->query($sql);

        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result['matches'][$row['id']] = $row;
            }

            if ($stmt->nextRowset()) {
                $result = array_merge($result, $this->parseMetaRowset($stmt->fetchAll(PDO::FETCH_ASSOC)));
            }

        }

        return $result;
    }

    public function exact($name)
    {
        $sql = "SELECT `id` FROM `{$this->index}` WHERE `name`=:name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':name' => $name,
        ));

        return $stmt->fetchColumn();
    }
}