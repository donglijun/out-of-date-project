<?php
class MySQL_LOL_Original_ChampionSkinsModel extends MySQL_BaseIDModel
{
    protected $table = 'championSkins';

    protected $fields = array(
        'id',
        'isBase',
        'rank',
        'championId',
        'name',
        'displayName',
        'portraitPath',
        'splashPath',
    );

    protected $defaultFields = array(
        'id',
        'isBase',
        'rank',
        'championId',
        'name',
        'displayName',
        'portraitPath',
        'splashPath',
    );
}