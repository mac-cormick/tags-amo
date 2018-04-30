<?php

ini_set('max_execution_time', 1800);

require_once "const.php";
require_once 'functions.php';

$subdomain = 'newtestdemo';
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c40';
$tags_names = ['tag', 'tag1', 'no-tag', '2454-234', 'new-tag']; // Теги к удалению

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {
	$rows = 500;
	$offset = 500;
    $count = 5000;

    $prepare_updates = prepare_updates_files($subdomain, $rows, $offset, $tags_names); // Запись данных для запросов апдейта сделок и добавления примечаний в json файлы
	if ($prepare_updates) {
		$make_updates = make_updates($subdomain, $count);
	} else {
		echo "Ошибки записи файлов\n";
	}
} else {
	echo "Ошибка авторизации\n";
	return $result;
}
