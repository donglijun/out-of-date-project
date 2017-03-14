<?php
class Mongo_CollectionModel
{
    protected $client;

    protected $dbName = '';

    protected $collectionName = '';

    protected $db;

    protected $collection;

    /**
     * Do some initialization
     */
    protected function init() {}

    public function __construct($client = null)
    {
        if (is_object($client)) {
            $this->client = $client;
        } else {
            $this->client = Daemon::getMongoClient();
        }

        if ($this->dbName) {
            $this->db = $this->client->selectDB($this->dbName);

            if ($this->collectionName) {
                $this->collection = $this->db->selectCollection($this->collectionName);
            }
        }

        $this->init();
    }

    public static function getModel($client = null)
    {
        $result = null;

        $class = get_called_class();
        if (!($result = Yaf_Registry::get($class))) {
            $result = new $class($client);

            Yaf_Registry::set($class, $result);
        }

        return $result;
    }

    public function getDB()
    {
        return $this->db;
    }

    public function getDBName()
    {
        return $this->dbName;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getCollectionName()
    {
        return $this->collectionName;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->collection, $name)) {
            return call_user_func_array(array($this->collection, $name), $arguments);
        } else {
            throw new Exception(sprintf('The required method "%s" does not exist for %s', $name, get_class($this)));
        }
    }
}