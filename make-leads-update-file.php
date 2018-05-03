<?php

require_once __DIR__ . "/functions.php";

// Параметры по умолчанию
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c40';
$rows = 500;
$errors = [];
$offset = 0;

// Получение параметров
$params = [
	''    => 'help',
	's:'  => 'subdomain:',
	'l::' => 'login::',
	'h::' => 'hash::',
	'c::' => 'rows::',
	't:'  => 'tags:',
	'o::' => 'offset'
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

if (isset($options['rows']) || isset($options['c'])) {
	$rows = isset( $options['rows'] ) ? $options['rows'] : $options['c'];
}

if (isset($options['tags']) || isset($options['t'])) {
	$tags = isset( $options['tags'] ) ? $options['tags'] : $options['t'];
} else {
	$errors[] = 'tags required';
}

if (isset($options['offset']) || isset($options['o'])) {
	$rows = isset( $options['offset'] ) ? $options['offset'] : $options['o'];
}

if ( isset($options['help']) || count($errors) )
{
	$help = "
usage: php make-leads-update-file.php [--help] [-s|--subdomain=subname] [-l|--login=login@yandex.ru] [-h|--hash=12345678900987654321] [-r|--rows=250] [-o|--offset=500] [-t|--tags=tag1,tag2,tag3

Options:
            --help         Показать это сообщение
        -s  --subdomain    Субдомен аккаунта
        -l  --login        Логин\E-mail пользователя
        -h  --hash         API-ключ
        -r  --rows         Количество сделок, получаемых одной итерацией
        -o --offset        Параметр limit_offset (если скрипт упадет во время получения сделок)
Example:
        php make-leads-update-file.php --subdomain=subname --login=login@yandex.ru --hash=12345678900987654321 --rows=500 --offset=35000 --tags=tag1,tag2,tag3
";
	if ($errors)
	{
		$help .= 'Errors:' . PHP_EOL . implode("\n", $errors) . PHP_EOL;
	}
	die($help);
}

$tags_names = explode(',', $tags);

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {
	echo "Авторизация пройдена успешно\n";

	// Формиирование файлов для апдейта и доб. примечаний
	$leads_update_count = prepare_leads_update_file($subdomain, $rows, $tags_names); // Запись данных для запросов апдейта сделок и добавления примечаний в json файлы
	if (($leads_update_count) > 0) {
		echo "Всего сделок к апдейту: " . $leads_update_count ."\n";
	} else {
		echo "Сделок к апдейту: 0\n";
	}
} else {
	echo "Ошибка авторизации\n";
	return $result;
}
