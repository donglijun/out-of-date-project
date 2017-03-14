<?php
class MySQL_League_MatchScheduleModel extends MySQL_BaseIDModel
{
    protected $table = 'league_match_schedule';

    protected $fields = array(
        'id',
        'season',
        'title',
        'from',
        'to',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'season',
        'title',
        'from',
        'to',
    );

    public function getBySeason($season)
    {
        $result = array();

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `season`=:season ORDER BY `from` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':season' => $season,
        ));

        if ($rows = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($rows as $row) {
                $result[$row['id']] = $row;
            }
        }

        return $result;
    }
}