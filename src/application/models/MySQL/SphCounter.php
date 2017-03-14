<?php
class MySQL_SphCounterModel extends MySQL_BaseModel
{
    const SLUG_PREFIX_LOL_CHAMPION_PICK_BAN = 'lol-champion-pick-ban-';

    const SLUG_PREFIX_LOL_MATCH             = 'lol-match-';

    const SLUG_PREFIX_LOL_USER              = 'lol-user-';

    const SLUG_PREFIX_LOL_USER_NAME         = 'lol-user-name-';

    const RANGE_STEP = 1000;

    protected $table = 'sph_counter';

    protected $fields = array(
        'index_slug',
        'max_doc_id',
    );

    protected $defaultFields = array(
        'index_slug',
        'max_doc_id',
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
            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES({$values})";
            $result = $this->db->exec($sql);
        }

        return $result;
    }

    public function update($slug, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['index_slug']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->table}` SET {$set} WHERE `index_slug`=:slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':slug' => $slug,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

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
            $sql = "REPLACE INTO {$this->table} ({$fields}) VALUES({$values})";
            $result = $this->db->exec($sql);
        }

        return $result;
    }

    public function getMaxDocId($slug)
    {
        $result = 0;

        if ($slug) {
            $sql = "SELECT `max_doc_id` FROM {$this->table} WHERE `index_slug`=:slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':user' => $slug,
            ));

            $result = (int) $stmt->fetchColumn();
        }

        return $result;
    }
}