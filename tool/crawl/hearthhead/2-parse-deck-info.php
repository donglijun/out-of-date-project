<?php
require_once __DIR__ . '/simple_html_dom.php';

define('URL_HEARTHHEAD', 'http://www.hearthhead.com');
define('RARITY_BASIC', 2);
define('CATEGORY_BASIC', 1);
define('CATEGORY_CONSTRUCTED', 2);

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

function get_card_basic($db)
{
    $result = array();
    $select_sql = 'SELECT * FROM `tbl_card_basic`';

    if ($stmt = $db->query($select_sql)) {
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['card_name']] = array(
                'card_id'   => $row['card_id'],
                'rarity'    => $row['rarity'],
            );
        }
    }

    return $result;
}

function get_html($url)
{
    $data = '';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($curl);
    curl_close($curl);

    return $data;
}

function parse_cards($url, &$card_basic)
{
    $result = array();

    $html_content = get_html($url);
    if ($html = str_get_html($html_content)) {
        if ($deckguide = $html->find('div.deckguide', 0)) {
            if ($cardnotes = $deckguide->find('div.deckguide-cardnotes', 0)) {
                $result['powernote']  = $cardnotes->plaintext;
            }

            if ($cardblock = $deckguide->find('div.deckguide-cards-type')) {
                $result['card_count'] = 0;
                $result['category']   = CATEGORY_BASIC;

                foreach ($cardblock as $block) {
                    foreach ($block->find('li a') as $a) {
                        $id = $a->getAttribute('data-id');
                        $name = trim($a->plaintext);
                        $card_info = $card_basic[$name];

                        if ($card_info['rarity'] != RARITY_BASIC) {
                            $result['category'] = CATEGORY_CONSTRUCTED;
                        }

                        $count = (preg_match('|x2$|', trim($a->parent()->plaintext))) ? 2 : 1;
                        $result['card_count'] += $count;

                        $result['cards'][] = array(
                            'i' => $card_info['card_id'],
                            'c' => $count,
                            'k'  => 0,
                        );
                    }
                }
            }
        } else {
            echo "Invalid content in {$url}\n";
        }

        $html->clear();
    } else {
        echo "Failed to get content from {$url}\n";
    }

    return $result;
}

$params = array();
$params = parseArgv($argv);

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$card_basic = get_card_basic($db);

$select_sql = 'SELECT `id`,`deck_url` FROM `tbl_hearthhead` WHERE `check_status`=0';
$update_sql = 'UPDATE `tbl_hearthhead` SET `powernote`=:powernote,`category`=:category,`cards`=:cards,`card_count`=:card_count,`check_status`=:check_status WHERE `id`=:id';

if ($stmt = $db->query($select_sql)) {
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $result = parse_cards($row['deck_url'], $card_basic);

        if ($result) {
            $stmt = $db->prepare($update_sql);
            $stmt->execute(array(
                ':powernote'    => $result['powernote'],
                ':category'     => $result['category'],
                ':cards'        => json_encode($result['cards']),
                ':card_count'   => $result['card_count'],
                ':check_status' => 1,
                ':id'           => $row['id'],
            ));
        }
    }
}
