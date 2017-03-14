<?php
class MySQL_Mkjogo_AnnouncementModel extends MySQL_BaseIDModel
{
    protected $table = 'announcement';

    protected $fields = array(
        'id',
        'client',
        'lang',
        'url',
        'status',
        'published_on',
        'published_by',
    );

    protected $defaultFields = array(
        'id',
        'client',
        'lang',
        'url',
        'status',
        'published_on',
        'published_by',
    );

    function publish($id, $user, $status = 1)
    {
        return $this->update($id, array(
            'status'        => $status ? 1 : 0,
            'published_by'  => $user,
            'published_on'  => time(),
        ));
    }

    function latest($client, $lang)
    {
        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `client`=:client AND `lang`=:lang AND `status`>0 ORDER BY `id` DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':client'   => $client,
            ':lang'     => $lang,
        ));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}