<?php
require_once __DIR__ . '/simple_html_dom.php';

define('URL_HEARTHHEAD', 'http://www.hearthhead.com');

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
$class = isset($params['class']) ? $params['class'] : die("Require 'class' parameter.\n");

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// $pattern = '|^.+CDATA.+\s+new.+data:\s+(.+)\}\);\s+\/\/\]\]>\s.$|';
// $content = trim(file_get_contents($inFile));

// if (preg_match($pattern, $content, $matches)) {
//     $data = sprintf('{"data": %s}', $matches[1]);
// }

$insert_sql = 'INSERT INTO `tbl_hearthhead` (`class`,`title`,`game_version`,`deck_url`,`author`,`deck_id`) VALUES(:class,:title,:game_version,:deck_url,:author,:deck_id)';

$html = file_get_html($in);

$listview = $html->find('table.listview-mode-default', 0);

$i = 0;
$pattern_url = '|^(.*deck=(\d+))\D+|';

foreach ($listview->find('tr') as $row) {
    if ($i++ < 1) {
        continue;
    }

    $a = $row->find('table.decks-list-name a', 0);
    if ($a) {
        if (preg_match($pattern_url, $a->href, $matches)) {
            $href = URL_HEARTHHEAD . $matches[1];
            $deck_id = $matches[2];
        } else {
            $href = $a->href;
            $deck_id = 0;
        }
        $title = trim($a->innertext);

        $author = trim($row->children(1)->plaintext);
        $game_version = trim($row->children(6)->plaintext);

        $stmt = $db->prepare($insert_sql);
        $stmt->execute(array(
            ':class'        => $class,
            ':title'        => $title,
            ':game_version' => $game_version,
            ':deck_url'     => $href,
            ':author'       => $author,
            ':deck_id'      => $deck_id,
        ));
    }
}

$html->clear();


