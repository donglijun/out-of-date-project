<?php
class MySQL_Gift_ChannelLogModel extends MySQL_BaseIDModel
{
    protected $table = 'gift_channel_log';

    protected $fields = array(
        'id',
        'channel',
        'user',
        'number',
        'dealt_on',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'user',
        'number',
        'dealt_on',
    );

    public function sum($from, $to, $channel = null)
    {
        $result = 0;
        $where = array();

        if ($from) {
            $where[] = "`dealt_on` >= {$from}";
        }

        if ($to) {
            $where[] = "`dealt_on` < {$to}";
        }

        if ($channel) {
            $where[] = "`channel` = {$channel}";
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT SUM(`number`) FROM `{$this->table}` WHERE {$where}";
        $stmt = $this->db->query($sql);
        $result = (int) $stmt->fetchColumn();

        return $result;
    }

    public function sumByChannel($from, $to)
    {
        $where = array();

        if ($from) {
            $where[] = "`dealt_on` >= {$from}";
        }

        if ($to) {
            $where[] = "`dealt_on` < {$to}";
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT `channel`, SUM(`number`) AS `sum` FROM `{$this->table}` WHERE {$where} GROUP BY `channel` ORDER BY `sum` DESC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sumByUser($from, $to)
    {
        $where = array();

        if ($from) {
            $where[] = "`dealt_on` >= {$from}";
        }

        if ($to) {
            $where[] = "`dealt_on` < {$to}";
        }

        $where = implode(' AND ', $where);

        $sql = "SELECT `user`, SUM(`number`) AS `sum` FROM `{$this->table}` WHERE {$where} GROUP BY `user` ORDER BY `sum` DESC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}