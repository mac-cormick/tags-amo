<?php

function auth($subdomain, $login, $hash){
	$user = array(
		'USER_LOGIN' => $login,
		'USER_HASH' => $hash
	);
	$url = '/private/api/auth.php?type=json';
	$result = init($subdomain, $url, $user);
	if (is_array($result) && $result['response']['auth'] == TRUE) {
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
	$url = '/api/v2/leas/';
	$result = init($subdomain, $url, $data);
	return $result;
}

function notes_add($subdomain, $data) {
	$url = '/api/v2/notes/';
	$result = init($subdomain, $url, $data);
	return $result;
}

function prepare_update_file($subdomain, $count, $tags_names, $offset=NULL) {
    $leads_result = TRUE;
    $i = 0;
    $leads_update_file_put_result = []; // Массив результатов записи в файл данных для апдейта сделок
//    $notes_add_file_put_result = [];    // Маассив результатов записи в файл данных для добавления примечаний

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
//        var_dump($leads_result);

		// Проверка результата запроса сделок
        if (isset($leads_result['http_code'])) {
        	if ($leads_result['http_code'] == 204) {
				echo "Список сделок пуст\n";
				break;
			} else {
        		echo "Ошибка при получении сделок на запросе с оффсетом - " . $limit_offset;
				file_put_contents(__DIR__ . "/errors/leads-get-errors.json", json_encode($leads_result) . "\n", FILE_APPEND);
				break;
			}
		}

        $leads = $leads_result['_embedded']['items']; // Массив сделок
		echo "Поиск по: ".count($leads)." сделкам, оффсет - " . $limit_offset . "...\n";

        foreach ($leads as $lead) {
            $lead_id = $lead['id'];
            $lead_tags_names = [];      // Массив имен тегов в сделке
            $lead_tags = $lead['tags'];

            foreach ($lead_tags as $lead_tag) {
                $lead_tags_names[] = $lead_tag['name'];
                // бэкап-файл тегов по полученным сделкам
				file_put_contents(__DIR__ . "/backups/tags.json", json_encode(array($lead_id, $lead_tags_names)) . "\n", FILE_APPEND);
			}
            $tags_to_del = array_intersect($lead_tags_names, $tags_names); // Теги к удалению в сделке
            $leave_tags_names = array_diff($lead_tags_names, $tags_names);   // Теги - остаются в сделке
			$leave_tags = implode($leave_tags_names, ',');
			$note_text = implode($tags_to_del, ' ');

            if (count($tags_to_del) > 0) {
                $leads_update = [  // Массив для апдейта сделок
                	'update' => ['id' => $lead_id, 'tags' => $leave_tags],
					'notes_add' => ['element_id' => $lead_id, 'element_type' => 2, 'note_type' => 25, 'params' => ['text' => $note_text, 'service' => 'Удалены теги']]
				];
                if (($file_put_size = file_put_contents(__DIR__ . "/update-files/tags-update.json", json_encode($leads_update) . "\n", FILE_APPEND) > 0)) {
					$leads_update_file_put_result[] = $file_put_size;
				} else {
					echo "Ошибка записи в файл сделки " . $lead_id;
					file_put_contents(__DIR__ . "/errors/tags-update-file-put-errors.json", json_encode($leads_update) . "\n", FILE_APPEND);
				}
            }
        }
        echo "Всего сделок к апдейту: " . count($leads_update_file_put_result) . "\n";
    }
    $leads_update_count = count($leads_update_file_put_result) > 0 ? count($leads_update_file_put_result) : FALSE;
	return $leads_update_count;
}

function make_updates($subdomain, $count) {
	$leads_result = TRUE;
    $leads_update_file_open = fopen(__DIR__ . "/update-files/tags-update.json", 'r');
    if ($leads_update_file_open) {
        while ($leads_result) {
            sleep(1);
            $leads_result = make_update_iteration($subdomain, $count, $leads_update_file_open);
//            var_dump($leads_result);
        }
        fclose($leads_update_file_open);
//        fclose($notes_add_file_open);
    }
    return TRUE;
}

function make_update_iteration($subdomain, $count, $descriptor, $run_again=true) {
	$leads_update_items = [];
    $leads_update_array = [];
	$notes_add_array = [];
	$leads_result = [];
    for ($x=0; $x<$count; $x++) {
        $leads_update_item = fgets($descriptor);
		if ($leads_update_item) {
			// Копируем прочитанную строку в новый файл(на случай падения во время апдейта)
			file_put_contents(__DIR__ . "/backups/updated-items.json", $leads_update_item . "\n", FILE_APPEND);
            $leads_update_item_array = json_decode($leads_update_item, true);
            $leads_update_items[] = $leads_update_item_array; // Массив с данными по сделкам данной итерации для формирования в случ. ошибок файлов логов и повторного запуска
            $leads_update_item_data = $leads_update_item_array['update'];
            $notes_add_item_data = $leads_update_item_array['notes_add'];
//                    var_dump($leads_update_item_array);
            $leads_update_item_data['updated_at'] = time(); // Дозапись параметра updated_at в массив для апдейта
            $leads_update_array[] = $leads_update_item_data;
            $notes_add_array[] = $notes_add_item_data;
        }
    }
    if (count($leads_update_array) > 0) {
		$leads_data = array(
			'update' => $leads_update_array,
		);
		$notes_data = array(
			'add' => $notes_add_array,
		);
//	var_dump($leads_data);
		echo "Удаление тегов из " . count($leads_update_array) . " сделок...\n";
		$leads_result = del_tags($subdomain, $leads_data);
		var_dump($leads_result);
		// Уменьшение $count в 2 раза при Timeout
		if (isset($leads_result['http_code']) && $leads_result['http_code'] == 504) {
			echo "Не удалось обновить " . $count . " сделок. Количество уменьшено в 2 раза\n";
			return make_update_iteration($subdomain, $count/2, $descriptor);
		}
		if (isset($leads_result['http_code'])) {
			if ($run_again) {
				// При ошибке запроса апдейта сделок пишем массив данных запроса в файл для повторного запуска, примечания не добавляем
				foreach ($leads_update_items as $leads_update_item) {
					file_put_contents(__DIR__ . "/run-again/leads-update.json", json_encode($leads_update_item) . "\n", FILE_APPEND);
				}
			} else {
				// При наличии ошибок после повторного запуска пишем логи, примечания не добавляются
				file_put_contents(__DIR__ . "/errors/leads-update-request-errors.json", json_encode($leads_result) . "\n", FILE_APPEND);
			}
			echo "Ошибка в запросе - " . $leads_result['http_code'] . "\n";
		} elseif ($leads_result_errors = $leads_result['_embedded']['errors']['update']) {
			if ($run_again) {
				// При наличии ошибок данной итерации пишем массив данных по неудавшимся апдейтам в файл для повторного запуска
				foreach ($leads_result_errors as $lead_id => $error_text) {
					$leads_update_array_item_key = array_search($lead_id, array_column($leads_update_array, 'id'));
					file_put_contents(__DIR__ . "/run-again/leads-update.json", json_encode($leads_update_array[$leads_update_array_item_key]) . "\n", FILE_APPEND);
					// Удаляем из массива данных для добавления примечаний элементы, соответствующие неудавшимся апдейтам сделок
					$notes_add_array_item_key = array_search($lead_id, array_column($notes_add_array, 'element_id'));
					unset($notes_add_array[$notes_add_array_item_key]);
				}
			} else {
				// При наличии ошибок после повторного запуска пишем логи
				file_put_contents(__DIR__ . "/errors/leads-update-result-errors.json", json_encode($leads_result['_embedded']['errors']) . "\n", FILE_APPEND);
				foreach ($leads_result_errors as $lead_id => $error_text) {
					// Удаляем из массива данных для добавления примечаний элементы, соответствующие неудавшимся апдейтам сделок
					$notes_add_array_item_key = array_search($lead_id, array_column($notes_add_array, 'element_id'));
					unset($notes_add_array[$notes_add_array_item_key]);
				}
			}
			$notes_data = array(
				'add' => $notes_add_array,
			);
			$notes_result = notes_add($subdomain, $notes_data);
		} else {
			$notes_result = notes_add($subdomain, $notes_data);
		}
	} else {
    	echo "Апдейт сделок завершен\n";
    	$leads_result = FALSE;
	}
    return $leads_result;
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
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	$out = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$info = curl_getinfo($ch);
	curl_close($ch);
	$code=(int)$code;
	if($code != 200) {
		return ['info' => $info, 'body' => $data];
	} else {
		$out = json_decode($out, TRUE);
		return $out;
	}
}
