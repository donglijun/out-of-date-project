<?php
class MySQL_Streaming_CampaignMemberModel extends MySQL_BaseIDModel
{
    protected $table = 'campaign_member';

    protected $fields = array(
        'id',
        'name',
        'game_account',
        'facebook',
        'skype',
        'signed_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
        'game_account',
        'facebook',
        'skype',
        'signed_on',
    );
}