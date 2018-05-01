<?php

$params = [
    '' => 'help',
    's:' => 'subdomain:',
    'l::' => 'login::',
    'h::' => 'hash::',
    'c::' => 'count::',
    't:' => 'tags:'
];

// Default values
$login = 'amolyakov@team.amocrm.com';
$hash = 2345246354345456456456;
$count = 500;
$errors = [];

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
usage: php test.php [--help] [-s|--subdomain=subname] [-l|--login=login@yandex.ru] [-h|--hash=12345678900987654321] [-c|--count=250] [-t|--tags=tag1,tag2,tag3

Options:
            --help      Show this message
        -s  --subdomain Account subdomain name
        -l  --login     User email (default: amolyakov@team.amocrm.com)
        -h  --hash      Api key (default: api key for amolyakov@team.amocrm.com)
        -c  --count     Count of leads updating in one iteration
        -t  --tags      Tags to delete list
Example:
        php test.php --subdomain=subname --login=login@yandex.ru --hash=12345678900987654321 --count=500 --tags=tag1,tag2,tag3
";
    if ( $errors )
    {
        $help .= 'Errors:' . PHP_EOL . implode("\n", $errors) . PHP_EOL;
    }
    die($help);
}

var_dump($options);



