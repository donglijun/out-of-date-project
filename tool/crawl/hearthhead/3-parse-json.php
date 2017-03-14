<?php
set_time_limit(0);

function parseArgv($argv)
{
    $params = array();
    array_shift($argv);
    foreach ($argv as $item) {
        list($k, $v) = explode('=', $item, 2);
        $params[$k] = $v;
    }
    return $params;
}

$config = array(
    'mysql' => array(
        'host'      => '127.0.0.1',
        'port'      => 3306,
        'dbname'    => 'mkjogo_hearthstone',
        'username'  => 'root',
        'password'  => 'root',
    ),
);

function get_deck_ids($db)
{
    $result = array();
    $select_sql = 'SELECT deck_id FROM `tbl_hearthhead`';

    if ($stmt = $db->query($select_sql)) {
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[] = $row['deck_id'];
        }
    }

    return $result;
}

$params = array();
$params = parseArgv($argv);
$in = isset($params['in']) && file_exists($params['in']) ? $params['in'] : die("Require 'in' parameter.\n");

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Processing {$in} ... ";

$deck_ids = get_deck_ids($db);

$insert_sql = 'INSERT INTO `tbl_hearthhead_json` (`deck_id`,`name`,`url`,`timestamp`,`class`,`ncards`,`description`,`strategy`,`powernote`) VALUES(:deck_id,:name,:url,:timestamp,:class,:ncards,:description,:strategy,:powernote)';

$data = file_get_contents($in);
$data = json_decode($data, true);

if ($data) {
    $stmt = $db->prepare($insert_sql);

    foreach ($data as $deck) {
        if (in_array($deck['id'], $deck_ids)) {
            $stmt->execute(array(
                ':deck_id'      => $deck['id'],
                ':name'         => $deck['name'] ?: '',
                ':url'          => $deck['url'] ?: '',
                ':timestamp'    => $deck['timestamp'] ?: 0,
                ':class'        => $deck['classs'] ?: 0,
                ':ncards'       => $deck['ncards'] ?: 0,
                ':description'  => $deck['description'] ?: '',
                ':strategy'     => $deck['strategy'] ?: '',
                ':powernote'    => $deck['powernote'] ?: '',
            ));
        }
    }

    echo count($data) . "\n";
} else {
    echo "failed\n";
}