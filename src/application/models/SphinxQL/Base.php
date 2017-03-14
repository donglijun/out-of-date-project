<?php
class SphinxQL_BaseModel
{
    protected $db;

    protected $index;

    protected $sort;

    protected $fields = array();

    protected $mva = array();

    /**
     * Do some initialization
     */
    protected function init() {}

    public function __construct($db = null)
    {
        if (is_object($db)) {
            $this->db = $db;
        } else {
            $this->db = Daemon::getSphinxQL();
        }

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

    public function getIndex()
    {
        return $this->index;
    }

    public function getDb()
    {
        return $this->db;
    }

    protected function quoteIdentifier($data)
    {
        $data = str_replace('`', '', $data);
        $data = explode('.', $data);

        foreach ($data as &$val) {
            $val = '`' . $val . '`';
        }

        return implode('.', $data);
    }

    protected function parseMetaRowset($rowset)
    {
        $result = array();

        if (is_array($rowset)) {
            foreach ($rowset as $row) {
                if (is_array($row) && count($row) == 2) {
                    $key = array_shift($row);
                    $val = array_shift($row);

                    $result[$key] = $val;
                }
            }
        }

        return $result;
    }

    protected function buildSelectSQL($params, $opts)
    {
        $select = isset($params['select']) ? $params['select'] : '*';
        $index = isset($params['index']) ? $params['index'] : $this->index;

        $where = array();
        if (isset($params['q']) && $params['q']) {
            $where[] = sprintf("MATCH('%s')", $params['q']);
        }
        if (isset($params['filter'])) {
            $where[] = $params['filter'];
        }
        if (isset($params['!filter'])) {
            $where[] = $params['!filter'];
        }
        if (isset($params['range'])) {
            $where[] = $params['range'];
        }
        if (isset($params['!range'])) {
            $where[] = $params['!range'];
        }
        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sort = isset($params['sort']) ? $params['sort'] : $this->sort;
        $sort = 'ORDER BY ' . $sort;

        $limit = '';
        if (isset($params['offset']) && isset($params['limit'])) {
            $limit = sprintf('LIMIT %d, %d', $params['offset'], $params['limit']);
        }

        $groupby = '';
        if (isset($params['groupby'])) {
            $groupby = 'GROUP BY ' . $params['groupby'];
        }

        $option = array();
        if ($opts) {
            foreach ($opts as $key => $val) {
                $option[] = $key . '=' . $val;
            }

            $option = implode(',', $option);
            $option = 'OPTION ' . $option;
        } else {
            $option = '';
        }

        $sql = "SELECT {$select} FROM {$index} {$where} {$groupby} {$sort} {$limit} {$option}";

        return $sql;
    }

    protected function buildFacetedSQL($params, $opts, $field)
    {
        $select = sprintf('%s,COUNT(*) AS group_count', $field);
        $index = isset($params['index']) ? Helper_Formatter_Sphinx::formatIndex($params['index']) : $this->index;

        $where = array();
        if (isset($params['q']) && $params['q']) {
            $where[] = sprintf("MATCH('%s')", $params['q']);
        }
        if (isset($params['filter'])) {
            unset($params['filter'][$field]);

            if ($params['filter']) {
                $where[] = Helper_Formatter_Sphinx::formatFilter($params['filter']);
            }
        }
        if (isset($params['!filter'])) {
            unset($params['!filter'][$field]);

            if ($params['!filter']) {
                $where[] = Helper_Formatter_Sphinx::formatFilter($params['!filter'], true);
            }
        }
        if (isset($params['range'])) {
            $where[] = Helper_Formatter_Sphinx::formatRange($params['range']);
        }
        if (isset($params['!range'])) {
            $where[] = Helper_Formatter_Sphinx::formatRange($params['!range'], true);
        }
        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sort = isset($params['sort']) ? Helper_Formatter_Sphinx::formatSort($params['sort']) : 'group_count DESC';
        $sort = 'ORDER BY ' . $sort;

        $limit = '';
        if (isset($params['offset']) && isset($params['limit'])) {
            $limit = sprintf('LIMIT %d, %d', $params['offset'], $params['limit']);
        }

        $groupby = '';
        $groupby = 'GROUP BY ' . $field;

        $option = array();
        if ($opts) {
            foreach ($opts as $key => $val) {
                $option[] = $key . '=' . $val;
            }

            $option = implode(',', $option);
            $option = 'OPTION ' . $option;
        } else {
            $option = '';
        }

        $sql = "SELECT {$select} FROM {$index} {$where} {$groupby} {$sort} {$limit} {$option}";

        return $sql;
    }

    protected function buildInsertSQL($data, $index = null)
    {
        $index = $index ?: $this->index;

        $fields = $values = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = in_array($key, $this->mva) ? $val : $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $sql = "INSERT INTO {$index} ({$fields}) VALUES({$values})";

        return $sql;
    }

    protected function buildReplaceSQL($data, $index = null)
    {
        $index = $index ?: $this->index;

        $fields = $values = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = in_array($key, $this->mva) ? $val : $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $sql = "REPLACE INTO {$index} ({$fields}) VALUES({$values})";

        return $sql;
    }

    protected function buildUpdateSQL($params)
    {
        $index = isset($params['index']) ? $params['index'] : $this->index;

        $set = array();

        foreach ($params['set'] as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = $this->quoteIdentifier($key) . '=' . in_array($key, $this->mva) ? $val : (int) $val;
            }
        }

        $set = implode(',', $set);

        $set = 'SET ' . $set;

        $where = array();

        if (isset($params['q'])) {
            $where[] = sprintf("MATCH('%s')", $params['q']);
        }
        if (isset($params['filter'])) {
            $where[] = $params['filter'];
        }
        if (isset($params['!filter'])) {
            $where[] = $params['!filter'];
        }
        if (isset($params['range'])) {
            $where[] = $params['range'];
        }
        if (isset($params['!range'])) {
            $where[] = $params['!range'];
        }
        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "UPDATE {$index} {$set} {$where}";

        return $sql;
    }

    public function search($params, $opts)
    {
        $result = array();

        $sql[] = $this->buildSelectSQL($params, $opts);
        $sql[] = 'SHOW META';
        $sql = implode(';', $sql);

        $stmt = $this->db->query($sql);

        if ($stmt) {
            $result['matches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($stmt->nextRowset()) {
                $result = array_merge($result, $this->parseMetaRowset($stmt->fetchAll(PDO::FETCH_ASSOC)));
            }
        }

        return $result;
    }

    public function faceted($params, $opts, $faceted)
    {
        $result = $sql = array();

        foreach ($faceted as $field) {
            $sql[] = $this->buildFacetedSQL($params, $opts, $field);
            $sql[] = 'SHOW META';
        }
        $sql = implode(';', $sql);

        $stmt = $this->db->query($sql);

        if ($stmt) {
            $data = $matches = $meta = array();

            do {
                $rowset = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($matches)) {
                    $matches = $rowset;
                } else if (empty($meta)) {
                    $meta = $this->parseMetaRowset($rowset);

                    $data['matches'] = $matches;
                    $data = array_merge($data, $meta);
                    $result[] = $data;

                    $data = array();
                    $matches = array();
                    $meta = array();
                }
            } while ($stmt->nextRowset());
        }

        return $result;
    }

    public function buildExcerpts($docs, $index, $words, $opts=array())
    {
        $result = $fragments = $data = array();

        if (is_string($docs)) {
            $data = $docs;
        } else if (is_array($docs)) {
            foreach ($docs as $doc) {
                $data[] = $this->db->quote($doc, PDO::PARAM_STR);
            }

            $data = sprintf('(%s)', implode(',', $data));
        }

        $fragments[] = $data;

        $fragments[] = $this->db->quote($index, PDO::PARAM_STR);

        $fragments[] = $this->db->quote($words, PDO::PARAM_STR);

        if ($opts) {
            foreach ($opts as $key => $val) {
                $val = is_numeric($val) ? $val : $this->db->quote($val, PDO::PARAM_STR);

                $opts[$key] = sprintf('%s AS %s', $val, $key);
            }

            $opts = implode(',', $opts);

            $fragments[] = $opts;
        }

        $sql = sprintf('CALL SNIPPETS(%s)', implode(',', $fragments));

        $stmt = $this->db->query($sql);

        if ($stmt) {
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }

        return $result;
    }

    public function insert($data, $index = null)
    {
        $sql = $this->buildInsertSQL($data, $index);
        $result = $this->db->exec($sql);

        return $result;
    }

    public function replace($data, $index = null)
    {
        $sql = $this->buildReplaceSQL($data, $index);
        $result = $this->db->exec($sql);

        return $result;
    }

    public function update($params)
    {
        $sql = $this->buildUpdateSQL($params);
        $result = $this->db->exec($sql);

        return $result;
    }

    public function multiInsert($data, $index = null)
    {
        $index = $index ?: $this->index;

        $fields = $values = array();

        foreach ($data as $row) {
            $setFieldFlag = empty($fields);
            $items = array();

            foreach ($row as $key => $val) {
                if (in_array($key, $this->fields)) {
                    if ($setFieldFlag) {
                        $fields[] = $this->quoteIdentifier($key);
                    }

                    $items[] = in_array($key, $this->mva) ? $val : $this->db->quote($val);
                }
            }

            $values[] = '(' . implode(',', $items) . ')';
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $sql = "INSERT INTO {$index} ({$fields}) VALUES {$values}";

        $result = $this->db->exec($sql);

        return $result;
    }

    public function multiReplace($data, $index = null)
    {
        $index = $index ?: $this->index;

        $fields = $values = array();

        foreach ($data as $row) {
            $setFieldFlag = empty($fields);
            $items = array();

            foreach ($row as $key => $val) {
                if (in_array($key, $this->fields)) {
                    if ($setFieldFlag) {
                        $fields[] = $this->quoteIdentifier($key);
                    }

                    $items[] = in_array($key, $this->mva) ? $val : $this->db->quote($val);
                }
            }

            $values[] = '(' . implode(',', $items) . ')';
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $sql = "REPLACE INTO {$index} ({$fields}) VALUES {$values}";

        $result = $this->db->exec($sql);

        return $result;
    }

    public function getRange($column, $where = null)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $where = $where ? 'WHERE ' . $where : '';

            $sql = "SELECT MIN({$column}) AS `min`, MAX({$column}) AS `max` FROM `{$this->index}` {$where}";

            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getRowsByRange($column, $start, $stop, $select = null)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $start  = (int) $start;
            $stop   = (int) $stop;
            $select = $select ?: '*';

            $sql = "SELECT {$select} FROM `{$this->index}` WHERE {$column}>={$start} AND {$column}<={$stop}";
            $stmt = $this->db->query($sql);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getRowsByStep($column, $start, $end, $limit, $select = null)
    {
        $result = array();

        if (in_array($column, $this->fields)) {
            $column = $this->quoteIdentifier($column);
            $start  = (int) $start;
            $end    = (int) $end;
            $limit  = (int) $limit;
            $select = $select ?: '*';

            $sql = "SELECT {$select} FROM `{$this->index}` WHERE {$column}>={$start} AND {$column}<={$end} ORDER BY {$column} ASC LIMIT {$limit}";
            $stmt = $this->db->query($sql);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function turnOffCache()
    {
        $sql = 'SET SESSION query_cache_type=OFF';

        return $this->db->exec($sql);
    }
}