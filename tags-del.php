<?php

ini_set('max_execution_time', 1800);

require_once "const.php";
require_once 'functions.php';

$subdomain = 'newdemonew';
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c4';
$tags_names = ['tag', 'tag1', '2454-234', '4341-qwer']; // Теги к удалению

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {
	$rows = 500;
	$offset = 500;

    $updates_size = make_updates_files($subdomain, $rows, $offset, $tags_names); // Запись данных для запросов апдейта сделок и добавления примечаний в json файлы
	if ($updates_size > 0) {
		// Получение массивов для апдейта из json файлов
		$files_dir = APP_DIR."/files";
		$update_files = array_diff(scandir($files_dir), array('..', '.'));
		foreach ($update_files as $update_file) {
			sleep(1);
			$json_string = file_get_contents($files_dir.'/'.$update_file);
			$updates_array = json_decode($json_string, true);

			// Удаление тегов и добавление примечаний
			foreach ($updates_array as $updates_array_item) {
				$leads_update_array = [];
				foreach ($updates_array_item[0] as $item) {
					$item['updated_at'] = time(); // Дозапись параметра updated_at в массив для апдейта
					$leads_update_array[] = $item;
				}
				$leads_data = array(
					'update' => $leads_update_array,
				);
				$leads_result = del_tags($subdomain, $leads_data);
				var_dump($leads_result);
				if (!is_array($leads_result)) {
					echo "Ошибка при апдейте сделки\n".$leads_result;
					break;
				}
				if ($leads_result_errors = $leads_result['_embedded']['errors']) {
					$leads_update_errors_put_result = file_put_contents(APP_DIR."/errors/leads-update-errors.json", json_encode($leads_result_errors), FILE_APPEND);
					continue; // При неудачном апдейте пишем лог, пропускаем добавление примечаний
				}
				$notes_data = array(
					'add' => $updates_array_item[1],
				);
				$notes_result = notes_add($subdomain, $notes_data);
				if (!is_array($notes_result)) {
					echo "Ошибка при добавлении примечания\n".$notes_result;
					break;
				}
			}
			unlink($files_dir.'/'.$update_file);
		}
	} else {
		echo "Ошибка записи\n";
	}
} else {
	echo "Ошибка авторизации\n";
	return $result;
}
