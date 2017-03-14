<?php
class MySQL_LOL_Champion_Summoner_Ranking_BaseModel extends MySQL_BaseIDModel
{
    const SCHEMA_PREFIX = 'mkjogo_lol_';

    const TABLE_PREFIX = 'champion_summoner_ranking_';

    protected $fields = array(
        'id',
        'name',
        'league_tier',
        'league_rank',
        'total_matches',
        'win',
        'win_rate',
        'k',
        'd',
        'a',
        'kda',
        'items',
        'games',
        'rank',
        'date',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'league_tier',
        'league_rank',
        'total_matches',
        'win',
        'win_rate',
        'k',
        'd',
        'a',
        'kda',
        'items',
        'games',
        'rank',
        'date',
        'created_on',
    );

    public function __construct($db, $platform, $champion)
    {
        $this->schema = static::SCHEMA_PREFIX . $platform;

        $this->table = static::TABLE_PREFIX . $champion;

        parent::__construct($db);

//        $this->db->exec('USE ' . $this->schema);
    }

    public function updateName($platform)
    {
//        $userTable = 'user_' . $platform;

        $sql = <<<EOF
UPDATE `{$this->schema}`.`{$this->table}` `t1`, `{$this->schema}`.`user` `t2`
SET `t1`.`name` = `t2`.`name`
WHERE `t1`.`id` = `t2`.`id`
EOF;

        return $this->db->exec($sql);
    }

    public function calculateRank($baseline = 15)
    {
        $sql = <<<EOT
UPDATE `{$this->schema}`.`{$this->table}` `t1`, (
    SELECT `a`.`id`, (@rownum:=@rownum+1) AS `order`
    FROM `{$this->schema}`.`{$this->table}` `a`, (SELECT @rownum:=0) `b`
    WHERE `total_matches`>={$baseline}
    ORDER BY `win_rate` DESC, `total_matches` DESC, `kda` DESC
) `t2`
SET `t1`.`rank`=`t2`.`order`
WHERE `t1`.`id`=`t2`.`id`
EOT;

        return $this->db->exec($sql);
    }

    public function dropIndexes()
    {
        if ($this->indexExists('rank_factors')) {
            $sql = "ALTER TABLE `{$this->schema}`.`{$this->table}` DROP INDEX `rank_factors`";
            $this->db->exec($sql);
        }

        if ($this->indexExists('rank')) {
            $sql = "ALTER TABLE `{$this->schema}`.`{$this->table}` DROP INDEX `rank`";
            $this->db->exec($sql);
        }

        return $this;
    }

    public function createIndexes()
    {
        if (!$this->indexExists('rank_factors')) {
            $sql = "ALTER TABLE `{$this->schema}`.`{$this->table}` ADD INDEX `rank_factors` (`total_matches`, `win_rate`, `kda`)";
            $this->db->exec($sql);
        }

        if (!$this->indexExists('rank')) {
            $sql = "ALTER TABLE `{$this->schema}`.`{$this->table}` ADD INDEX `rank` (`rank`)";
            $this->db->exec($sql);
        }

        return $this;
    }

    public static function compareMulti($a, $b)
    {
        $result = 0;

        $tma = (int) $a['total_matches'];
        $tmb = (int) $b['total_matches'];

        $wra = (float) $a['win_rate'];
        $wrb = (float) $b['win_rate'];

        $kdaa = (float) $a['kda'];
        $kdab = (float) $b['kda'];

        if ($tma > $tmb) {
            $result = -1;
        } else if ($tma < $tmb) {
            $result = 1;
        } else {
            if ($wra > $wrb) {
                $result = -1;
            } else if ($wra < $wrb) {
                $result = 1;
            } else {
                if ($kdaa > $kdab) {
                    $result = -1;
                } else if ($kdaa < $kdab) {
                    $result = 1;
                }
            }
        }

        return $result;
    }

    public function getMultiByRank($summoner, $champions, $columns = null)
    {
        $result = $sql = $fields = array();

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

        if ($fields) {
            if (!isset($fields['win_rate'])) {
                $fields[] = 'win_rate';
            }

            if (!isset($fields['total_matches'])) {
                $fields[] = 'total_matches';
            }

            if (!isset($fields['kda'])) {
                $fields[] = 'kda';
            }
        }

        $fields = implode(',', $fields);

        $summoner = (int) $summoner;

        foreach ($champions as $champion) {
            $table = static::TABLE_PREFIX . $champion;
            $sql[] = "SELECT {$fields}, {$champion} AS `champion` FROM `{$this->schema}`.`{$table}` WHERE `id`={$summoner}";
        }
        $sql = implode(';', $sql);

        $stmt = $this->db->query($sql);

        if ($stmt) {
            do {
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $result[] = $row;
                }
            } while ($stmt->nextRowset());

            usort($result, array($this, 'compareMulti'));
        }

        return $result;
    }
}