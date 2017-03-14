<?php
class MySQL_LOL_Original_Champions_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'champions';

    protected $fields = array(
        'id',
        'name',
        'displayName',
        'title',
        'iconPath',
        'portraitPath',
        'splashPath',
        'danceVideoPath',
        'tags',
        'description',
        'quote',
        'quoteAuthor',
        'range',
        'moveSpeed',
        'armorBase',
        'armorLevel',
        'manaBase',
        'manaLevel',
        'criticalChanceBase',
        'criticalChanceLevel',
        'manaRegenBase',
        'manaRegenLevel',
        'healthRegenBase',
        'healthRegenLevel',
        'magicResistBase',
        'magicResistLevel',
        'healthBase',
        'healthLevel',
        'attackBase',
        'attackLevel',
        'ratingDefense',
        'ratingMagic',
        'ratingDifficulty',
        'ratingAttack',
        'tips',
        'opponentTips',
        'selectSoundPath',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'displayName',
        'title',
        'iconPath',
        'portraitPath',
        'splashPath',
        'danceVideoPath',
        'tags',
        'description',
        'quote',
        'quoteAuthor',
        'range',
        'moveSpeed',
        'armorBase',
        'armorLevel',
        'manaBase',
        'manaLevel',
        'criticalChanceBase',
        'criticalChanceLevel',
        'manaRegenBase',
        'manaRegenLevel',
        'healthRegenBase',
        'healthRegenLevel',
        'magicResistBase',
        'magicResistLevel',
        'healthBase',
        'healthLevel',
        'attackBase',
        'attackLevel',
        'ratingDefense',
        'ratingMagic',
        'ratingDifficulty',
        'ratingAttack',
        'tips',
        'opponentTips',
        'selectSoundPath',
    );

    public function getChampionsMap()
    {
        $result = array();

        $sql = "SELECT `id`,`name` FROM `{$this->schema}`.`{$this->table}`";

        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function getIds()
    {
        $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}`";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function formatForJS()
    {
        $result = array();

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = array(
                'name'          => $row['name'],
                'displayName'   => $row['displayName'],
                'title'         => $row['title'],
                'tags'          => $row['tags'],
            );
        }

        return $result;
    }
}