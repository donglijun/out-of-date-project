<?php
class MySQL_Gift_RaceModel extends MySQL_BaseIDModel
{
    protected $table = 'gift_race';

    protected $fields = array(
        'id',
        'from',
        'to',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'from',
        'to',
        'created_on',
    );

}