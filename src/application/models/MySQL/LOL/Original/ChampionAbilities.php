<?php
class MySQL_LOL_Original_ChampionAbilitiesModel extends MySQL_BaseIDModel
{
    protected $table = 'championAbilities';

    protected $fields = array(
        'id',
        'rank',
        'championId',
        'name',
        'cost',
        'cooldown',
        'iconPath',
        'videoPath',
        'range',
        'effect',
        'description',
        'hotkey',
    );

    protected $defaultFields = array(
        'id',
        'rank',
        'championId',
        'name',
        'cost',
        'cooldown',
        'iconPath',
        'videoPath',
        'range',
        'effect',
        'description',
        'hotkey',
    );

    public function groupAbilitiesByChampion()
    {
        $result = array();

        $sql = "SELECT `championId`, GROUP_CONCAT(`id` ORDER BY `rank` ASC) AS `abilities` FROM `{$this->table}` GROUP BY `championId` ORDER BY `championId` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['championId']] = $row['abilities'];
        }

        return $result;
    }

    public function formatForJS()
    {
        $result = array();

        $sql = "SELECT * FROM `{$this->table}` ORDER BY `id` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = array(
                'champion'  => $row['championId'],
                'name'      => $row['name'],
                'cost'      => $row['cost'],
                'cooldown'  => $row['cooldown'],
                'effect'    => $row['effect'],
                'hotkey'    => $row['hotkey'],
            );
        }

        return $result;
    }
}