<?php
class MySQL_User_SigninLogModel extends MySQL_BaseIDModel
{
    protected $table = 'signin_log';

    protected $fields = array(
        'id',
        'user',
        'ip',
        'client',
        'client_version',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'ip',
        'client',
        'client_version',
        'created_on',
    );
}