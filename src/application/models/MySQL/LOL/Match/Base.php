<?php
class MySQL_LOL_Match_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'match';

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

    protected $defaultFields = array(
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

    public function cleanup($from, $to)
    {
        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `created_on`>=:from AND `created_on`<:to";
        $stmt = $this->db->prepare($sql);

        $stmt->execute(array(
            ':from' => $from,
            ':to'   => $to,
        ));

        return $stmt->rowCount();
    }
}