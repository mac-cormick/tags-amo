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
	$url = '/api/v2/leads/';
	$result = init($subdomain, $url, $data);
	return $result;
}

function notes_add($subdomain, $data) {
	$url = '/api/v2/notes/';
	$result = init($subdomain, $url, $data);
	return $result;
}

function prepare_leads_update_file($subdomain, $count, $tags_names, $offset=NULL) {
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
//				$leads_result = FALSE;
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
//            $leave_tags = '';           // Строка тегов, кот. останутся после апдейта - для запроса апдейта
            $lead_tags = $lead['tags'];
//            $note_text = "";            // Строка списка удаленных тегов для примечания

            foreach ($lead_tags as $lead_tag) {
                $lead_tags_names[] = $lead_tag['name'];
                // бэкап-файл тегов по полученным сделкам
				file_put_contents(__DIR__ . "/backups/tags.json", json_encode(array($lead_id, $lead_tags_names)) . "\n", FILE_APPEND);
			}
            $tags_to_del = array_intersect($lead_tags_names, $tags_names); // Теги к удалению в сделке
            $leave_tags_names = array_diff($lead_tags_names, $tags_names);   // Теги - остаются в сделке
			$leave_tags = implode($leave_tags_names, ',');

//            foreach ($tags_to_del as $tag_to_del) {
//                $note_text .= $tag_to_del . ' ';
//            }

            if (count($tags_to_del) > 0) {
                $leads_update = [  // Массив для апдейта сделок
                	'update' => ['id' => $lead_id, 'tags' => $leave_tags],
					'old_tags' => $lead_tags_names
				];
                if (($file_put_size = file_put_contents(__DIR__ . "/update-files/tags-update.json", json_encode($leads_update) . "\n", FILE_APPEND) > 0)) {
					$leads_update_file_put_result[] = $file_put_size;
				} else {
					echo "Ошибка записи в файл сделки " . $lead_id;
					file_put_contents(__DIR__ . "/errors/tags-update-file-put-errors.json", json_encode($leads_update) . "\n", FILE_APPEND);
				}
//                $notes_add_array = array('element_id' => $lead_id, 'element_type' => 2, 'note_type' => 25, 'params' => array('text' => $note_text,'service' => 'Удалены теги')); // Массив для добавления примечаний об удаленных тегах
//                $notes_add_file_put_result[] = file_put_contents(__DIR__ . "/update-files/notes-add.json", json_encode($notes_add_array) . "\n", FILE_APPEND);
            }
        }
        echo "Всего сделок к апдейту: " . count($leads_update_file_put_result) . "\n";
    }
    $leads_update_count = count($leads_update_file_put_result) > 0 ? count($leads_update_file_put_result) : FALSE;
	return $leads_update_count;
}

function make_updates($subdomain, $count) {
    $i = 0;
    // Получение массивов для апдейта из json файлов
    $leads_update_file_open = fopen(__DIR__ . '/tags-update.json', 'r');
    $notes_add_file_open = fopen(__DIR__ . '/notes-add.json', 'r');
    if ($leads_update_file_open && $notes_add_file_open) {
        $leads_update_item = TRUE;
        $notes_add_item = TRUE;
        while ($leads_update_item && $notes_add_item) {
            $leads_update_array = [];
            $notes_add_array = [];
            $i++;
            sleep(1);
            // Получение данных для запросов из файлов по $count для одной итерации
            for ($x=0; $x<$count; $x++) {
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
            if (count($leads_update_array) == 0) break;
            $leads_data = array(
                'update' => $leads_update_array,
            );
            $notes_data = array(
                'add' => $notes_add_array,
            );
            echo "Удаление тегов из " . count($leads_update_array) . " сделок...\n";
            $leads_result = del_tags($subdomain, $leads_data);
            // Уменьшение $count в 2 раза при Timeout
            if ($leads_result == 504) {
                echo "Не удалось обновить " . $count . " сделок. Количество уменьшено в 2 раза\n";
                return make_updates($subdomain, $count/2);
            }
            if (!is_array($leads_result)) {
                // При ошибке запроса апдейта сделки пишем номер запроса и код ошибки, пропускаем добавление примечаний
                file_put_contents(__DIR__ . "/leads-update-errors.txt", $i . ' - ' . $leads_result . "\n", FILE_APPEND);
                echo $i . " Ошибка при апдейте сделок - " . $leads_result . "\n";
                continue;
            }
            if ($leads_result_errors = $leads_result['_embedded']['errors']) {
                // При наличии ошибок данной итерации пишем лог, пропускаем добавление примечаний
                file_put_contents(__DIR__ . "/leads-update-errors.json", json_encode($leads_result_errors), FILE_APPEND);
                continue;
            }
            echo "Добавление примечаний в " . count($notes_add_array) . " сделок...\n";
            $notes_result = notes_add($subdomain, $notes_data);
            if (!is_array($notes_result)) {
                // При ошибке запроса добавления примечаний пишем номер запроса и код ошибки
                file_put_contents(__DIR__ . "/notes-update-errors.txt", $i . ' - ' . $notes_result . "\n", FILE_APPEND);
                echo $i . " Ошибка при добавлении примечания - " . $notes_result . "\n";
            }
            if ($notes_result_errors = $notes_result['_embedded']['errors']) {
                // При наличии ошибок данной итерации пишем лог
                file_put_contents(__DIR__ . "/notes-add-errors.json", json_encode($notes_result_errors), FILE_APPEND);
            }
        }
        fclose($leads_update_file_open);
        fclose($notes_add_file_open);
    }
    return TRUE;
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
		return $info;
	} else {
		$out = json_decode($out, TRUE);
		return $out;
	}
}
