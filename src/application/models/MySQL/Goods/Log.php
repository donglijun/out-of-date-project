<?php
class MySQL_Goods_LogModel extends MySQL_BaseIDModel
{
    protected $table = 'goods_log';

    protected $fields = array(
        'id',
        'sender',
        'receiver',
        'goods',
        'number',
        'golds',
        'withdraw_rate',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'sender',
        'receiver',
        'goods',
        'number',
        'golds',
        'withdraw_rate',
        'created_on',
    );

    public function getHistoryByDay($channel, $days = 30, $timezone = null)
    {
        if (!is_null($timezone)) {
//            $timezoneVal = ($timezone >= 0 ? '+' . $timezone : $timezone) . ':00';
            $timezoneVal = date_default_timezone_get();

//            $sql = "set time_zone '{$timezoneVal}'";
//            $this->db->exec($sql);
        }

        $timestamp = mktime(0, 0, 0, date('m'), date('d') - $days, date('Y'));

        $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`created_on`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, `goods`, SUM(`number`) AS `total` FROM `{$this->schema}`.`{$this->table}` WHERE `receiver`=:receiver AND `created_on`>=:timestamp GROUP BY `dt`, `goods` ORDER BY `dt` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':receiver'  => $channel,
            ':timestamp' => $timestamp,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}