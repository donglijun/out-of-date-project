<?php
class MySQL_Streaming_LiveLengthLogModel extends MySQL_BaseIDModel
{
    protected $table = 'live_length_log';

    protected $fields = array(
        'id',
        'channel',
        'upstream_ip',
        'session',
        'from',
        'to',
        'length',
        'hourly_pay',
        'exclusive_bonus',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'upstream_ip',
        'session',
        'from',
        'to',
        'length',
        'hourly_pay',
        'exclusive_bonus',
    );

    public function locate($channel, $upstream_ip, $session)
    {
        $sql = "SELECT `id`, `from`, `to` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `upstream_ip`=:upstream_ip AND `session`=:session";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'      => $channel,
            ':upstream_ip'  => $upstream_ip,
            ':session'      => $session,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function campaign($times, $from, $to)
    {
        $result = $rows = array();

        $sql = <<<EOT
SELECT `channel`, COUNT(`channel`) AS `times`, SUM(`length`) as `lengths`
FROM `{$this->schema}`.`{$this->table}`
WHERE `from` >= :from
AND `from` < :to
GROUP BY `channel`
HAVING `times` >= :times
ORDER BY `times` DESC
EOT;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':from'     => $from,
            ':to'       => $to,
            ':times'    => $times,
        ));

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $result[$row['channel']] = $row;
        }

        return $result;
    }

    public function lengthRanking($from, $to, $minFragment = 600)
    {
        $result = $rows = array();

        $sql = <<<EOT
SELECT `channel`, COUNT(`channel`) AS `times`, SUM(`length`) as `lengths`
FROM `{$this->schema}`.`{$this->table}`
WHERE `from` >= :from
AND `from` < :to
AND `length` >= :min
GROUP BY `channel`
ORDER BY `lengths` DESC
EOT;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':from' => $from,
            ':to'   => $to,
            ':min'  => $minFragment,
        ));

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $result[$row['channel']] = $row;
        }

        return $result;
    }

    public function recent($channel, $days = 30)
    {
        $from = strtotime(sprintf('-%d day', (int) $days));

        $sql = "SELECT `channel`, `from`, `to`, `length` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `from`>=:from ORDER BY `id` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'  => $channel,
            ':from'     => $from,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function last($channel)
    {
        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel ORDER BY `from` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'      => $channel,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}