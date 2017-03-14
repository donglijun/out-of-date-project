<?php
class MySQL_Streaming_SupervisorModel extends MySQL_BaseIDModel
{
    protected $table = 'supervisor';

    protected $fields = array(
        'id',
        'name',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'name',
    );

    public function isMember($user)
    {
        return $this->exists($user);
    }
}