<?php
class MySQL_Streaming_ColumnItemModel extends MySQL_BaseIDModel
{
    const BASE_TOP_DISPLAY_ORDER = 3000000000;

    const MEDIA_TYPE_STREAMING = 'streaming';

    const MEDIA_TYPE_VIDEO = 'video';

    const MEDIA_TYPE_LINK = 'link';

    const MEDIA_TYPE_YOUTUBE = 'youtube';

    protected $table = 'column_item';

    protected $fields = array(
        'id',
        'column',
        'media_type',
        'source',
        'title',
        'small_image',
        'large_image',
        'live_schedule_time',
        'display_order',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'column',
        'media_type',
        'source',
        'title',
        'small_image',
        'large_image',
        'live_schedule_time',
    );

    public function getRowsByColumn($column, $columns = null)
    {
        $result = array();
        $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        if ($fields = implode(',', $fields)) {
            if ($column !== null) {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `column`=:column ORDER BY `display_order` DESC, `id` DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':column' => $column,
                ));
            } else {
                $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` ORDER BY `display_order` DESC";
                $stmt = $this->db->query($sql);
            }

            if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
                $result = array();
            }
        }

        return $result;
    }

    public function deleteByColumn($column)
    {
        $sql = "DELETE FROM `{$this->schema}`.`{$this->table}` WHERE `column`=:column";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':column'   => $column,
        ));

        return $stmt->rowCount();
    }

    public function top($id)
    {
        $sql = "SELECT MAX(`display_order`) FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);
        $max = $stmt->fetchColumn();

        $max = $max < self::BASE_TOP_DISPLAY_ORDER ? self::BASE_TOP_DISPLAY_ORDER : $max;
        $max += 1;

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `display_order`=:display_order WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':display_order'    => $max,
            ':id'               => $id,
        ));
    }

    public function liftStreaming($channel)
    {
        $sql = "SELECT MAX(`display_order`) FROM `{$this->schema}`.`{$this->table}`";
        $stmt = $this->db->query($sql);
        $max = $stmt->fetchColumn();

        $max = $max < self::BASE_TOP_DISPLAY_ORDER ? self::BASE_TOP_DISPLAY_ORDER : $max;
        $max += 1;

        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `display_order`=:display_order WHERE `source`=:source AND `media_type`='streaming'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':display_order'    => $max,
            ':source'           => (int) $channel,
        ));
    }

    public function lowerStreaming($channel)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `display_order`=1 WHERE `source`=:source AND `media_type`='streaming'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':source' => (int) $channel,
        ));
    }

    public function getMediaTypes()
    {
        return array(
            static::MEDIA_TYPE_STREAMING,
            static::MEDIA_TYPE_VIDEO,
            static::MEDIA_TYPE_LINK,
            static::MEDIA_TYPE_YOUTUBE,
        );
    }
}