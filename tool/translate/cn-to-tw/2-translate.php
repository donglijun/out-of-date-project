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

require_once __DIR__ . '/lib/ZhConversion.php';

function loadTranslateTable()
{
    global $db;

    $result = array();
    $select_sql = 'SELECT `content_from`,`content_to` FROM `translate_table`';
    $stmt = $db->query($select_sql);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result[$row['content_from']] = $row['content_to'];
    }

    return $result;
}

function translateWord($data)
{
    global $translateTable;

    return strtr($data, $translateTable);
}

function translateChar($data)
{
    global $zh2Hant, $zh2TW;

    return strtr(strtr($data, $zh2TW), $zh2Hant);
}

$params = array();
$params = parseArgv($argv);

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$translateTable = loadTranslateTable();

$min = 29;
$max = 204;
$select_sql = 'SELECT `d`.`id`,`d`.`title`,`de`.`description` FROM `deck` AS d LEFT JOIN `deck_extra` AS de ON d.`id`=de.`deck` WHERE `d`.`id` BETWEEN :min AND :max';
$insert_sql = 'INSERT INTO `tmp` (`id`,`title`,`description`,`old_title`,`old_description`) VALUES(:id,:title,:description,:old_title,:old_description)';
$stmt2 = $db->prepare($insert_sql);
$stmt = $db->prepare($select_sql);
$stmt->execute(array(
    ':min'  => $min,
    ':max'  => $max,
));
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $id = $row['id'];
    $title = translateChar(translateWord($row['title']));
    $description = translateChar(translateWord($row['description']));

    $stmt2->execute(array(
        ':id'               => $id,
        ':title'            => $title,
        ':description'      => $description,
        ':old_title'        => $row['title'],
        ':old_description'  => $row['description'],
    ));
}
