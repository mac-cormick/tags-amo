<?php

require_once "const.php";

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

function prepare_updates_files($subdomain, $rows, $offset, $tags_names_array) {
    $leads_result = true;
    $i = 0;
    $leads_update_file_put_result = []; // Массив результатов записи в файл данных для апдейта сделок
    $notes_add_file_put_result = [];    // Маассив результатов записи в файл данных для добавления примечаний

    while ($leads_result) {
        sleep(1);
        $limit_offset = $i*$offset;
        $i++;

        // Получение списка сделок
        $leads_result = get_leads($subdomain, $rows, $limit_offset);

        if (!is_array($leads_result)) {
        	echo "Список сделок пуст\n\n";
        	$leads_result = false;
            break;
        }

        $leads = $leads_result['_embedded']['items']; // Массив сделок
		echo "Поиск по: ".count($leads)." сделкам...\n";

        foreach ($leads as $lead) {
            $lead_id = $lead['id'];
            $lead_tags_names = [];      // Массив имен тегов в сделке
            $leave_tags = '';           // Строка тегов, кот. останутся после апдейта - для запроса апдейта
            $lead_tags = $lead['tags'];
            $note_text = "";            // Строка списка удаленных тегов для примечания

            foreach ($lead_tags as $lead_tag) {
                $lead_tags_names[] = $lead_tag['name'];
            }
            $tags_to_del = array_intersect($lead_tags_names, $tags_names_array); // Теги к удалению в сделке
            $leave_tags_arr = array_diff($lead_tags_names, $tags_names_array);   // Теги - остаются в сделке
            foreach ($leave_tags_arr as $leave_tag) {
                $leave_tags .= $leave_tag . ',';
            }

            foreach ($tags_to_del as $tag_to_del) {
                $note_text .= $tag_to_del . ' ';
            }

            if (count($tags_to_del) > 0) {
                $leads_update_array = array('id' => $lead_id, 'tags' => $leave_tags); // Массив для апдейта сделок
                $leads_update_file_put_result[] = file_put_contents(__DIR__ . "/files/tags-update.json", json_encode($leads_update_array) . "\n", FILE_APPEND);
                $notes_add_array = array('element_id' => $lead_id, 'element_type' => 2, 'note_type' => 25, 'params' => array('text' => $note_text,'service' => 'Удалены теги')); // Массив для добавления примечаний об удаленных тегах
                $notes_add_file_put_result[] = file_put_contents(__DIR__ . "/files/notes-add.json", json_encode($notes_add_array) . "\n", FILE_APPEND);
            }
        }
        if (count($leads_update_file_put_result) == count($notes_add_file_put_result)) {
        	echo "Всего сделок к апдейту: ".count($leads_update_file_put_result)."\n";
		} else {
        	echo "Сделок к апдейту: 0\n";
		}
    }
    if (in_array(FALSE, $leads_update_file_put_result) || in_array(FALSE, $notes_add_file_put_result)) {
        return false;
    } else {
        return true;
    }
}

function make_updates($subdomain, $count) {
    $i = 0;
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
                file_put_contents(__DIR__ . "/files/leads-update-errors.txt", $i . ' - ' . $leads_result . "\n", FILE_APPEND);
                echo $i . " Ошибка при апдейте сделок - " . $leads_result . "\n";
                continue;
            }
            if ($leads_result_errors = $leads_result['_embedded']['errors']) {
                // При наличии ошибок данной итерации пишем лог, пропускаем добавление примечаний
                file_put_contents(__DIR__ . "/files/leads-update-errors.json", json_encode($leads_result_errors), FILE_APPEND);
                continue;
            }
            echo "Добавление примечаний в " . count($notes_add_array) . " сделок...\n";
            $notes_result = notes_add($subdomain, $notes_data);
            if (!is_array($notes_result)) {
                // При ошибке запроса добавления примечаний пишем номер запроса и код ошибки
                file_put_contents(__DIR__ . "/files/notes-update-errors.txt", $i . ' - ' . $notes_result . "\n", FILE_APPEND);
                echo $i . " Ошибка при добавлении примечания - " . $notes_result . "\n";
            }
            if ($notes_result_errors = $notes_result['_embedded']['errors']) {
                // При наличии ошибок данной итерации пишем лог
                file_put_contents(__DIR__ . "/files/notes-add-errors.json", json_encode($notes_result_errors), FILE_APPEND);
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
	$out = curl_exec ($ch);
	$code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$code=(int)$code;
	if($code != 200) {
		return $code;
	} else {
		$out = json_decode($out, TRUE);
		return $out;
	}
}
