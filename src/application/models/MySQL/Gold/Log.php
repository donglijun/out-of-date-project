<?php
class MySQL_Gold_LogModel extends MySQL_BaseIDModel
{
    const LOG_TYPE_RECHARGE = 1;

    const LOG_TYPE_CONSUME = 2;

    const LOG_TYPE_EARN = 3;

    const LOG_TYPE_WITHDRAW = 4;

    const LOG_TYPE_CANCEL_WITHDRAW = 5;

    const LOG_TYPE_BONUS = 6;

    const LOG_TYPE_RETURN = 7;

    const LOG_TYPE_ROLLBACK = 8;

    protected $table = 'gold_log';

    protected $fields = array(
        'id',
        'user',
        'number',
        'type',
        'dealt_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'number',
        'type',
        'dealt_on',
    );

    public function getTypeMap()
    {
        return array(
            static::LOG_TYPE_RECHARGE          => 'recharge',
            static::LOG_TYPE_CONSUME           => 'consume',
            static::LOG_TYPE_EARN              => 'earn',
            static::LOG_TYPE_WITHDRAW          => 'withdraw',
            static::LOG_TYPE_CANCEL_WITHDRAW   => 'cancel-withdraw',
            static::LOG_TYPE_RETURN            => 'return',
            static::LOG_TYPE_ROLLBACK          => 'rollback',
        );
    }
}