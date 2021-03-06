<?php

function auth($subdomain, $login, $hash){
	$user = [
		'USER_LOGIN' => $login,
		'USER_HASH' => $hash
	];
	$url = '/private/api/auth.php?type=json';
	$result = init($subdomain, $url, $user);
	if (is_array($result['out']) && $result['out']['response']['auth'] == TRUE) {
		return TRUE;
	} else {
		return $result;
	}
}

function get_leads($subdomain, $rows, $offset) {
	$url = '/api/v2/leads?limit_rows='.$rows.'&limit_offset='.$offset;
	$result = init($subdomain, $url);
	return $result;
}

function del_tags($subdomain, $data) {
	$url = '/api/v2/leads/';
	$result = init($subdomain, $url, $data);
	return $result;
}

function notes_add($subdomain, $data) {
	$url = '/api/v2/notes/';
	$result = init($subdomain, $url, $data);
	return $result;
}

function init($subdomain, $url, $data=null) {
    $link = 'https://' . $subdomain . '.amocrm.ru' . $url;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'amoCRM-API-client/2.0');
    curl_setopt($ch, CURLOPT_URL, $link);
    if (!empty($data)){
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $out = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $out = json_decode($out, true);
    $result = ['info' => $info, 'body' => $data, 'out' => $out];
    return $result;
}

function check_notes_result($result) {
    $notes = $result['out']['_embedded']['items'];
    if (isset($result['out']['response']['error'])) {
        file_put_contents(__DIR__ . "/errors/notes-add-request-errors.json", json_encode($result) . "\n", FILE_APPEND);
        echo "Ошибка запроса добавления примечаний - " . $result['info']['http_code'] . "\n";
    } elseif ($result_errors = $result['out']['_embedded']['errors']) {
        file_put_contents(__DIR__ . "/errors/notes-add-result-errors.json", json_encode($result) . "\n", FILE_APPEND);
    } else {
        echo "Добавлены примечания в " . count($notes) . " сделок\n";
    }
}

function prepare_update_file($subdomain, $count, $tags_names, $offset=NULL) {
    $leads_result = TRUE;
    $i = 0;
    $leads_update_file_put_result = []; // Массив результатов записи в файл данных для апдейта сделок

    while ($leads_result) {
        sleep(1);
        if ($offset > 0) {  // Если в запрос передан offset
			$limit_offset = $offset;
			$limit_offset += $i*$count;
		} else {
			$limit_offset = $i*$count;
			$i++;
		}

        // Получение списка сделок
        $leads_result = get_leads($subdomain, $count, $limit_offset);

		// Проверка результата запроса сделок
        if (isset($leads_result['out']['response']['error'])) {
            echo "Ошибка при получении сделок на запросе с оффсетом - " . $limit_offset . "\n";
            file_put_contents(__DIR__ . "/errors/leads-get-errors.json", json_encode($leads_result) . "\n", FILE_APPEND);
            break;
		}

        $leads = $leads_result['out']['_embedded']['items']; // Массив сделок
        if (count($leads) == 0) {
            echo "0 сделок получено\n";
            break;
        }
		echo "Поиск по сделкам, текущий оффсет - " . $limit_offset . "...\n";

        foreach ($leads as $lead) {
            $lead_id = $lead['id'];
            $lead_updated_at = $lead['updated_at'];
            $lead_tags_names = [];      // Массив имен тегов в сделке
            $lead_tags = $lead['tags'];

            foreach ($lead_tags as $lead_tag) {
                $lead_tags_names[] = $lead_tag['name'];
			}
            // бэкап-файл тегов по полученным сделкам
            file_put_contents(__DIR__ . "/backups/tags.json", json_encode([$lead_id, $lead_tags_names]) . "\n", FILE_APPEND);

            $tags_to_del = array_intersect($lead_tags_names, $tags_names); // Теги к удалению в сделке
            $leave_tags_names = array_diff($lead_tags_names, $tags_names);   // Теги - остаются в сделке
			$leave_tags = implode($leave_tags_names, ',');
			$note_text = implode($tags_to_del, ' ');

            if (count($tags_to_del) > 0) {
                $leads_update = [  // Массив для апдейта сделок
                	'update' => ['id' => $lead_id, 'updated_at' => $lead_updated_at, 'tags' => $leave_tags],
					'notes_add' => ['element_id' => $lead_id, 'element_type' => 2, 'note_type' => 25, 'params' => ['text' => $note_text, 'service' => 'Удалены теги']]
				];
                if (($file_put_size = file_put_contents(__DIR__ . "/update-files/tags-update.json", json_encode($leads_update) . "\n", FILE_APPEND) > 0)) {
					$leads_update_file_put_result[] = $file_put_size;
				} else {
					echo "Ошибка записи в файл сделки " . $lead_id . "\n";
					file_put_contents(__DIR__ . "/errors/tags-update-file-put-errors.json", json_encode($leads_update) . "\n", FILE_APPEND);
				}
            }
        }
        echo "Всего сделок к апдейту: " . count($leads_update_file_put_result) . "\n";
    }
    $leads_update_count = count($leads_update_file_put_result) > 0 ? count($leads_update_file_put_result) : FALSE;
	return $leads_update_count;
}

function make_updates($subdomain, $dir, $run_again=true) {
    global $position;
    $make_updates_results = [];
	$leads_result = TRUE;
    $leads_update_file_open = fopen(__DIR__ . "/" . $dir . "/tags-update.json", 'r');
    if (isset($position) && $run_again==true) {
        fseek($leads_update_file_open, $position);
    }
    if ($leads_update_file_open) {
        while ($leads_result) {
            sleep(1);
            $leads_result = make_update_iteration($subdomain, $leads_update_file_open, $run_again);
            if ($leads_result !== FALSE) {
                $make_updates_results[] = $leads_result;
            }
        }
        fclose($leads_update_file_open);
    }
    return $make_updates_results;
}

function make_update_iteration($subdomain, $descriptor, $run_again=true) {
    global $count;
    $file_position = ftell($descriptor); // Получение текущей позиции дескриптора в файле
    echo "Текущее положение дескриптора файла апдейта: " . $file_position . "\nПодготовка массивов данных для запросов...\n";
	$leads_update_items = [];
    $leads_update_request_data = [];
	$notes_add_request_data = [];
    for ($x=0; $x<$count; $x++) {
        $leads_update_item_json = fgets($descriptor);
		if ($leads_update_item_json) {
			$current_time = time();
			// Копируем прочитанную строку в новый файл(на случай падения скрипта во время апдейта)
			file_put_contents(__DIR__ . "/backups/updated-items.json", $leads_update_item_json, FILE_APPEND);
            $leads_update_item_decoded = json_decode($leads_update_item_json, true);
            $leads_update_items[] = $leads_update_item_decoded; // Массив с данными по сделкам данной итерации для формирования в случ. ошибок файлов логов и повторного запуска
            $leads_update_item_data = $leads_update_item_decoded['update'];
            if ($leads_update_item_data['updated_at'] > $current_time) {
				$current_time = $leads_update_item_data['updated_at'] + 10;
			}
            $notes_add_item_data = $leads_update_item_decoded['notes_add'];
            $leads_update_item_data['updated_at'] = $current_time; // Дозапись параметра updated_at в массив для апдейта
            $leads_update_request_data[] = $leads_update_item_data;
            $notes_add_request_data[] = $notes_add_item_data;
        }
    }
    if (count($leads_update_request_data) > 0) {
		$leads_data = [
			'update' => $leads_update_request_data,
		];
		$notes_data = [
			'add' => $notes_add_request_data,
		];
		echo "Удаление тегов из " . count($leads_update_request_data) . " сделок...\n";
		$leads_result = del_tags($subdomain, $leads_data);
		// Уменьшение $count в 2 раза при Timeout
		if ($leads_result['info']['http_code'] == 504) {
            echo "504 Timeout при попытке обновления " . count($leads_update_request_data) . " сделок. Количество получаемых строк уменьшено в 2 раза\n";
		    fseek($descriptor, $file_position); // Перемещение дескриптора в позицию, с кот. началось чтение файла текущей итерации
		    $count /= 2;
		    $count = ceil($count);
			return make_update_iteration($subdomain, $descriptor);
		}
		if (isset($leads_result['out']['response']['error'])) {
			if ($run_again) {
				// При ошибке запроса апдейта сделок пишем массив данных запроса в файл для повторного запуска, примечания не добавляем
				foreach ($leads_update_items as $leads_update_item) {
					file_put_contents(__DIR__ . "/run-again/tags-update.json", json_encode($leads_update_item) . "\n", FILE_APPEND);
				}
			} else {
				// При наличии ошибок после повторного запуска пишем логи, примечания не добавляются
				file_put_contents(__DIR__ . "/errors/leads-update-request-errors.json", json_encode($leads_result) . "\n", FILE_APPEND);
			}
			echo "Ошибка запроса апдейта сделок - " . $leads_result['info']['http_code'] . "\nСписок сделок записан в файл для повторного запуска\n";
		} elseif ($leads_result_errors = $leads_result['out']['_embedded']['errors']['update']) {
			if ($run_again) {
				// При наличии ошибок данной итерации пишем массив данных по неудавшимся апдейтам в файл для повторного запуска
				foreach ($leads_result_errors as $lead_id => $error_text) {
					$leads_update_request_data_item_key = array_search($lead_id, array_column($leads_update_request_data, 'id'));
					file_put_contents(__DIR__ . "/run-again/tags-update.json", json_encode($leads_update_items[$leads_update_request_data_item_key]) . "\n", FILE_APPEND);
					// Удаляем из массива данных для добавления примечаний элементы, соответствующие неудавшимся апдейтам сделок
					$notes_add_request_data_item_key = array_search($lead_id, array_column($notes_add_request_data, 'element_id'));
					unset($notes_add_request_data[$notes_add_request_data_item_key]);
				}
			} else {
				// При наличии ошибок после повторного запуска пишем логи
				file_put_contents(__DIR__ . "/errors/leads-update-result-errors.json", json_encode($leads_result) . "\n", FILE_APPEND);
				foreach ($leads_result_errors as $lead_id => $error_text) {
					// Удаляем из массива данных для добавления примечаний элементы, соответствующие неудавшимся апдейтам сделок
					$notes_add_request_data_item_key = array_search($lead_id, array_column($notes_add_request_data, 'element_id'));
					unset($notes_add_request_data[$notes_add_request_data_item_key]);
				}
			}
            echo "Обновлено " . count($leads_result['out']['_embedded']['items']) . "\n" . count($leads_result['out']['_embedded']['errors']['update']) . " сделок записано в файл для повторного запуска\n";
			$notes_data = [
				'add' => $notes_add_request_data,
			];
			$notes_result = notes_add($subdomain, $notes_data);
            check_notes_result($notes_result);
		} else {
		    echo count($leads_result['out']['_embedded']['items']) . " сделок обновлено\n";
			$notes_result = notes_add($subdomain, $notes_data);
            check_notes_result($notes_result);
		}
	} else {
    	echo "Апдейт сделок завершен\n";
    	$leads_result = FALSE;
	}
    return $leads_result;
}
