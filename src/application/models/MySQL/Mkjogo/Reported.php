<?php
class MySQL_Mkjogo_ReportedModel extends MySQL_BaseIDModel
{
    const STATUS_NEW = 1;

    const STATUS_RESOLVED = 2;

    protected $table = 'reported';

    protected $fields = array(
        'id',
        'target',
        'module',
        'type',
        'user',
        'user_name',
        'content',
        'reason',
        'reporter',
        'reporter_name',
        'status',
        'ip',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'target',
        'module',
        'type',
        'user',
        'user_name',
        'content',
        'reason',
        'reporter',
        'reporter_name',
        'status',
        'ip',
        'created_on',
    );

    protected $enumTypes = array(
        'link',
        'comment',
        'bullet',
        'channel',
        'chat',
    );

    public function validateType($type)
    {
        return in_array($type, $this->enumTypes);
    }

    public function resolve($target, $module, $type)
    {
        $sql = "UPDATE `{$this->schema}`.`{$this->table}` SET `status`=:status WHERE `target`=:target AND `module`=:module AND `type`=:type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':status'   => static::STATUS_RESOLVED,
            ':target'   => $target,
            ':module'   => $module,
            ':type'     => $type,
        ));

        return $stmt->rowCount();
    }
}