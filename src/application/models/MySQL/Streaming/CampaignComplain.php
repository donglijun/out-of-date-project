<?php
class MySQL_Streaming_CampaignComplainModel extends MySQL_BaseIDModel
{
    protected $table = 'campaign_complain';

    protected $fields = array(
        'id',
        'user',
        'reason',
        'contact',
        'status',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'reason',
        'contact',
        'status',
        'created_on',
    );
}