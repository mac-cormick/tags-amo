<?php

ini_set('max_execution_time', 10800);

require_once 'functions.php';

// Параметры по умолчанию
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c40';
$count = 500;
$errors = [];

// Получение параметров
$params = [
    '' => 'help',
    's:' => 'subdomain:',
    'l::' => 'login::',
    'h::' => 'hash::',
    'c::' => 'count::',
    't:' => 'tags:'
];

$options = getopt(implode('', array_keys($params)), $params);

if (isset($options['subdomain']) || isset($options['s'])) {
    $subdomain = isset( $options['subdomain'] ) ? $options['subdomain'] : $options['s'];
} else {
    $errors[] = 'subdomain required';
}

if (isset($options['login']) || isset($options['l'])) {
    $login = isset( $options['login'] ) ? $options['login'] : $options['l'];
}

if (isset($options['hash']) || isset($options['h'])) {
    $hash = isset( $options['hash'] ) ? $options['hash'] : $options['h'];
}

if (isset($options['count']) || isset($options['c'])) {
    $count = isset( $options['count'] ) ? $options['count'] : $options['c'];
}

if (isset($options['tags']) || isset($options['t'])) {
    $tags = isset( $options['tags'] ) ? $options['tags'] : $options['t'];
} else {
    $errors[] = 'tags required';
}

if ( isset($options['help']) || count($errors) )
{
    $help = "
usage: php tags-del.php [--help] [-s|--subdomain=subname] [-l|--login=login@yandex.ru] [-h|--hash=12345678900987654321] [-c|--count=250] [-t|--tags=tag1,tag2,tag3

Options:
            --help      Show this message
        -s  --subdomain Account subdomain name
        -l  --login     User email (default: amolyakov@team.amocrm.com)
        -h  --hash      Api key (default: api key for amolyakov@team.amocrm.com)
        -c  --count     Count of leads updating in one iteration
        -t  --tags      Tags to delete list
Example:
        php tags-del.php --subdomain=subname --login=login@yandex.ru --hash=12345678900987654321 --count=500 --tags=tag1,tag2,tag3
";
    if ( $errors )
    {
        $help .= 'Errors:' . PHP_EOL . implode("\n", $errors) . PHP_EOL;
    }
    die($help);
}

$tags_names_array = explode(',', $tags);

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {
    echo "Авторизация пройдена успешно\n";
	$rows = 5;
	$offset = 5;

	// Формиирование файлов для апдейта и доб. примечаний
    $prepare_updates = prepare_updates_files($subdomain, $rows, $offset, $tags_names_array); // Запись данных для запросов апдейта сделок и добавления примечаний в json файлы
	// Выполнение апдейта и доб. примечаний по $count сделок за итерацию
    if ($prepare_updates) {
		$make_updates = make_updates($subdomain, $count);
	} else {
		echo "Ошибки записи файлов\n";
	}
} else {
	echo "Ошибка авторизации\n";
	return $result;
}
