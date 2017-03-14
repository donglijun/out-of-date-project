<?php
class Redis_BaseModel
{
    const MAX_OFFSET = 4294967296;

    protected $db;

    protected $dbindex = 0;

    /**
     * Do some initialization
     */
    protected function init() {}

    public function __construct($db = null)
    {
        if (is_object($db)) {
            $this->db = $db;
        } else {
            $this->db = Daemon::getRedis();
        }

        $this->db->select($this->dbindex);

        $this->init();
    }

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

    public function getDb()
    {
        return $this->db;
    }

    public function getDbIndex()
    {
        return $this->dbindex;
    }

    public function selectDb()
    {
        $this->db->select($this->dbindex);
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->db, $name)) {
            return call_user_func_array(array($this->db, $name), $arguments);
        } else {
            throw new Exception(sprintf('The required method "%s" does not exist for %s', $name, get_class($this)));
        }
    }
}