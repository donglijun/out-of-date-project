<?php
/**
 * Class MySQL_BaseModel
 *
 * A simple class to access MySQL table
 */
class MySQL_BaseModel
{
    const BATCH_THRESHOLD = 100;

    /**
     * @var object PDO object
     */
    protected $db;

    /**
     * @var string Full database name
     */
    protected $schema;

    /**
     * @var string Full table name
     */
    protected $table;

    /**
     * @var array Available fields
     */
    protected $fields = array();

    /**
     * @var array Default returned fields when query
     */
    protected $defaultFields = array();

    protected $batchInsertData = array();

    protected $batchReplaceData = array();

    /**
     * Do some initialization
     */
    protected function init() {}

    public function __construct($db = null)
    {
        if (is_object($db)) {
            $this->db = $db;
        } else {
//            $this->db = Yaf_Registry::get('db');
            $this->db = Daemon::getDb();
        }

        if (!$this->schema) {
            $this->schema = $this->getDatabaseName();
        }

        $this->init();
    }

    /**
     * Format identifier like table name or field name
     *
     * @param string $data
     * @return string
     */
    public static function quoteIdentifier($data)
    {
        $data = str_replace('`', '', $data);
        $data = explode('.', $data);

        foreach ($data as &$val) {
            $val = '`' . $val . '`';
        }

        return implode('.', $data);
    }

    /**
     * Retrieve a global model object
     *
     * @param object $db
     * @return object
     */
    public static function getModel($db = null)
    {
        $result = null;

        $class = get_called_class();
        if (!($result = Yaf_Registry::get($class))) {
            $result = new $class($db);

            Yaf_Registry::set($class, $result);
        }

        return $result;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getTableName()
    {
        return $this->table;
    }

    public function getDefaultFields()
    {
        return $this->defaultFields;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getRange($column, $where = null)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $where = $where ? 'WHERE ' . $where : '';

            $sql = "SELECT MIN({$column}) AS `min`, MAX({$column}) AS `max` FROM `{$this->schema}`.`{$this->table}` {$where}";

            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getRowsByRange($column, $start, $stop)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE {$column}>=:start AND {$column}<=:stop";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':start'    => $start,
                ':stop'     => $stop,
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getRowsByStep($column, $start, $end, $limit)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $limit = (int) $limit;
            $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE {$column}>=:start AND {$column}<=:end ORDER BY {$column} ASC LIMIT {$limit}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':start'    => $start,
                ':end'      => $end,
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function truncate()
    {
        $sql = "TRUNCATE TABLE `{$this->schema}`.`{$this->table}`";

        $this->db->exec($sql);

        return $this;
    }

    public function indexExists($keyName)
    {
        $sql = "SHOW INDEX FROM `{$this->schema}`.`{$this->table}` WHERE `key_name`=:key_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':key_name' => $keyName,
        ));

        return $stmt->fetchColumn() ? true : false;
    }

    public function multiInsert($data)
    {
        $fields = $values = array();

        foreach ($data as $row) {
            $setFieldFlag = empty($fields);
            $items = array();

            foreach ($row as $key => $val) {
                if (in_array($key, $this->fields)) {
                    if ($setFieldFlag) {
                        $fields[] = $this->quoteIdentifier($key);
                    }

                    $items[] = $this->db->quote($val);
                }
            }

            $values[] = '(' . implode(',', $items) . ')';
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $sql = "INSERT INTO `{$this->schema}`.{$this->table} ({$fields}) VALUES {$values}";

        $result = $this->db->exec($sql);

        return $result;
    }

    public function batchInsert($data, $threshold = null)
    {
        $threshold = $threshold ?: static::BATCH_THRESHOLD;

        $this->batchInsertData[] = $data;

        if (count($this->batchInsertData) >= $threshold) {
            $this->multiInsert($this->batchInsertData);

            $this->batchInsertData = array();
        }

        return true;
    }

    public function completeBatchInsert()
    {
        if ($this->batchInsertData) {
            $this->multiInsert($this->batchInsertData);

            $this->batchInsertData = array();
        }

        return true;
    }

    public function multiReplace($data)
    {
        $fields = $values = array();

        foreach ($data as $row) {
            $setFieldFlag = empty($fields);
            $items = array();

            foreach ($row as $key => $val) {
                if (in_array($key, $this->fields)) {
                    if ($setFieldFlag) {
                        $fields[] = $this->quoteIdentifier($key);
                    }

                    $items[] = $this->db->quote($val);
                }
            }

            $values[] = '(' . implode(',', $items) . ')';
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $sql = "REPLACE INTO `{$this->schema}`.{$this->table} ({$fields}) VALUES {$values}";

        $result = $this->db->exec($sql);

        return $result;
    }

    public function batchReplace($data, $threshold = null)
    {
        $threshold = $threshold ?: static::BATCH_THRESHOLD;

        $this->batchReplaceData[] = $data;

        if (count($this->batchReplaceData) >= $threshold) {
            $this->multiReplace($this->batchReplaceData);

            $this->batchReplaceData = array();
        }

        return true;
    }

    public function completeBatchReplace()
    {
        if ($this->batchReplaceData) {
            $this->multiReplace($this->batchReplaceData);

            $this->batchReplaceData = array();
        }

        return true;
    }

    public function getDatabaseName()
    {
        $sql = 'SELECT DATABASE()';

        return $this->db->query($sql)->fetchColumn();
    }

    public function ping()
    {
        return sprintf('`%s`.`%s`@`%s`', $this->schema, $this->table, $this->getDatabaseName());
    }

    public function getDistinct($column, $where = null)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $where = $where ? 'WHERE ' . $where : '';

            $sql = "SELECT DISTINCT({$column}) FROM `{$this->schema}`.`{$this->table}` {$where} ORDER BY {$column} ASC";

            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        return $result;
    }

    public function turnOffCache()
    {
        $sql = 'SET SESSION query_cache_type=OFF';

        return $this->db->exec($sql);
    }
}