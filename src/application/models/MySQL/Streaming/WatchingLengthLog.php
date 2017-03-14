<?php
class MySQL_Streaming_WatchingLengthLogModel extends MySQL_BaseIDModel
{
    protected $table = 'watching_length_log';

    protected $fields = array(
        'id',
        'channel',
        'upstream_ip',
        'session',
        'from',
        'to',
        'length',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'upstream_ip',
        'session',
        'from',
        'to',
        'length',
    );

    public function locate($channel, $upstream_ip, $session)
    {
        $sql = "SELECT `id`, `from`, `to` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `upstream_ip`=:upstream_ip AND `session`=:session ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'      => $channel,
            ':upstream_ip'  => $upstream_ip,
            ':session'      => $session,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function summary($from, $to)
    {
        $sql = "SELECT COUNT(`length`) AS `v`, SUM(`length`) AS `l` FROM `{$this->schema}`.`{$this->table}` WHERE `from`>=:from AND `from`<:to AND `length`>0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':from' => $from,
            ':to' => $to,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function summaryByChannel($from, $to, $minFragment = 60)
    {
        $sql = "SELECT `channel`, COUNT(`length`) AS `v`, SUM(`length`) AS `l` FROM `{$this->schema}`.`{$this->table}` WHERE `from`>=:from AND `from`<:to AND `length`>=:min GROUP BY `channel` ORDER BY `l` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':from' => $from,
            ':to' => $to,
            ':min' => $minFragment,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}