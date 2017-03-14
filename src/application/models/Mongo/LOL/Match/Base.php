<?php
class Mongo_LOL_Match_BaseModel extends Mongo_CollectionModel
{
    const GAMETYPE_CUSTOM       = 'CUSTOM';

    const GAMETYPE_MATCHED_GAME = 'MATCHED_GAME';

    protected $dbName = 'lol';

    protected $collectionName = 'match';

    protected $defaultFields = array(
        'gameid',
        'gamelen',
        'gamestarttime',
        'gametype',
        'otherteamplayers',
        'queuetype',
        'ranked',
        'selfteamplayers',
        'mkdata',
    );

    protected function formatMatch($raw)
    {
        $result = array(
            'game'      => (int) $raw['gameid'],
            'len'       => (int) $raw['gamelen'],
            'start'     => (int) $raw['gamestarttime'],
            'mode'      => $raw['queuetype'],
            'ranked'    => (int) $raw['ranked'],
            'map'       => (int) $raw['mkdata']['mapId'],
            'win_t'     => array(),
            'lose_t'    => array(),
        );

        $st = $ot = array();
        $sw = false;

        foreach ($raw['selfteamplayers'] as $player) {
            $maindata = current($player['maindata']);

            $detail = array(
                'id'            => (int) $player['userid'],
                'name'          => $player['summonername'],
                'champion'      => $player['champion'],
                'bot'           => (int) $player['botPlayer'],
                'k'             => (int) $maindata['CHAMPIONS_KILLED'],
                'd'             => (int) $maindata['NUM_DEATHS'],
                'a'             => (int) $maindata['ASSISTS'],
                'mddp'          => (int) $maindata['MAGIC_DAMAGE_DEALT_PLAYER'],
                'pddp'          => (int) $maindata['PHYSICAL_DAMAGE_DEALT_PLAYER'],
                'tdt'           => (int) $maindata['TOTAL_DAMAGE_TAKEN'],
                'lmk'           => (int) $maindata['LARGEST_MULTI_KILL'],
                'mk'            => (int) $maindata['MINIONS_KILLED'],
                'nmk'           => (int) $maindata['NEUTRAL_MINIONS_KILLED'],
                'gold'          => (int) $maindata['GOLD_EARNED'],
                'items'         => array(
                    (int) $maindata['ITEM0'],
                    (int) $maindata['ITEM1'],
                    (int) $maindata['ITEM2'],
                    (int) $maindata['ITEM3'],
                    (int) $maindata['ITEM4'],
                    (int) $maindata['ITEM5'],
                    (int) $maindata['ITEM6'],
                ),
                'spells'        => array(
                    (int) $player['spell1Id'],
                    (int) $player['spell2Id'],
                ),
            );

            $st[] = $detail;

            if (isset($maindata['WIN']) && $maindata['WIN']) {
                $sw = true;
            }
        }

        foreach ($raw['otherteamplayers'] as $player) {
            $userid   = (int) $player['userid'];
            $maindata = current($player['maindata']);

            $detail = array(
                'id'            => (int) $player['userid'],
                'name'          => $player['summonername'],
                'champion'      => $player['champion'],
                'bot'           => (int) $player['botPlayer'],
                'k'             => (int) $maindata['CHAMPIONS_KILLED'],
                'd'             => (int) $maindata['NUM_DEATHS'],
                'a'             => (int) $maindata['ASSISTS'],
                'mddp'          => (int) $maindata['MAGIC_DAMAGE_DEALT_PLAYER'],
                'pddp'          => (int) $maindata['PHYSICAL_DAMAGE_DEALT_PLAYER'],
                'tdt'           => (int) $maindata['TOTAL_DAMAGE_TAKEN'],
                'lmk'           => (int) $maindata['LARGEST_MULTI_KILL'],
                'mk'            => (int) $maindata['MINIONS_KILLED'],
                'nmk'           => (int) $maindata['NEUTRAL_MINIONS_KILLED'],
                'gold'          => (int) $maindata['GOLD_EARNED'],
                'items'         => array(
                    (int) $maindata['ITEM0'],
                    (int) $maindata['ITEM1'],
                    (int) $maindata['ITEM2'],
                    (int) $maindata['ITEM3'],
                    (int) $maindata['ITEM4'],
                    (int) $maindata['ITEM5'],
                    (int) $maindata['ITEM6'],
                ),
                'spells'        => array(
                    (int) $player['spell1Id'],
                    (int) $player['spell2Id'],
                ),
            );

            $ot[] = $detail;
        }

        if ($sw) {
            $result['win_t']    = $st;
            $result['lose_t']   = $ot;
        } else {
            $result['lose_t']   = $st;
            $result['win_t']    = $ot;
        }

        return $result;
    }

    public function getRows($ids, $columns = null, $sort = null)
    {
        $result = $fields = array();

        $query = array(
            '_id'   => array(
                '$in'   => $ids,
            ),
        );

        $sort = $sort ?: array(
            '_id'   => -1,
        );

        $fields = is_array($columns) && $columns ? $columns : $this->defaultFields;
        $fields = array_fill_keys($fields, true);

        $cursor = $this->collection->find($query, $fields)->sort($sort);
        $cursor = iterator_to_array($cursor);

        foreach ($cursor as $key => $val) {
            $result[] = $this->formatMatch($val);
        }

        return $result;
    }

    public function getRow($id, $columns = null)
    {
        $result = $fields = array();

        $query = array(
            '_id'   => $id,
        );

        $fields = is_array($columns) && $columns ? $columns : $this->defaultFields;
        $fields = array_fill_keys($fields, true);

        $result = $this->collection->findOne($query, $fields);

        if ($result) {
            $result = $this->formatMatch($result);
        }

        return $result;
    }

    public function cleanup($from, $to)
    {
        $result = array();

        $criteria = array(
            'gamestarttime' => array(
                '$gt'   => (int) $from,
                '$lt'   => (int) $to,
            ),
        );

        $result = $this->collection->remove($criteria);

        return $result && isset($result['n']) ? $result['n'] : 0;
    }
}