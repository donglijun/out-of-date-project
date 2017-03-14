<?php
class MySQL_Gold_WithdrawOrderModel extends MySQL_BaseIDModel
{
    const STATUS_NEW = 0;

    const STATUS_COMPLETED = 1;

    const STATUS_CANCELED = 2;

    protected $table = 'gold_withdraw_order';

    protected $fields = array(
        'id',
        'user',
        'golds',
        'money',
        'status',
        'created_on',
        'processed_on',
        'paypal',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'golds',
        'money',
        'status',
        'created_on',
        'processed_on',
        'paypal',
    );
}