<?php
class MySQL_DeckModel extends MySQL_BaseModel
{
    protected $table = 'deck';

    protected $extraTable = 'deck_extra';

    protected $fields = array(
        'id',
        'user',
        'title',
        'created_on',
        'modified_on',
        'game_version',
        'category',
        'class',
        'distribution',
        'ncards',
        'lang',
        'checksum',
        'is_public',
        'favorites',
        'comments',
        'views',
        'source',
        'source_url',
        'author',
    );

    protected $extraFields = array(
        'deck',
        'cards',
        'description',
        'distr_rarity',
        'distr_type',
        'distr_cost',
        'distr_attack',
        'distr_health',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'title',
        'created_on',
        'modified_on',
        'game_version',
        'category',
        'class',
        'distribution',
        'ncards',
        'lang',
        'checksum',
        'is_public',
        'favorites',
        'comments',
        'views',
        'source',
        'source_url',
        'author',
        'distr_rarity',
        'distr_type',
        'distr_cost',
        'distr_attack',
        'distr_health',
    );

    /**
     * Get all fields in deck and deck_extra
     *
     * @return array
     */
    public function getAllFields()
    {
        return array_merge($this->fields, $this->extraFields);
    }

    /**
     * Increment views field
     *
     * @param $id
     */
    public function view($id)
    {
        $sql = "UPDATE `{$this->table}` SET `views`=`views`+1 WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    /**
     * Increment/Decrement favorites field
     *
     * @param array $ids A group of deck ids
     * @param bool $plus True for increment, false for decrement
     * @return mixed
     */
    public function favorite($ids, $plus = true)
    {
        $operator = $plus ? '+' : '-';

        $ids = is_string($ids) ? array($ids) : $ids;
        $placeHolders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE `{$this->table}` SET `favorites`=`favorites`{$operator}1 WHERE `id` IN ({$placeHolders})";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($ids);
    }

    /**
     * Get deck information by deck id
     *
     * @param int $id
     * @param array $columns Fields need to be returned
     * @return array
     */
    public function getRow($id, $columns = null)
    {
        $result = array();
        $fields = $extraFields = $data = $extraData = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            } else if (in_array($column, $this->extraFields)) {
                $extraFields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);
        $extraFields = implode(',', $extraFields);

        if ($id) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id'   => $id,
                ));

                if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($data['distribution'])) {
                        $data['distribution'] = json_decode($data['distribution'], true);
                    }
                } else {
                    $data = array();
                }
            }

            if ($extraFields) {
                $sql = "SELECT {$extraFields} FROM {$this->extraTable} WHERE `deck`=:deck";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':deck' => $id,
                ));

                if ($extraData = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($extraData['cards'])) {
                        $extraData['cards'] = json_decode($extraData['cards'], true);
                    }

                    if (isset($extraData['distr_rarity'])) {
                        $extraData['distr_rarity'] = json_decode($extraData['distr_rarity'], true);
                    }

                    if (isset($extraData['distr_type'])) {
                        $extraData['distr_type'] = json_decode($extraData['distr_type'], true);
                    }

                    if (isset($extraData['distr_cost'])) {
                        $extraData['distr_cost'] = json_decode($extraData['distr_cost'], true);
                    }

                    if (isset($extraData['distr_attack'])) {
                        $extraData['distr_attack'] = json_decode($extraData['distr_attack'], true);
                    }

                    if (isset($extraData['distr_health'])) {
                        $extraData['distr_health'] = json_decode($extraData['distr_health'], true);
                    }
                } else {
                    $extraData = array();
                }
            }

            $result = array_merge($data, $extraData);
        }

        // Retrieve comment count from comment service
        if (isset($result['comments'])) {
            $comments = (int) current(Mkjogo_Comment::getCount($id));

            if ($comments && ($comments != $result['comments'])) {
                $result['comments'] = $comments;

                $this->update($id, array(
                    'comments' => $comments,
                ));
            }
        }

        return $result;
    }

    /**
     * Get a group of decks' information by ids
     *
     * @param array $ids
     * @param array $columns
     * @return array
     */
    public function getRows($ids, $columns = null)
    {
        $result = $rowset = $comments = array();
        $fields = $extraFields = $data = $extraData = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            } else if (in_array($column, $this->extraFields)) {
                $extraFields[] = $this->quoteIdentifier($column);
            }
        }

        if ($fields && !isset($fields['id'])) {
            $fields[] = 'id';
        }
        $fields = implode(',', $fields);

        if ($extraFields) {
            $extraFields[] = 'deck';
        }
        $extraFields = implode(',', $extraFields);

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->table}` WHERE `id` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (isset($row['distribution'])) {
                        $row['distribution'] = json_decode($row['distribution'], true);
                    }

                    if (isset($row['comments'])) {
                        $comments[] = $row['id'];
                    }

                    $rowset[$row['id']] = $row;
                }
            }

            if ($extraFields) {
                $sql = "SELECT {$extraFields} FROM `{$this->extraTable}` WHERE `deck` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (isset($row['cards'])) {
                        $row['cards'] = json_decode($row['cards'], true);
                    }

                    if (isset($row['distr_rarity'])) {
                        $row['distr_rarity'] = json_decode($row['distr_rarity'], true);
                    }

                    if (isset($row['distr_type'])) {
                        $row['distr_type'] = json_decode($row['distr_type'], true);
                    }

                    if (isset($row['distr_cost'])) {
                        $row['distr_cost'] = json_decode($row['distr_cost'], true);
                    }

                    if (isset($row['distr_attack'])) {
                        $row['distr_attack'] = json_decode($row['distr_attack'], true);
                    }

                    if (isset($row['distr_health'])) {
                        $row['distr_health'] = json_decode($row['distr_health'], true);
                    }

                    if (isset($rowset[$row['deck']])) {
                        $rowset[$row['deck']] = array_merge($rowset[$row['deck']], $row);
                    } else {
                        $rowset[$row['deck']] = $row;
                    }
                }
            }

            // Retrieve comments count from comment service
            if ($comments) {
                $comments = Mkjogo_Comment::getCount($comments);

                foreach ($rowset as $key => $val) {
                    if (isset($comments[$key]) && $comments[$key] != $val['comments']) {
                        $rowset[$key]['comments'] = $comments[$key];

                        $this->update($key, array(
                            'comments' => $comments[$key],
                        ));
                    }
                }
            }

            foreach ($ids as $id) {
                if (array_key_exists($id, $rowset)) {
                    $result[$id] = $rowset[$id];
                }
            }
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
    public function old_search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`id`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['distribution'])) {
                $row['distribution'] = json_decode($row['distribution'], true);
            }

            if (isset($row['id'])) {
                $data[$row['id']] = $row;
            } else {
                $data[] = $row;
            }
        }
        $result['data'] = $data;

        return $result;
    }

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = $ids = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`id`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT `id` FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = $row['id'];
        }

        $result['data'] = $this->getRows($ids);

        return $result;
    }

    /**
     * Delete decks by ids
     *
     * @param array $ids
     * @return bool
     */
    public function delete($ids)
    {
        $result = false;

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            try {
                $this->db->beginTransaction();

                $this->favorite($ids, false);

                $sql = "DELETE FROM `{$this->extraTable}` WHERE `deck` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                $sql = "DELETE FROM `{$this->table}` WHERE `id` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($ids);

                $this->db->commit();

                $result = $stmt->rowCount();
            } catch (Exception $e) {
                $this->db->rollBack();
                // @todo have a log
            }
        }

        return $result;
    }

    /**
     * Insert a new deck
     *
     * @param array $data
     * @return bool
     */
    public function insert($data)
    {
        $result = false;
        $fields = $values = $extraFields = $extraValues = array();

        // These fields should not be inserted
        unset($data['id']);

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = $this->db->quote($val);
            } else if (in_array($key, $this->extraFields)) {
                $extraFields[] = $this->quoteIdentifier($key);
                $extraValues[] = $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        if ($fields) {
            try {
                $this->db->beginTransaction();

                $sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES({$values})";
                $this->db->exec($sql);
                $deckid = $this->db->lastInsertId();

                $extraFields[] = 'deck';
                $extraValues[] = $deckid;
                $extraFields = implode(',', $extraFields);
                $extraValues = implode(',', $extraValues);
                $sql = "INSERT INTO `{$this->extraTable}` ({$extraFields}) VALUES({$extraValues})";
                $this->db->exec($sql);

                $this->db->commit();

                $result = $deckid;
            } catch (Exception $e) {
                $this->db->rollBack();
                //@todo have a log
            }
        }

        return $result;
    }

    /**
     * Update a deck by id
     *
     * @param int $id
     * @param array $data
     * @return bool|mixed
     */
    public function update($id, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['id']);
        unset($data['deck']);

        $set = $where = $extraSet = $extraWhere = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            } else if (in_array($key, $this->extraFields)) {
                $extraSet[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);
        $extraSet = implode(',', $extraSet);

        // Don't check ownership here, check it outside
        $affectedCount = $affectedCountExtra = 0;
        try {
            $this->db->beginTransaction();

            if ($set) {
                $sql = "UPDATE `{$this->table}` SET {$set} WHERE `id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id'   => $id,
                ));
                $affectedCount = $stmt->rowCount();
            }

            if ($extraSet) {
                $sql = "UPDATE `{$this->extraTable}` SET {$extraSet} WHERE `deck`=:deck";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':deck' => $id,
                ));
                $affectedCountExtra = $stmt->rowCount();
            }

            $this->db->commit();

            $result = max($affectedCount, $affectedCountExtra);
        } catch (Exception $e) {
            $this->db->rollBack();
            //@todo have a log
        }

        return $result;
    }

    /**
     * Check whether deck owned by user
     *
     * @param int $user
     * @param int $id
     * @return bool
     */
    public function checkOwnership($user, $id)
    {
        $stmt = $this->db->prepare("SELECT `user` FROM `{$this->table}` WHERE `id`=:id AND `user`=:user");
        $stmt->execute(array(
            ':id'   => $id,
            ':user' => $user,
        ));

        return $stmt->rowCount() > 0;
    }

    /**
     * Validate a group of decks which owned by user
     *
     * @param int $user
     * @param array $ids
     * @return array
     */
    public function validateOwnership($user, $ids)
    {
        $result = $placeHolders = $inputParams = array();

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));
            $inputParams = $ids;
            $inputParams[] = $user;

            $stmt = $this->db->prepare("SELECT `id` FROM `{$this->table}` WHERE `id` IN ({$placeHolders}) AND `user`=?");
            $stmt->execute($inputParams);

            foreach ($stmt->fetchALL(PDO::FETCH_ASSOC) as $row) {
                $result[] = $row['id'];
            }
        }

        return $result;
    }

    /**
     * Get all deck classes
     *
     * @return array
     */
    public function getClassMap()
    {
        return array(
            'druid'     => 'Druid',
            'hunter'    => 'Hunter',
            'mage'      => 'Mage',
            'paladin'   => 'Paladin',
            'priest'    => 'Priest',
            'rogue'     => 'Rogue',
            'shaman'    => 'Shaman',
            'warlock'   => 'Warlock',
            'warrior'   => 'Warrior',
        );
    }

    /**
     * Get all supported deck langs
     *
     * @return array
     */
    public function getLangMap()
    {
        return array(
            'zh_CN' => 'zh_CN',
            'zh_TW' => 'zh_TW',
            'en_US' => 'en_US',
        );
    }

    public function getTotalCount()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    public function getTodayTotal()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `created_on`>=:created_on";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':created_on'   => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
        ));

        return (int) $stmt->fetchColumn();
    }

    public function calcDistribution($cards)
    {
        $summary = $detail = array();
        $distr_rarity = $distr_type = $distr_cost = $distr_attack = $distr_health = array();

        foreach ($cards as $info) {
            $summary[$info['i']] = array(
                'count' => (int) $info['c'],
            );
        }

        $cardModel = new MySQL_CardModel($this->db);
        $detail = $cardModel->getRowsByCardID(array_keys($summary));

        foreach ($summary as $key => $val) {
            $val = array_merge($val, $detail[$key]);

            $distr_rarity[$val['rarity']]   = isset($distr_rarity[$val['rarity']]) ? $distr_rarity[$val['rarity']] + $val['count'] : $val['count'];

            $distr_type[$val['type']]       = isset($distr_type[$val['type']]) ? $distr_type[$val['type']] + $val['count'] : $val['count'];

            $distr_cost[$val['cost']]       = isset($distr_cost[$val['cost']]) ? $distr_cost[$val['cost']] + $val['count'] : $val['count'];

            $distr_attack[$val['attack']]   = isset($distr_attack[$val['attack']]) ? $distr_attack[$val['attack']] + $val['count'] : $val['count'];

            $distr_health[$val['health']]   = isset($distr_health[$val['health']]) ? $distr_health[$val['health']] + $val['count'] : $val['count'];
        }

        ksort($distr_rarity, SORT_NUMERIC);
        ksort($distr_type, SORT_NUMERIC);
        ksort($distr_cost, SORT_NUMERIC);
        ksort($distr_attack, SORT_NUMERIC);
        ksort($distr_health, SORT_NUMERIC);

        return array(
            'distr_rarity'  => $distr_rarity,
            'distr_type'    => $distr_type,
            'distr_cost'    => $distr_cost,
            'distr_attack'  => $distr_attack,
            'distr_health'  => $distr_health,
        );
    }
}