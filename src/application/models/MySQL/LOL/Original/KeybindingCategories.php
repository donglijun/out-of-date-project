<?php
class MySQL_LOL_Original_KeybindingCategoriesModel extends MySQL_BaseIDModel
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