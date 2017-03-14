<?php
class MySQL_LOL_Original_SearchTags_BaseModel extends MySQL_BaseIDModel
{
    protected $table = 'searchTags';

    protected $fields = array(
        'id',
        'name',
        'displayName',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'displayName',
    );
}