<?php
class MySQL_MkjogoUserModel extends MySQL_BaseModel
{
    const BASE_ID = 100000;

    protected $table = 'user';

    protected $detailTable = 'user_detail';

    protected $fields = array(
        'user_id',
        'user_type',
        'group_id',
        'user_ip',
        'user_regdate',
        'username',
        'username_clean',
        'user_password',
        'user_passchg',
        'user_email',
        'user_email_hash',
        'user_birthday',
        'user_lastvisit',
        'user_lang',
        'user_timezone',
        'user_rank',
        'user_avatar',
        'user_avatar_type',
        'user_avatar_width',
        'user_avatar_height',
        'user_from',
        'user_new',
    );

    protected $detailFields = array(
        'nickname',
        'mute_until',
        'ban_until',
        'lang',
    );

    protected $defaultFields = array(
        'user_id',
        'user_type',
        'group_id',
        'user_ip',
        'user_regdate',
        'username',
        'username_clean',
        'user_password',
        'user_email',
        'user_birthday',
        'user_lastvisit',
        'user_lang',
        'user_avatar',
    );

    protected $editableFields = array(
        'user_email',
        'user_password',
        'user_avatar',
        'nickname',
        'mute_until',
        'ban_until',
        'lang',
    );

    /**
     * Get all fields in user and user_detail
     *
     * @return array
     */
    public function getAllFields()
    {
        return array_merge($this->fields, $this->detailFields);
    }

    /**
     * Set mute status
     *
     * @param string $ids A group of user ids
     * @param int $days
     * @return mixed
     */
    public function mute($ids, $days = 14)
    {
        $until = strtotime(sprintf('+%d day', (int) $days));

        /**
         * Make sure user has detail
         */
        foreach ($ids as $id) {
            if (!$this->checkDetail($id)) {
                $this->insertDetail(array(
                    'user_id'   => $id,
                ));
            }
        }

        $ids = is_string($ids) ? array($ids) : $ids;
        $placeHolders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE `{$this->detailTable}` SET `mute_until`={$until} WHERE `user_id` IN ({$placeHolders})";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
    }

    /**
     * Set banned status
     *
     * @param string $ids A group of user ids
     * @param int $days
     * @return mixed
     */
    public function ban($ids, $days = 14)
    {
        $until = strtotime(sprintf('+%d day', (int) $days));

        /**
         * Make sure user has detail
         */
        foreach ($ids as $id) {
            if (!$this->checkDetail($id)) {
                $this->insertDetail(array(
                    'user_id'   => $id,
                ));
            }
        }

        $ids = is_string($ids) ? array($ids) : $ids;
        $placeHolders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE `{$this->detailTable}` SET `ban_until`={$until} WHERE `user_id` IN ({$placeHolders})";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
    }

    /**
     * Get user information by user id
     *
     * @param int $id
     * @param array $columns Fields need to be returned
     * @return array
     */
    public function getRow($id, $columns = null)
    {
        $result = array();
        $fields = $detailFields = $data = $detailData = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            } else if (in_array($column, $this->detailFields)) {
                $detailFields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);
        $detailFields = implode(',', $detailFields);

        if ($id) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `user_id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id' => $id,
                ));

                if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $data = array();
                }
            }

            if ($detailFields) {
                $sql = "SELECT {$detailFields} FROM {$this->detailTable} WHERE `user_id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id' => $id,
                ));

                if (!($detailData = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $detailData = array();
                }
            }

            $result = array_merge($data, $detailData);
        }

        return $result;
    }

    /**
     * Get a group of users' information by ids
     *
     * @param array $ids
     * @param array $columns
     * @return array
     */
    public function getRows($ids, $columns = null)
    {
        $result = $rowset = array();
        $fields = $detailFields = $data = $detailData = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            } else if (in_array($column, $this->detailFields)) {
                $detailFields[] = $this->quoteIdentifier($column);
            }
        }

        if ($fields && !isset($fields['user_id'])) {
            $fields[] = $this->quoteIdentifier('user_id');
        }
        $fields = implode(',', $fields);

        if ($detailFields) {
            $detailFields[] = $this->quoteIdentifier('user_id');
        }
        $detailFields = implode(',', $detailFields);

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->table}` WHERE `user_id` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rowset[$row['user_id']] = $row;
                }
            }

            if ($detailFields) {
                $sql = "SELECT {$detailFields} FROM `{$this->detailTable}` WHERE `user_id` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (isset($rowset[$row['user_id']])) {
                        $rowset[$row['user_id']] = array_merge($rowset[$row['user_id']], $row);
                    } else {
                        $rowset[$row['user_id']] = $row;
                    }
                }
            }

            foreach ($ids as $id) {
                if (array_key_exists($id, $rowset)) {
//                    $result[$id] = $rowset[$id];
                    $result[] = $rowset[$id];
                }
            }
        }

        return $result;
    }

    /**
     * Insert a new user
     *
     * @param array $data
     * @return bool
     */
    public function insertDetail($data)
    {
        $result = false;
        $detailFields = $detailValues = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->detailFields)) {
                $detailFields[] = $this->quoteIdentifier($key);
                $detailValues[] = $this->db->quote($val);
            }
        }

        if (isset($data['user_id'])) {
            $detailFields[] = $this->quoteIdentifier('user_id');
            $detailValues[] = $this->db->quote($data['user_id']);
        }

        if ($detailFields) {
            try {
                $detailFields = implode(',', $detailFields);
                $detailValues = implode(',', $detailValues);

                $sql = "INSERT INTO `{$this->detailTable}` ({$detailFields}) VALUES({$detailValues})";
                $this->db->exec($sql);

                $result = true;
            } catch (Exception $e) {
                //@todo have a log
            }
        }

        return $result;
    }

    /**
     * Update a user by id
     *
     * @param int $id
     * @param array $data
     * @return bool|mixed
     */
    public function update($id, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['user_id']);

        $set = $where = $detailSet = $detailWhere = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            } else if (in_array($key, $this->detailFields)) {
                $detailSet[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);
        $detailSet = implode(',', $detailSet);

        $affectedCount = $affectedCountDetail = 0;
        try {
            $this->db->beginTransaction();

            if ($set) {
                $sql = "UPDATE `{$this->table}` SET {$set} WHERE `user_id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id'   => $id,
                ));
                $affectedCount = $stmt->rowCount();
            }

            if ($detailSet) {
                /**
                 * Make sure user has detail
                 */
                if (!$this->checkDetail($id)) {
                    $this->insertDetail(array(
                        'user_id'   => $id,
                    ));
                }

                $sql = "UPDATE `{$this->detailTable}` SET {$detailSet} WHERE `user_id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id' => $id,
                ));
                $affectedCountDetail = $stmt->rowCount();
            }

            $this->db->commit();

            $result = max($affectedCount, $affectedCountDetail);
        } catch (Exception $e) {
            $this->db->rollBack();
            //@todo have a log
        }

        return $result;
    }

    /**
     * A simple search query
     *
     * @param string $select
     * @param string $where
     * @param string $sort
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`user_id`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['user_id'])) {
                $data[$row['user_id']] = $row;
            } else {
                $data[] = $row;
            }
        }
        $result['data'] = $data;

        return $result;
    }

    /**
     * Check existence of user detail
     *
     * @param int $user
     * @return bool
     */
    public function checkDetail($user)
    {
        $stmt = $this->db->prepare("SELECT `user_id` FROM `{$this->detailTable}` WHERE `user_id`=:user");
        $stmt->execute(array(
            ':user' => $user,
        ));

        return $stmt->rowCount() > 0;
    }

    public function getRegistrationCount($from, $to)
    {
        $result = 0;
        $where = array();

        if ($from) {
            $where[] = "`user_regdate` >= {$from}";
        }

        if ($to) {
            $where[] = "`user_regdate` < {$to}";
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE {$where}";
        $stmt = $this->db->query($sql);
        $result = (int) $stmt->fetchColumn();

        return $result;
    }

    public function getTotalCount()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `user_id`>=:user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user_id'  => self::BASE_ID,
        ));

        return (int) $stmt->fetchColumn();
    }

    public function getTodayTotal()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `user_regdate`>=:user_regdate";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user_regdate' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
        ));

        return (int) $stmt->fetchColumn();
    }

    public function getIdByName($name)
    {
        $sql = "SELECT `user_id` FROM `{$this->table}` WHERE `username`=:username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':username' => $name,
        ));

        return $stmt->fetchColumn();
    }
}