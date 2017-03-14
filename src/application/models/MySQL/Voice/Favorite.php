<?php
class MySQL_Voice_FavoriteModel extends MySQL_BaseIDModel
{
    protected $table = 'favorite';

    protected $fields = array(
        'id',
        'user',
        'room',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'room',
        'created_on',
    );
}