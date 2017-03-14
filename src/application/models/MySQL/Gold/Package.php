<?php
class MySQL_Gold_PackageModel extends MySQL_BaseIDModel
{
    const PACKAGE_CLIENT_GENERAL = 0;

    const PACKAGE_CLIENT_WEB = 1;

    const PACKAGE_CLIENT_ANDROID = 2;

    const PACKAGE_CLIENT_IOS = 4;

    const PACKAGE_CLIENT_WP = 8;

    protected $table = 'gold_package';

    protected $fields = array(
        'id',
        'title',
        'money',
        'money_unit',
        'golds',
        'bonus',
        'client',
        'created_on',
    );

    protected $defaultFields = array(
        'id',
        'title',
        'money',
        'money_unit',
        'golds',
        'bonus',
        'client',
    );

    public function getRowsByClient($client, $columns = null)
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

        $sql = "SELECT {$fields} FROM `{$this->schema}`.`{$this->table}` WHERE `client`=:client ORDER BY `golds` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':client' => $client,
        ));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getClientMap()
    {
        return array(
            static::PACKAGE_CLIENT_GENERAL  => 'General',
            static::PACKAGE_CLIENT_WEB      => 'Web',
            static::PACKAGE_CLIENT_ANDROID  => 'Android',
            static::PACKAGE_CLIENT_IOS      => 'iOS',
            static::PACKAGE_CLIENT_WP       => 'WP',
        );
    }
}