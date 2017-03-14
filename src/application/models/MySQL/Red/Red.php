<?php
class MySQL_Red_RedModel extends MySQL_BaseIDModel
{
    const RED_CLIENT_WEB = 1;

    const RED_CLIENT_ANDROID = 2;

    const RED_CLIENT_IOS = 4;

    const RED_CLIENT_WP = 8;

    protected $table = 'red';

    protected $fields = array(
        'id',
        'user',
        'name',
        'points',
        'number',
        'consumed_points',
        'consumed_number',
        'returned_points',
        'memo',
        'target_channel',
        'target_client',
        'hash',
        'created_on',
        'ending_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'name',
        'points',
        'number',
        'consumed_points',
        'consumed_number',
        'returned_points',
        'memo',
        'target_channel',
        'target_client',
        'hash',
        'created_on',
        'ending_on',
    );

    public function consume($id, $points)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `consumed_points`=`consumed_points`+:points, `consumed_number`=`consumed_number`+1, `ending_on`=:ending_on WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':points'    => $points,
            ':id'        => $id,
            ':ending_on' => time(),
        ));

        return $stmt->rowCount() ? true : false;
    }

    public function checkAlive($user)
    {
        $result = false;

        $sql = "SELECT `points`, `consumed_points`, `returned_points` FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user' => $user,
        ));

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result = ($row['points'] != $row['consumed_points'] + $row['returned_points']);
        }

        return $result;
    }

    public function getLast($user)
    {
        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `user`=:user AND `target_channel`>0 ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':user' => $user,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLastSystem($client = 0)
    {
        if ($client) {
            $clients = array(0, (int) $client);
            $clients = implode(',', array_unique($clients));
            $sql = "SELECT `id`,`points`,`number`,`consumed_points`,`consumed_number`,`returned_points`,`memo`,`target_channel`,`target_client`,`hash`,`created_on`,`ending_on` FROM `{$this->schema}`.`{$this->table}` WHERE `target_channel`=0 AND `target_client` IN ({$clients}) ORDER BY `id` DESC LIMIT 1";
        } else {
            $sql = "SELECT `id`,`points`,`number`,`consumed_points`,`consumed_number`,`returned_points`,`memo`,`target_channel`,`target_client`,`hash`,`created_on`,`ending_on` FROM `{$this->schema}`.`{$this->table}` WHERE `target_channel`=0 ORDER BY `id` DESC LIMIT 1";
        }

        $stmt = $this->db->query($sql);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getClientMap()
    {
        return array(
            static::RED_CLIENT_WEB     => 'Web',
            static::RED_CLIENT_ANDROID => 'Android',
            static::RED_CLIENT_IOS     => 'iOS',
            static::RED_CLIENT_WP      => 'WP',
        );
    }

    public function summary($offset = 0, $limit = 50, $timezone = null)
    {
        $result = array();

        if (!is_null($timezone)) {
//            $timezoneVal = ($timezone >= 0 ? '+' . $timezone : $timezone) . ':00';
            $timezoneVal = date_default_timezone_get();
        }

        $sql = "SELECT COUNT(`{$this->primary}`) AS `total_found`, SUM(`points`) AS `total_points` FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['total_found'] = (int) $result['total_found'];
        $result['total_points'] = (int) $result['total_points'];
        $result['page_count']  = ceil($result['total_found'] / $limit);

        $sql = "SELECT DATE(CONVERT_TZ(FROM_UNIXTIME(`created_on`), @@session.time_zone, '{$timezoneVal}')) AS `dt`, SUM(`points`) AS `points` FROM `{$this->schema}`.`{$this->table}` GROUP BY `dt` ORDER BY `dt` DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
}