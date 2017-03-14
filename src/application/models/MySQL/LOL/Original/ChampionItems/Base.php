<?php
class MySQL_LOL_Original_ChampionItems_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'championItems';

    protected $fields = array(
        'id',
        'championId',
        'itemId',
        'gameMode',
    );

    protected $defaultFields = array(
        'id',
        'championId',
        'itemId',
        'gameMode',
    );
}