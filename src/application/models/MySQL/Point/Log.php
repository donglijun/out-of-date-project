<?php
class MySQL_Point_LogModel extends MySQL_BaseIDModel
{
    const LOG_TYPE_RECHARGE = 1;

    const LOG_TYPE_CONSUME_RED = 2;

    const LOG_TYPE_EARN_RED = 3;

    const LOG_TYPE_RETURN_RED = 4;

    const LOG_TYPE_EXCHANGE_CARD = 5;

    const LOG_TYPE_AWARD = 6;

    const LOG_TYPE_EARN_TASK = 7;

    protected $table = 'point_log';

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
            static::LOG_TYPE_RECHARGE      => 'recharge',
            static::LOG_TYPE_CONSUME_RED   => 'consume-red',
            static::LOG_TYPE_EARN_RED      => 'earn-red',
            static::LOG_TYPE_RETURN_RED    => 'return-red',
            static::LOG_TYPE_EXCHANGE_CARD => 'exchange-card',
            static::LOG_TYPE_AWARD         => 'award',
            static::LOG_TYPE_EARN_TASK     => 'earn-task',
        );
    }
}