<?php
class MySQL_LOL_Champion_PickBan_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'champion_pick_ban';

    protected $fields = array(
        'id',
        'pick',
        'ban',
        'map',
        'mode',
        'start',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'pick',
        'ban',
        'map',
        'mode',
        'start',
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