<?php
class SphinxQL_LOL_User_BaseModel extends SphinxQL_BaseModel
{
//    protected $index = 'rt_lol_user';
    protected $index = 'lol_user';

    protected $sort = 'WEIGHT() DESC, name ASC';

    protected $fields = array(
        'id',
        'level',
        'icon_id',
        'metadata',
        'updated_on',
    );
}