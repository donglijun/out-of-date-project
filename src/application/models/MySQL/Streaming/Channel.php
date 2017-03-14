<?php
class MySQL_Streaming_ChannelModel extends MySQL_BaseIDModel
{
    protected $table = 'channel';

    protected $fields = array(
        'id',
        'title',
        'hash',
        'is_online',
        'is_banned',
        'is_signed',
        'is_exclusive',
        'owner_name',
        'playing_game',
        'alias',
        'special',
        'followers',
        'upstream_ip',
        'upstream_on',
        'resolutions',
        'paypal',
        'facebook',
        'class',
        'small_show_image',
        'large_show_image',
        'offline_image',
        'background_image',
        'created_on',
        'memo',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'hash',
        'is_online',
        'is_banned',
        'is_signed',
        'is_exclusive',
        'owner_name',
        'playing_game',
        'alias',
        'special',
        'followers',
        'upstream_ip',
        'upstream_on',
        'resolutions',
        'paypal',
        'facebook',
        'class',
        'small_show_image',
        'large_show_image',
        'offline_image',
        'background_image',
    );

    public function authenticate($channel, $password)
    {
        $sql = "SELECT `password` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $channel,
        ));

        $passwordHash = $stmt->fetchColumn();

        return $password && password_verify($password, $passwordHash);
    }

    public function authenticateStream($channel, $hash)
    {
        $sql = "SELECT `hash` FROM `{$this->schema}`.`{$this->table}` WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':id'   => $channel,
        ));

        $channelHash = $stmt->fetchColumn();

        return $channelHash == $hash;
    }

    public static function makeStreamKey($channel, $hash)
    {
        return sprintf('live_%d_%s', $channel, $hash);
    }

    public function resetStreamKey($channel)
    {
        $channelHash = md5($channel . microtime());

        $this->update($channel, array(
            'hash'  => $channelHash,
        ));

        return static::makeStreamKey($channel, $channelHash);
    }

    public function ban($ids, $status = 1)
    {
        $result = false;

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));
            array_unshift($ids, $status);

            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `is_banned`=? WHERE `id` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function online($channel, $upstreamIp)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `is_online`=1, `upstream_ip`=:upstream_ip, `upstream_on`=:upstream_on WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':id'           => $channel,
            ':upstream_ip'  => $upstreamIp,
            ':upstream_on'  => time(),
        ));
    }

    public function offline($channel)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `is_online`=0 WHERE `id`=:id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':id'   => $channel,
        ));
    }

    public function follow($channel, $up = true)
    {
        if ($up) {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `followers`=`followers`+1 WHERE `id`=:id";
        } else {
            $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `followers`=`followers`-1 WHERE `id`=:id";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':id'   => $channel,
        ));
    }

    public function getOwnerNames($ids)
    {
        $result = array();

        if ($ids) {
            $placeHolders = implode(',', array_fill(0, count($ids), '?'));

            $sql = "SELECT `id`,`owner_name` FROM `{$this->schema}`.`{$this->table}` WHERE `id` IN ({$placeHolders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[$row['id']] = $row['owner_name'];
            }
        }

        return $result;
    }

    public function aliasToID($alias)
    {
        $result = false;

        if ($alias) {
            $sql = "SELECT `id` FROM `{$this->schema}`.`{$this->table}` WHERE `alias`=:alias LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':alias' => $alias,
            ));

            $result = $stmt->fetchColumn(0);
        }

        return $result;
    }

    public function getRowsBySpecial($special = 'league', $columns = null)
    {
        $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        if (!in_array($this->primary, $columns)) {
            $columns[] = $this->primary;
        }

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `special`=:special ORDER BY `alias` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':special' => $special,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}