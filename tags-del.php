<?php

ini_set('max_execution_time', 1800);
require_once 'functions.php';

$subdomain = 'newdemonew';
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c40';
$tags_names = ['tag', 'taest']; // Теги к удалению

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {
	$rows = 3;
	$offset = 3;

    $updates_array = make_updates_array($subdomain, $rows, $offset, $tags_names);
//    echo '<pre>';
//    var_dump($updates_array);
//    echo '</pre>';

// Удаление тегов и добавление примечаний
	foreach ($updates_array as $updates_array_item) {
	    if (count($updates_array_item[0]) == 0) continue;
		echo count($updates_array_item[0]).' - ';
		echo count($updates_array_item[1]).'<br>';
		sleep(1);
		$leads_data = array(
			'update' => $updates_array_item[0],
		);
		$leads_result = del_tags($subdomain, $leads_data);
		echo '<pre>';
		var_dump($leads_result);
		echo '</pre>';
		echo '<br>';
		$notes_data = array(
			'add' => $updates_array_item[1],
		);
		$notes_result = notes_add($subdomain, $notes_data);
		echo '<pre>';
		var_dump($notes_result);
		echo '</pre>';
		echo '<hr>';
	}
} else {
	return $result;
}
