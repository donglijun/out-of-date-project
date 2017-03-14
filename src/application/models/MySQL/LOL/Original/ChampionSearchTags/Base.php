<?php
class MySQL_LOL_Original_ChampionSearchTags_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'championSearchTags';

    protected $fields = array(
        'id',
        'championId',
        'searchTagId',
    );

    protected $defaultFields = array(
        'id',
        'championId',
        'searchTagId',
    );
}