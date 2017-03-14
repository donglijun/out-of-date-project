<?php
class SphinxQL_LOL_Champion_PickBan_BaseModel extends SphinxQL_BaseModel
{
//    protected $index = 'rt_lol_champion_pick_ban';
    protected $index = 'lol_champion_pick_ban';

    protected $sort = 'id ASC';

    protected $fields = array(
        'id',
        'pick',
        'ban',
        'map',
        'mode',
        'start',
        'created_on',
    );

    protected $mva = array(
        'pick',
        'ban',
    );
}