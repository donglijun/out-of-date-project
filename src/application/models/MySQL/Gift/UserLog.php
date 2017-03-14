<?php
class MySQL_Gift_UserLogModel extends MySQL_BaseIDModel
{
    protected $table = 'gift_user_log';

    protected $fields = array(
        'id',
        'user',
        'channel',
        'highlight',
        'task',
        'number',
        'dealt_on',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'channel',
        'highlight',
        'task',
        'number',
        'dealt_on',
    );

    public function sumCollecting($from, $to)
    {
        $result = 0;
        $where = array();

        if ($from) {
            $where[] = "`dealt_on` >= {$from}";
        }

        if ($to) {
            $where[] = "`dealt_on` < {$to}";
        }

        $where[] = "`number`>0";

        $where = implode(' AND ', $where);

        $sql = "SELECT SUM(`number`) FROM `{$this->table}` WHERE {$where}";
        $stmt = $this->db->query($sql);
        $result = (int) $stmt->fetchColumn();

        return $result;
    }

    public function sumGiving($from, $to)
    {
        $result = 0;
        $where = array();

        if ($from) {
            $where[] = "`dealt_on` >= {$from}";
        }

        if ($to) {
            $where[] = "`dealt_on` < {$to}";
        }

        $where[] = "`number`<0";

        $where = implode(' AND ', $where);

        $sql = "SELECT SUM(`number`) FROM `{$this->table}` WHERE {$where}";
        $stmt = $this->db->query($sql);
        $result = (int) $stmt->fetchColumn();

        return $result * -1;
    }
}