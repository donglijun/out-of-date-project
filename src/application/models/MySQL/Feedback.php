<?php
class MySQL_FeedbackModel extends MySQL_BaseModel
{
    const STATUS_NEW = 1;

    const STATUS_TRANSLATED = 2;

    protected $table = 'feedback';

    protected $errorTable = 'feedback_error';

    protected $fields = array(
        'id',
        'user',
        'lang',
        'os',
        'description',
        'translation',
        'log_path',
        'created_on',
        'translated_on',
        'translated_by',
        'status',
        'contact_way',
        'contact_info',
        'client',
        'ip',
    );

    protected $errorFields = array(
        'feedback',
        'message',
        'times',
    );

    protected $defaultFields = array(
        'id',
        'user',
        'lang',
        'os',
        'description',
        'translation',
        'log_path',
        'created_on',
        'translated_on',
        'translated_by',
        'status',
        'contact_way',
        'contact_info',
        'client',
        'ip',
    );

    public function getRow($id, $columns = null)
    {
        $result = $fields = array();

        if (null === $columns) {
            $columns = $this->defaultFields;
        } else if (is_string($columns)) {
            $columns =  array($columns);
        }

        $errors = in_array('errors', $columns);

        foreach ($columns as $column) {
            if (in_array($column, $this->fields)) {
                $fields[] = $this->quoteIdentifier($column);
            }
        }

        $fields = implode(',', $fields);

        if ($id) {
            if ($fields) {
                $sql = "SELECT {$fields} FROM {$this->table} WHERE `id`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id'   => $id,
                ));

                if (!($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                    $result = array();
                }
            }

            if ($errors) {
                $sql = "SELECT `message`,`times` FROM `{$this->errorTable}` WHERE `feedback`=:id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(array(
                    ':id' => $id,
                ));

                $result['errors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return $result;
    }

    public function insert($data)
    {
        $result = false;
        $fields = $values = $errorFields = $errorValues = $errors = array();

        // These fields should not be inserted
        unset($data['id']);

        // Auto-fill fields
        $data['status'] = self::STATUS_NEW;

        if (!isset($data['created_on'])) {
            $data['created_on'] = time();
        }

        if (isset($data['errors']) && is_array($data['errors'])) {
            $errors = $data['errors'];
        }

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $fields[] = $this->quoteIdentifier($key);
                $values[] = $this->db->quote($val);
            }
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        if ($fields) {
            try {
                $this->db->beginTransaction();

                $sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES({$values})";
                $this->db->exec($sql);
                $feedback = $this->db->lastInsertId();

                foreach ($errors as $val) {
                    $errorFields = array();
                    $errorValues = array();

                    $errorFields[] = $this->quoteIdentifier('feedback');
                    $errorValues[] = $feedback;

                    $errorFields[] = $this->quoteIdentifier('created_on');
                    $errorValues[] = $data['created_on'];

                    if (isset($val['message'])) {
                        $errorFields[] = $this->quoteIdentifier('message');
                        $errorValues[] = $this->db->quote($val['message']);
                    }

                    if (isset($val['times'])) {
                        $errorFields[] = $this->quoteIdentifier('times');
                        $errorValues[] = $this->db->quote($val['times']);
                    }

                    $errorFields = implode(',', $errorFields);
                    $errorValues = implode(',', $errorValues);
                    $sql = "INSERT INTO `{$this->errorTable}` ({$errorFields}) VALUES({$errorValues})";
                    $this->db->exec($sql);
                }

                $this->db->commit();

                $result = $feedback;
            } catch (Exception $e) {
                $this->db->rollBack();
                //@todo have a log
            }
        }

        return $result;
    }

    public function update($id, $data)
    {
        $result = false;

        // These fields should not be updated
        unset($data['id']);

        $set = $where = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->fields)) {
                $set[] = sprintf('%s=%s', $this->quoteIdentifier($key), $this->db->quote($val));
            }
        }
        $set = implode(',', $set);

        if ($set) {
            $sql = "UPDATE `{$this->table}` SET {$set} WHERE `id`=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':id'   => $id,
            ));

            $result = $stmt->rowCount();
        }

        return $result;
    }

    public function query($from, $to, $lang = null, $client = null, $message = null)
    {
        $result = $where = array();

        if ($from) {
            $where[] = "`f`.`created_on`>={$from}";
        }

        if ($to) {
            $where[] = "`f`.`created_on`<{$to}";
        }

        if ($lang) {
            $where[] = sprintf('`f`.`lang`=%s', $this->db->quote($lang));
        }

        if ($client) {
            $where[] = sprintf('`f`.`client`=%s', $this->db->quote($client));
        }

        if ($message) {
            $where[] = sprintf('`fe`.`message`=%s', $this->db->quote($message));
        }

        $where = implode(' AND ', $where);
        $where = $where ? "WHERE {$where}" : '';

        $sql = "SELECT `f`.* FROM `{$this->table}` AS `f`
                LEFT JOIN `{$this->errorTable}` AS `fe` ON `f`.`id`=`fe`.`feedback`
                {$where}
                ORDER BY `created_on` DESC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public function groupByMessage($from, $to, $lang = null, $client = null)
    {
        $result = $where = array();

        if ($from) {
            $where[] = "`f`.`created_on`>={$from}";
        }

        if ($to) {
            $where[] = "`f`.`created_on`<{$to}";
        }

        if ($lang) {
            $where[] = sprintf('`f`.`lang`=%s', $this->db->quote($lang));
        }

        if ($client) {
            $where[] = sprintf('`f`.`client`=%s', $this->db->quote($client));
        }

        $where = implode(' AND ', $where);
        $where = $where ? "WHERE {$where}" : '';

        $sql = "SELECT `fe`.`message`, SUM(`fe`.`times`) AS `times` FROM `{$this->table}` AS `f`
                LEFT JOIN `{$this->errorTable}` AS `fe` ON `f`.`id`=`fe`.`feedback`
                {$where}
                GROUP BY `fe`.`message`
                ORDER BY `times` DESC";
        $stmt = $this->db->query($sql);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['message']) {
                $row['message'] = $row['message'];
                $result[] = $row;
            }
        }

        return $result;
    }

    public function translate($id, $translation, $translated_by)
    {
        $data = array(
            'translation'   => $translation,
            'translated_by' => (int) $translated_by,
            'translated_on' => time(),
            'status'        => self::STATUS_TRANSLATED,
        );

        return $this->update($id, $data);
    }

    public function clear($from, $to)
    {
        $result = false;
        $where = array();

        try {
            $this->db->beginTransaction();

            if ($from) {
                $where[] = "`created_on`>={$from}";
            }

            if ($to) {
                $where[] = "`created_on`<{$to}";
            }

            $where = implode(' AND ', $where);

            $sql = "DELETE FROM `{$this->table}` WHERE {$where}";
            $sql_error = "DELETE FROM `{$this->errorTable}` WHERE {$where}";

            $this->db->exec($sql_error);

            $result = $this->db->exec($sql);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            //@todo have a log
        }

        return $result;
    }

    /**
     * Get all supported langs
     *
     * @return array
     */
    public function getLangMap()
    {
        return array(
            'en_US' => 'en_US',
            'zh_CN' => 'zh_CN',
            'zh_TW' => 'zh_TW',
            'pt_BR' => 'pt_BR',
            'ko_KR' => 'ko_KR',
            'es_ES' => 'es_ES',
            'tr_TR' => 'tr_TR',
            'pl_PL' => 'pl_PL',
            'it_IT' => 'it_IT',
            'es_MX' => 'es_MX',
            'de_DE' => 'de_DE',
            'ru_RU' => 'ru_RU',
            'fr_FR' => 'fr_FR',
        );
    }

    public function getClientMap()
    {
        return array(
            'hs'             => 'HS',
            'lol'            => 'LOL',
            'lolv2'          => 'LOL v2',
            'nikksy_android' => 'NIKKSY ANDROID',
            'nikksy_ios'     => 'NIKKSY IOS',
        );
    }

    public function getTodayTotal()
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `created_on`>=:created_on";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':created_on'   => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
        ));

        return (int) $stmt->fetchColumn();
    }

    public function getTodayUntranslatedByLang()
    {
        $sql = "SELECT `lang`, COUNT(`lang`) AS `count` FROM `{$this->table}` WHERE `created_on`>=:created_on GROUP BY `lang` ORDER BY `lang`";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':created_on'   => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
        ));

        return $stmt->fetchAll();
    }
}