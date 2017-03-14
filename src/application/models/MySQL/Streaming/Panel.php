<?php
class MySQL_Streaming_PanelModel extends MySQL_BaseIDModel
{
    protected $table = 'panel';

    protected $fields = array(
        'id',
        'channel',
        'title',
        'image',
        'link',
        'description',
        'display_order',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'title',
        'image',
        'link',
        'description',
        'display_order',
        'created_on',
    );

}