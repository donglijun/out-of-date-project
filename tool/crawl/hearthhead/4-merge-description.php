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

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$update_description_and_strategy_sql = <<<EOT
UPDATE `tbl_hearthhead` AS `h`, `tbl_hearthhead_json` AS `hj`
SET `h`.`description`=`hj`.`description`, `h`.`strategy`=`hj`.`strategy`
WHERE `h`.`deck_id`=`hj`.`deck_id`
EOT;

$update_powernote_sql = <<<EOT
UPDATE `tbl_hearthhead` AS `h`, `tbl_hearthhead_json` AS `hj`
SET `h`.`powernote`=`hj`.`powernote`
WHERE `h`.`deck_id`=`hj`.`deck_id` AND `h`.`powernote`='' AND `hj`.`powernote`<>''
EOT;

$select_sql = "SELECT `id`,`description`,`powernote`,`strategy` FROM `tbl_hearthhead` WHERE `final_description`=''";

$update_final_description_sql = 'UPDATE `tbl_hearthhead` SET `final_description`=:final_description WHERE `id`=:id';

//$db->exec($update_description_and_strategy_sql);
//$db->exec($update_powernote_sql);

$stmt = $db->query($select_sql);
$stmt2 = $db->prepare($update_final_description_sql);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $final_description = array();

    if ($row['description']) {
        $final_description[] = 'Description';
        $final_description[] = $row['description'];
    }

    if ($row['powernote']) {
        $final_description[] = 'Powernote';
        $final_description[] = $row['powernote'];
    }

    if ($row['strategy']) {
        $final_description[] = 'Strategy';
        $final_description[] = $row['strategy'];
    }

    if ($final_description) {
        $final_description = implode("\n", $final_description);

        $stmt2->execute(array(
            ':id'                   => $row['id'],
            ':final_description'    => $final_description,
        ));
    }
}
