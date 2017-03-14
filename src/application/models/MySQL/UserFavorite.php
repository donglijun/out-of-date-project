<?php
class MySQL_UserFavoriteModel extends MySQL_BaseModel
{
    protected $table = 'user_favorite';

    protected $fields = array(
        'id',
        'user',
        'deck',
        'deck_owner',
        'added_on',
    );

    /**
     * Delete favorites
     *
     * @param array $ids
     * @return bool
     */
    public function delete($ids)
    {
        $result = false;

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            $sql = "DELETE FROM `{$this->table}` WHERE `id` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);

            $result = $stmt->rowCount();
        }

        return $result;
    }

    /**
     * Insert new favorite relation
     *
     * @param array $data
     * @return bool
     */
    public function insert($data)
    {
        $result = false;
        $fields = $values = array();

        // These fields should not be inserted
        unset($data['id']);

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
            $this->db->exec($sql);

            $result = $this->db->lastInsertId();
        }

        return $result;
    }

    /**
     * Add deck into favorites
     *
     * @param int $user
     * @param int $deck
     * @return bool
     */
    public function add($user, $deck)
    {
        $result = false;

        $deckModel = MySQL_DeckModel::getModel($this->db);
        $deckInfo = $deckModel->getRow($deck, array('user'));

        if ($deckInfo) {
            $deckOwner = $deckInfo['user'];
            $timestamp = time();

            try {
                $this->db->beginTransaction();

                $favoriteId = $this->insert(array(
                    'user'          => $user,
                    'deck'          => $deck,
                    'deck_owner'    => $deckOwner,
                    'added_on'      => $timestamp,
                ));

                $deckModel->favorite($deck);

                $this->db->commit();

                $result = $favoriteId;
            } catch (Excpetion $e) {
                $this->db->rollBack();
                //@todo have a log
            }
        }

        return $result;
    }

    /**
     * Remove a group of favorites by deck id
     *
     * @param int $user
     * @param array $ids
     * @return bool|int
     */
    public function remove($user, $decks)
    {
        $result = false;
        $placeHolders = $inputParams = array();
        $delete = $update = array();

        if ($decks) {
            $placeHolders = implode(',', array_fill(0, count($decks), '?'));
            $inputParams = $decks;
            array_unshift($inputParams, $user);

            $sql = "SELECT `id`,`deck` FROM `{$this->table}` WHERE `user`=? AND `deck` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($inputParams);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $delete[] = $row['id'];
                $update[] = $row['deck'];
            }

            if ($delete) {
                try {
                    $this->db->beginTransaction();

                    $deckModel = MySQL_DeckModel::getModel($this->db);
                    $deckModel->favorite($update, false);

                    $this->delete($delete);

                    $this->db->commit();

                    $result = count($delete);
                } catch (Exception $e) {
                    $this->db->rollback();
                    //@todo have a log
                }
            }
        }

        return $result;
    }

    /**
     * Remove a group of favorites by id
     *
     * @param int $user
     * @param array $ids
     * @return bool|int
     */
    public function removeByID($user, $ids)
    {
        $result = false;
        $placeHolders = $inputParams = array();
        $delete = $update = array();

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));
            $inputParams = $ids;
            $inputParams[] = $user;

            $sql = "SELECT `id`,`deck` FROM `{$this->table}` WHERE `id` IN ({$placeHolders}) AND `user`=?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($inputParams);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $delete[] = $row['id'];
                $update[] = $row['deck'];
            }

            if ($delete) {
                try {
                    $this->db->beginTransaction();

                    $deckModel = MySQL_DeckModel::getModel($this->db);
                    $deckModel->favorite($update, false);

                    $this->delete($delete);

                    $this->db->commit();

                    $result = count($delete);
                } catch (Exception $e) {
                    $this->db->rollback();
                    //@todo have a log
                }
            }
        }

        return $result;
    }

    /**
     * Get all favorites of user
     *
     * @param int $user
     * @param array $columns
     * @return array
     */
    public function my($user, $columns = null)
    {
        $result = array();

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

        if (!in_array('id', $fields)) {
            $fields[] = 'id';
        }
        $fields = implode(',', $fields);

        $sql = "SELECT {$fields} FROM `{$this->table}` WHERE `user`=:user ORDER BY `added_on` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user' => $user,
        ));

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }
}