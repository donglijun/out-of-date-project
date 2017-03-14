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

function parse_card_xml($path)
{
	$result = array(
        'card_id'   => '',
        'card_name' => '',
        'rarity'    => 0,
        'type'      => 0,
        'class'     => 0,
        'cost'      => 0,
        'attack'    => 0,
        'health'    => 0,
    );

	$entity = simplexml_load_file($path);
	$result['card_id'] = (string) $entity->attributes()->CardID;

	foreach ($entity->Tag as $tag) {
		$attrs = $tag->attributes();
		$name = (string) $attrs->name;

        switch ($name) {
            case 'CardName':
                $result['card_name'] = (string) $tag->enUS;
                break;
            case 'Rarity':
                $result['rarity'] = (int) $attrs->value;
                break;
            case 'CardType':
                $result['type'] = (int) $attrs->value;
                break;
            case 'Cost':
                $result['cost'] = (int) $attrs->value;
                break;
            case 'Atk':
                $result['attack'] = (int) $attrs->value;
                break;
            case 'Health':
                $result['health'] = (int) $attrs->value;
                break;
            case 'Class':
                $result['class'] = (int) $attrs->value;
                break;
        }

	}

	return $result;
}

$params = array();
$params = parseArgv($argv);

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['mysql']['host'], $config['mysql']['port'], $config['mysql']['dbname']);
$db = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$insert_sql = 'INSERT INTO `card` (`card_id`,`name`,`rarity`,`type`,`cost`,`attack`,`health`,`class`) VALUES(:card_id,:name,:rarity,:type,:cost,:attack,:health,:class)';

$xmls = glob(__DIR__ . '/data/xml/*.txt');

if (is_array($xmls)) {
	foreach ($xmls as $xml) {
        $card_info = parse_card_xml($xml);

		$stmt = $db->prepare($insert_sql);
		$stmt->execute(array(
			':card_id'		=> $card_info['card_id'],
			':name'	        => $card_info['card_name'],
			':rarity'		=> $card_info['rarity'],
            ':type'         => $card_info['type'],
            ':cost'         => $card_info['cost'],
            ':attack'       => $card_info['attack'],
            ':health'       => $card_info['health'],
            ':class'        => $card_info['class'],
		));
	}
}
