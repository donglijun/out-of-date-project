<?php
class MySQL_User_SigninAttemptLogModel extends MySQL_BaseIDModel
{
    protected $table = 'signin_attempt_log';

    protected $fields = array(
        'id',
        'ip',
        'account',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'ip',
        'account',
        'created_on',
    );
}