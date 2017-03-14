<?php
class MySQL_CardModel extends MySQL_BaseModel
{
    const CARD_TYPE_MINION  = 4;
    const CARD_TYPE_ABILITY = 5;
    const CARD_TYPE_WEAPON  = 7;

    const CARD_RARITY_COMMON    = 1;
    const CARD_RARITY_FREE      = 2;
    const CARD_RARITY_RARE      = 3;
    const CARD_RARITY_EPIC      = 4;
    const CARD_RARITY_LEGENDARY = 5;

    const CARD_CLASS_NONE           = 0;
    const CARD_CLASS_DEATHKNIGHT    = 1;
    const CARD_CLASS_DRUID          = 2;
    const CARD_CLASS_HUNTER         = 3;
    const CARD_CLASS_MAGE           = 4;
    const CARD_CLASS_PALADIN        = 5;
    const CARD_CLASS_PRIEST         = 6;
    const CARD_CLASS_ROGUE          = 7;
    const CARD_CLASS_SHAMAN         = 8;
    const CARD_CLASS_WARLOCK        = 9;
    const CARD_CLASS_WARRIOR        = 10;
    const CARD_CLASS_DREAM          = 11;

    protected $table = 'card';

    protected $fields = array(
        'id',
        'card_id',
        'name',
        'rarity',
        'type',
        'cost',
        'attack',
        'health',
        'class',
    );

    protected $defaultFields = array(
        'id',
        'card_id',
        'name',
        'rarity',
        'type',
        'cost',
        'attack',
        'health',
        'class',
    );

    public function getRowByCardID($cardId, $columns = null)
    {
        $result = array();
        $fields = $data = array();

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

        if ($cardId) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `card_id`=:card_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':card_id' => $cardId,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    public function getRowsByCardID($cardIds, $columns = null)
    {
        $result = $rowset = array();
        $fields = $data = array();

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

        if ($fields && !isset($fields['card_id'])) {
            $fields[] = $this->quoteIdentifier('card_id');
        }
        $fields = implode(',', $fields);

        if ($cardIds) {
            $placeHolders = implode(',', array_fill(0, count($cardIds), '?'));

            if ($fields) {
                $sql = "SELECT {$fields} FROM `{$this->table}` WHERE `card_id` IN ({$placeHolders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($cardIds);

                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rowset[$row['card_id']] = $row;
                }
            }

            foreach ($cardIds as $id) {
                if (array_key_exists($id, $rowset)) {
                    $result[$id] = $rowset[$id];
                }
            }
        }

        return $result;
    }

    public function search($select, $where = null, $sort = null, $offset = 0, $limit = 20)
    {
        $result = $data = array();

        $where = $where ? 'WHERE ' . $where : '';
        $sort =  $sort ? 'ORDER BY ' . $sort : '';

        $sql = "SELECT count(`card_id`) FROM `{$this->table}` {$where}";
        $stmt = $this->db->query($sql);
        $result['total_found'] = (int) $stmt->fetchColumn();
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT {$select} FROM `{$this->table}` {$where} {$sort} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($row['card_id'])) {
                $data[$row['card_id']] = $row;
            } else {
                $data[] = $row;
            }
        }
        $result['data'] = $data;

        return $result;
    }

    public static function getTypeMap()
    {
        return array(
            self::CARD_TYPE_MINION  => 'minion',
            self::CARD_TYPE_ABILITY => 'ability',
            self::CARD_TYPE_WEAPON  => 'weapon',
        );
    }

    public static function getRarityMap()
    {
        return array(
            self::CARD_RARITY_COMMON    => 'common',
            self::CARD_RARITY_FREE      => 'free',
            self::CARD_RARITY_RARE      => 'rare',
            self::CARD_RARITY_EPIC      => 'epic',
            self::CARD_RARITY_LEGENDARY => 'legendary',
        );
    }

    public static function getClassMap()
    {
        return array(
            self::CARD_CLASS_NONE           => 'none',
            self::CARD_CLASS_DEATHKNIGHT    => 'deathknight',
            self::CARD_CLASS_DRUID          => 'druid',
            self::CARD_CLASS_HUNTER         => 'hunter',
            self::CARD_CLASS_MAGE           => 'mage',
            self::CARD_CLASS_PALADIN        => 'paladin',
            self::CARD_CLASS_PRIEST         => 'priest',
            self::CARD_CLASS_ROGUE          => 'rogue',
            self::CARD_CLASS_SHAMAN         => 'shaman',
            self::CARD_CLASS_WARLOCK        => 'warlock',
            self::CARD_CLASS_WARRIOR        => 'warrior',
            self::CARD_CLASS_DREAM          => 'dream',
        );
    }
}