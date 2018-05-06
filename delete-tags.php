<?php

require_once __DIR__ . '/functions.php';

// Параметры по умолчанию
$count = 500;
$errors = [];
$position = null;

// Получение параметров
$params = [
    '' => 'help',
    's:' => 'subdomain:',
    'l:' => 'login:',
    'h:' => 'hash:',
    'c::' => 'count::',
    'p::' => 'position::'
];

$options = getopt(implode('', array_keys($params)), $params);

if (isset($options['subdomain']) || isset($options['s'])) {
    $subdomain = isset($options['subdomain']) ? $options['subdomain'] : $options['s'];
} else {
    $errors[] = 'subdomain required';
}

if (isset($options['login']) || isset($options['l'])) {
    $login = isset($options['login']) ? $options['login'] : $options['l'];
} else {
    $errors[] = 'login required';
}

if (isset($options['hash']) || isset($options['h'])) {
    $hash = isset($options['hash']) ? $options['hash'] : $options['h'];
} else {
    $errors[] = 'hash required';
}

if (isset($options['count']) || isset($options['c'])) {
    $count = isset($options['count']) ? $options['count'] : $options['c'];
}

if (isset($options['position']) || isset($options['p'])) {
    $position = isset($options['position']) ? $options['position'] : $options['p'];
}

if (isset($options['help']) || count($errors))
{
    $help = "
usage: php delete-tags.php [--help] [-s|--subdomain=subname] [-l|--login=login@yandex.ru] [-h|--hash=12345678900987654321] [-c|--count=250] [-p|--position=35000]

Options:
            --help         Показать это сообщение
        -s  --subdomain    Субдомен аккаунта
        -l  --login        Логин\E-mail пользователя
        -h  --hash         API-ключ
        -c  --count        Количество сделок к апдейту за один запрос
        -p  --position     Смещение дескриптора в файле апдейта, с кот. нужно возобновить выполнение(в случае падения скрипта в процессе выполнения)
Example:
        php delete-tags.php --subdomain=subname --login=login@yandex.ru --hash=12345678900987654321 --count=500 --position=0
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
        $dir = 'update-files';
        $make_updates_results = make_updates($subdomain, $dir);
    } else {
        echo "Файл апдейта не существует\n";
    }

    // Выполнение второго прохода при наличии ошибок апдейта
    if (count($make_updates_results)) {
        $dir = 'run-again';
        if (file_exists(__DIR__ . "/run-again/tags-update.json")) {
            echo "Есть ошибки апдейта. Запуск прохода скрипта по файлу неудачных апдейтов\n";
            $run_again_updates_results = make_updates($subdomain, $dir, false);
        }
    } else {
        echo "Нет удачных результатов апдейта\n";
    }
} else {
    echo "Ошибка авторизации\n";
    return $result;
}
