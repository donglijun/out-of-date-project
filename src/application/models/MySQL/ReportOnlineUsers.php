<?php
class MySQL_ReportOnlineUsersModel extends MySQL_ReportBaseModel
{
    protected $table = 'report_online_users';

    protected $fields = array(
        'date',
        'lang',
        'total',
        'updated_on',
    );

    protected $defaultFields = array(
        'date',
        'lang',
        'total',
        'updated_on',
    );

    public static function key($timestamp = null)
    {
        $timestamp = $timestamp ?: time();

        return sprintf('%s%02d', date('YmdH', $timestamp), floor(date('i', $timestamp) / 5));
    }

    public function betweenOnHourTime($from, $to, $lang)
    {
        $where = $parameters = array();

        if ($from) {
            $where[] = '`date`>=:from';
            $parameters[':from'] = $from;
        }

        if ($to) {
            $where[] = '`date`<:to';
            $parameters[':to'] = $to;
        }

        if ($lang) {
            $where[] = '`lang`=:lang';
            $parameters[':lang'] = $lang;
        }

        $where[] = '`date` % 100 = 0';

        $where = implode(' AND ', $where);

        $sql = "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY `date` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLangMap()
    {
        $result = array();

        $sql = "SELECT DISTINCT `lang` FROM `{$this->table}` ORDER BY `lang` ASC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['lang']] = $row['lang'];
        }

        return $result;
    }
}