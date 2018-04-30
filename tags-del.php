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
	$i = 0;

    $make_updates = make_updates_files($subdomain, $rows, $offset, $tags_names); // Запись данных для запросов апдейта сделок и добавления примечаний в json файлы
	if ($make_updates) {
		// Получение массивов для апдейта из json файлов
        $leads_update_file_open = fopen(__DIR__ . '/files/tags-update.json', 'r');
        $notes_add_file_open = fopen(__DIR__ . '/files/notes-add.json', 'r');
        if ($leads_update_file_open && $notes_add_file_open) {
            $leads_update_item = TRUE;
            $notes_add_item = TRUE;
            while ($leads_update_item && $notes_add_item) {
                $leads_update_array = [];
                $notes_add_array = [];
                $i++;
                sleep(1);
                for ($x=0; $x<500; $x++) {
                    $leads_update_item = fgets($leads_update_file_open);
                    if ($leads_update_item) {
                        $leads_update_item_array = json_decode($leads_update_item, true);
                        $leads_update_item_array['updated_at'] = time(); // Дозапись параметра updated_at в массив для апдейта
                        $leads_update_array[] = $leads_update_item_array;
                    }
                    $notes_add_item = fgets($notes_add_file_open);
                    if ($notes_add_item) {
                        $notes_add_array[] = json_decode($notes_add_item, true);
                    }
                }
//                var_dump($leads_update_array);
//                var_dump($notes_add_array);
                if (count($leads_update_array) == 0) break;
                $leads_data = array(
                    'update' => $leads_update_array,
                );
                $notes_data = array(
                    'add' => $notes_add_array,
                );
                echo "Удаление тегов из $x сделок...\n";
                $leads_result = del_tags($subdomain, $leads_data);
                if (!is_array($leads_result)) {
                    // При ошибке запроса пишем номер запроса и ошибки, пропускаем добавление примечаний
                    $leads_update_errors_put_result_txt = file_put_contents(__DIR__ . "/files/leads-update-errors.txt", $i . ' - ' . $leads_result . "\n", FILE_APPEND);
                    echo $i . " Ошибка при апдейте сделок - " . $leads_result . "\n";
                    continue;
                }
                if ($leads_result_errors = $leads_result['_embedded']['errors']) {
                    // При неудачном апдейте пишем лог ошибок данной итерации, пропускаем добавление примечаний
                    $leads_update_errors_put_result_json = file_put_contents(__DIR__ . "/files/leads-update-errors.json", json_encode($leads_result_errors), FILE_APPEND);
                    continue;
                }
                echo "Добавление примечаний в $x сделок...\n";
                $notes_result = notes_add($subdomain, $notes_data);
                if (!is_array($notes_result)) {
                    // При ошибке запроса пишем номер запроса и ошибки, пропускаем добавление примечаний
                    $leads_update_errors_put_result_txt = file_put_contents(__DIR__ . "/files/notes-update-errors.txt", $i . ' - ' . $notes_result . "\n", FILE_APPEND);
                    echo $i . " Ошибка при добавлении примечания - " . $notes_result . "\n";
                }
                if ($notes_result_errors = $notes_result['_embedded']['errors']) {
                    // При неудачном апдейте пишем лог ошибок данной итерации, пропускаем добавление примечаний
                    $notes_add_errors_put_result_json = file_put_contents(__DIR__ . "/files/notes-add-errors.json", json_encode($notes_result_errors), FILE_APPEND);
                }
            }
            fclose($leads_update_file_open);
            fclose($notes_add_file_open);
        }
	} else {
		echo "Ошибки записи файлов\n";
	}
} else {
	echo "Ошибка авторизации\n";
	return $result;
}
