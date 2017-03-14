<?php
class MySQL_Streaming_WithdrawOrderModel extends MySQL_BaseIDModel
{
    const STATUS_NEW = 0;

    const STATUS_COMPLETED = 1;

    const STATUS_CANCELED = 2;

    protected $table = 'withdraw_order';

    protected $fields = array(
        'id',
        'user',
        'dt',
        'live_length',
        'live_salary',
        'live_exclusive_bonus',
        'goods_golds',
        'goods_money',
        'total_money',
        'pay_money',
        'status',
        'created_on',
        'processed_on',
        'paypal',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'dt',
        'live_length',
        'live_salary',
        'live_exclusive_bonus',
        'goods_golds',
        'goods_money',
        'total_money',
        'pay_money',
        'status',
        'created_on',
        'processed_on',
        'paypal',
    );

    public static function getStatusMap()
    {
        return array(
            static::STATUS_NEW  => 'New',
            static::STATUS_COMPLETED => 'Completed',
            static::STATUS_CANCELED => 'Canceled',
        );
    }
}