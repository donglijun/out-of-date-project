<?php
class MySQL_LOL_Original_KeybindingCategories_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'keybindingCategories';

    protected $fields = array(
        'id',
        'name',
        'commands',
        'categoryIndex',
        'modeIndex',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'commands',
        'categoryIndex',
        'modeIndex',
    );
}