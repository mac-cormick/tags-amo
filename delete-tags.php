<?php

require_once __DIR__ . '/functions.php';

// Параметры по умолчанию
$count = 500;
$errors = [];

// Получение параметров
$params = [
    '' => 'help',
    's:' => 'subdomain:',
    'l:' => 'login::',
    'h:' => 'hash::',
    'c::' => 'count::'
];

$options = getopt(implode('', array_keys($params)), $params);

if (isset($options['subdomain']) || isset($options['s'])) {
    $subdomain = isset( $options['subdomain'] ) ? $options['subdomain'] : $options['s'];
} else {
    $errors[] = 'subdomain required';
}

if (isset($options['login']) || isset($options['l'])) {
    $login = isset( $options['login'] ) ? $options['login'] : $options['l'];
} else {
    $errors[] = 'login required';
}

if (isset($options['hash']) || isset($options['h'])) {
    $hash = isset( $options['hash'] ) ? $options['hash'] : $options['h'];
} else {
    $errors[] = 'hash required';
}

if (isset($options['count']) || isset($options['c'])) {
    $count = isset( $options['count'] ) ? $options['count'] : $options['c'];
}

if (isset($options['help']) || count($errors))
{
    $help = "
usage: php delete-tags.php [--help] [-s|--subdomain=subname] [-l|--login=login@yandex.ru] [-h|--hash=12345678900987654321] [-c|--count=250]

Options:
            --help         Show this message
        -s  --subdomain    Account subdomain name
        -l  --login        User email (default: amolyakov@team.amocrm.com)
        -h  --hash         Api key (default: api key for amolyakov@team.amocrm.com)
        -c  --count        Count of leads updating in one iteration
Example:
        php delete-tags.php --subdomain=subname --login=login@yandex.ru --hash=12345678900987654321 --count=500
";
    if ($errors)
    {
        $help .= 'Errors:' . PHP_EOL . implode("\n", $errors) . PHP_EOL;
    }
    die($help);
}

// Авторизация
$auth_result = auth($subdomain, $login, $hash);

if ($auth_result === TRUE) {
    echo "Авторизация пройдена успешно\n";

    // Выполнение апдейта и доб. примечаний по $count сделок за итерацию
    if (file_exists(__DIR__ . "/update-files/tags-update.json")) {
        $make_updates = make_updates($subdomain, $count);
    } else {
        echo "Файл апдейта не существует\n";
    }
} else {
    echo "Ошибка авторизации\n";
    return $result;
}
