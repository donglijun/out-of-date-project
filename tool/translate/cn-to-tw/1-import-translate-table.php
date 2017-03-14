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

$params = array();
$params = parseArgv($argv);
$in = isset($params['in']) && file_exists($params['in']) ? $params['in'] : die("Require 'in' parameter.\n");

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$lang_from  = 'cn';
$lang_to    = 'tw';
$insert_sql = 'INSERT INTO `translate_table` (`lang_from`,`lang_to`,`content_from`,`content_to`) VALUES(:lang_from,:lang_to,:content_from,:content_to)';
$stmt = $db->prepare($insert_sql);

$map = array();
if ($fh = fopen($in, 'rb')) {
    while (($data = fgetcsv($fh, 1000, ',')) !== false) {
        if (($cn = $data[0]) && ($tw = $data[1])) {
            $map[$cn] = $tw;
        }
    }

    foreach ($map as $cn => $tw) {
        $stmt->execute(array(
            ':lang_from'    => $lang_from,
            ':lang_to'      => $lang_to,
            ':content_from' => $cn,
            ':content_to'   => $tw,
        ));
    }

    fclose($fh);
}