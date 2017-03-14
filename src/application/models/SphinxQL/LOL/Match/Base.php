<?php
class SphinxQL_LOL_Match_BaseModel extends SphinxQL_BaseModel
{
//    protected $index = 'rt_lol_match';
    protected $index = 'lol_match';

    protected $sort = 'game DESC';

    protected $fields = array(
        'id',
        'game',
        'user',
        'champion',
        'map',
        'mode',
        'ranked',
        'start',
        'k',
        'd',
        'a',
        'mddp',
        'pddp',
        'tdt',
        'lmk',
        'mk',
        'nmk',
        'gold',
        'len',
        'win',
        'items',
        'spells',
        'aps',
        'created_on',
    );

    protected $mva = array(
        'items',
        'spells',
    );

    public function getChampionsUsage()
    {
        //@todo select champion,win,count(*) from lol_match group by champion, win;
    }

    public function getUsersInMatch($start, $limit = 500000)
    {
        $result = array();

        $sql = "SELECT `user` FROM `{$this->index}` WHERE `user`>={$start} GROUP BY `user` ORDER BY `user` ASC LIMIT {$limit} OPTION max_matches=1000000";
        $stmt = $this->db->query($sql);

        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $result;
    }

    public function statsItems($where)
    {
        $result = array();

        $where = $where ? 'WHERE ' . $where : '';

        $sql = "SELECT GROUPBY() AS `item`, COUNT(*) AS `total` FROM `{$this->index}` {$where} GROUP BY `items` ORDER BY `total` DESC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['item']] = $row['total'];
        }

        return $result;
    }
}