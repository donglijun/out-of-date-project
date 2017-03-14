<?php
class MySQL_Streaming_WatchingLengthSummaryModel extends MySQL_BaseIDModel
{
    protected $table = 'watching_length_summary';

    protected $fields = array(
        'id',
        'dt',
        'views',
        'length',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'dt',
        'views',
        'length',
        'created_on',
    );
}