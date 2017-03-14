<?php
class MySQL_Streaming_BroadcastModel extends MySQL_BaseIDModel
{
    protected $table = 'broadcast';

    protected $fields = array(
        'id',
        'channel',
        'upstream_ip',
        'session',
        'recording_ip',
        'recording_on',
        'ending_on',
        'length',
        'size',
        'w',
        'h',
        'title',
        'memo',
        'uploaded_on',
        'remote_path',
        'preview_path',
        'total_views',
        'is_deleted',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'upstream_ip',
        'session',
        'recording_ip',
        'recording_on',
        'ending_on',
        'length',
        'size',
        'w',
        'h',
        'title',
        'memo',
        'uploaded_on',
        'remote_path',
        'preview_path',
        'total_views',
        'is_deleted',
    );

    public function locate($channel, $upstream_ip, $session)
    {
        $sql = "SELECT `id`, `recording_ip` FROM `{$this->schema}`.`{$this->table}` WHERE `channel`=:channel AND `upstream_ip`=:upstream_ip AND `session`=:session";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':channel'      => $channel,
            ':upstream_ip'  => $upstream_ip,
            ':session'      => $session,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getFailedUpload($hoursAgo)
    {
        $ago = time() - $hoursAgo * 3600;

        $sql = "SELECT `id`, `recording_ip` FROM `{$this->schema}`.`{$this->table}` WHERE `recording_on`<:ago AND `ending_on`>0 AND `length`>0 AND `uploaded_on`=0 ORDER BY `id` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':ago' => $ago,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fixNoEnding($hoursAgo)
    {
        $ago = time() - $hoursAgo * 3600;
        $ago2 = $ago - 3600;
        $ending = time();

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `ending_on`=:ending, `length`=1 WHERE `recording_on`>=:ago2 AND `recording_on`<=:ago AND `ending_on`=0";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':ago'    => $ago,
            ':ago2'   => $ago2,
            ':ending' => $ending,
        ));
    }

    public function view($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `total_views`=`total_views`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function campaign($times, $from, $to)
    {
        $result = $rows = array();

        $sql = <<<EOT
SELECT `channel`, COUNT(`channel`) AS `times`, SUM(`length`) as `lengths`
FROM `{$this->schema}`.`{$this->table}`
WHERE `recording_on` >= :from
AND `recording_on` < :to
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
WHERE `recording_on` >= :from
AND `recording_on` < :to
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

}