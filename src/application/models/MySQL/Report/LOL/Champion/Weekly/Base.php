<?php
class MySQL_Report_LOL_Champion_Weekly_BaseModel extends MySQL_Report_LOL_Champion_BaseModel
{
    public static function key($timestamp = null)
    {
        return date('oW', $timestamp ?: time());
    }

    protected function formatSummary($rowset)
    {
        $result = array();

        if ($rowset) {
            foreach ($rowset as $row) {
                $result[$row['mode']] = $row;
            }
        }

        return $result;
    }

    public function getLatestSummary($champion)
    {
        $result = array();

        $parameters = array(
            ':champion' => (int) $champion,
            ':date'     => static::key(strtotime('-7 day')),
        );

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `champion`=:champion";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

//        $result = $this->formatSummary($result);

        return $result;
    }

    public function getMostPopular($mode, $limit = 10)
    {
        $parameters = array(
            ':date' => static::key(strtotime('-7 day')),
            ':mode' => (int) $mode,
        );

        $limit = (int) $limit;

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `mode`=:mode AND `champion`>0 ORDER BY `total` DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getLeastPopular($mode, $limit = 10)
    {
        $parameters = array(
            ':date' => static::key(strtotime('-7 day')),
            ':mode' => (int) $mode,
        );

        $limit = (int) $limit;

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `mode`=:mode AND `champion`>0 ORDER BY `total` ASC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getHighestWinRate($mode, $limit = 10)
    {
        $parameters = array(
            ':date' => static::key(strtotime('-7 day')),
            ':mode' => (int) $mode,
        );

        $limit = (int) $limit;

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `mode`=:mode AND `champion`>0 AND `total`>0 ORDER BY `win_rate` DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getLowestWinRate($mode, $limit = 10)
    {
        $parameters = array(
            ':date' => static::key(strtotime('-7 day')),
            ':mode' => (int) $mode,
        );

        $limit = (int) $limit;

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `mode`=:mode AND `champion`>0 AND `total`>0 ORDER BY `win_rate` ASC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getMostPicked($mode, $limit = 10)
    {
        $parameters = array(
            ':date' => static::key(strtotime('-7 day')),
            ':mode' => (int) $mode,
        );

        $limit = (int) $limit;

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `mode`=:mode AND `champion`>0 AND `ranked_total`>0 ORDER BY `ranked_pick_rate` DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getMostBanned($mode, $limit = 10)
    {
        $parameters = array(
            ':date' => static::key(strtotime('-7 day')),
            ':mode' => (int) $mode,
        );

        $limit = (int) $limit;

        $sql = "SELECT * FROM `{$this->schema}`.`{$this->table}` WHERE `date`=:date AND `mode`=:mode AND `champion`>0 AND `ranked_total`>0 ORDER BY `ranked_ban_rate` DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);

        if (!($result = $stmt->fetchAll(PDO::FETCH_ASSOC))) {
            $parameters[':date'] = static::key(strtotime('-14 day'));
            $stmt->execute($parameters);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }
}