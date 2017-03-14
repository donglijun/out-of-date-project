<?php
class MySQL_LOL_Original_KeybindingEventsModel extends MySQL_BaseIDModel
{
    protected $table = 'keybindingEvents';

    protected $fields = array(
        'id',
        'internalName',
        'displayName',
        'bindings',
    );

    protected $defaultFields = array(
        'id',
        'internalName',
        'displayName',
        'bindings',
    );
}