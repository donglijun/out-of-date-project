<?php
class MySQL_Streaming_BroadcastHighlightModel extends MySQL_BaseIDModel
{
    protected $table = 'broadcast_highlight';

    protected $fields = array(
        'id',
        'channel',
        'broadcast',
        'start',
        'stop',
        'length',
        'size',
        'w',
        'h',
        'title',
        'memo',
        'submitted_on',
        'uploaded_on',
        'remote_path',
        'preview_path',
        'total_views',
        'total_bullets',
        'is_hidden',
    );

    protected $defaultFields = array(
        'id',
        'channel',
        'broadcast',
        'start',
        'stop',
        'length',
        'size',
        'w',
        'h',
        'title',
        'memo',
        'submitted_on',
        'uploaded_on',
        'remote_path',
        'preview_path',
        'total_views',
        'total_bullets',
        'is_hidden',
    );

    public function view($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `total_views`=`total_views`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

    public function bullet($id)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `total_bullets`=`total_bullets`+1 WHERE `id`=:id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array(
            ':id'   => $id,
        ));
    }

}