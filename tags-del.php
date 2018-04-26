<?php

ini_set('max_execution_time', 1800);
require_once 'functions.php';

$subdomain = 'newdemonew';
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c40';
$tags_names = ['tag', 'tag1', 'tag-test', '34534', 'теги удаление', 'постоянные клиенты', 'покупочки']; // Теги к удалению

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {

	$rows = 500;
	$offset = 500;
	$leads_result = true;
	$i = 0;

	$leads_update = []; // Массив для апдейта всех сделок, в кот найдены теги
	$notes_add = [];  // Массив для добавления примечаний в сделки

	while ($leads_result) {
		sleep(1);
		$limit_offset = $i*$offset;
		$i++;
		echo $i.'<br>';

		// Получение списка сделок
		$leads_result = get_leads($subdomain, $rows, $limit_offset);

		if (!is_array($leads_result)) {
			break;
		}

		$leads = $leads_result['_embedded']['items']; // Массив сделок

		$leads_update_array = [];
		$notes_add_array = [];

		foreach ($leads as $lead) {
			$lead_id = $lead['id'];
			$lead_updated_at = time();
			$lead_tags_names = [];
			$leave_tags = '';
			$lead_tags = $lead['tags'];

			foreach ($lead_tags as $lead_tag) {
				$lead_tags_names[] = $lead_tag['name'];
			}
			$tags_to_del = array_intersect($lead_tags_names, $tags_names); // Теги к удалению в сделке
			$leave_tags_arr = array_diff($lead_tags_names, $tags_names); // Теги - остаются в сделке
			foreach ($leave_tags_arr as $leave_tag) {
				$leave_tags .= $leave_tag . ',';
			}

			$note_text = "";
			foreach ($tags_to_del as $tag_to_del) {
				$note_text .= $tag_to_del . ' ';
			}

			if (count($tags_to_del) > 0) {
				$leads_update_array[] = array('id' => $lead_id, 'updated_at' => $lead_updated_at, 'tags' => $leave_tags); // Массив для апдейта сделок
				$notes_add_array[] = array('element_id' => $lead_id, 'element_type' => '2', 'note_type' => '25', 'params' => array('text' => $note_text,'service' => 'Удалены теги')); // Массив для добавления примечаний об удаленных тегах
			}
		}
		$leads_update[] = $leads_update_array;
		$notes_add[] = $notes_add_array;
	}

// Удаление тегов из сделок
	foreach ($leads_update as $leads_update_item) {
		sleep(1);
		$data = array(
			'update' => $leads_update_item,
		);
		$result = del_tags($subdomain, $data);
		var_dump($result);
	}

// Добавление примечаний в сделки
	foreach ($notes_add as $notes_add_item) {
		sleep(1);
		$data = array (
			'add' => $notes_add_item,
		);
		$result = notes_add($subdomain, $data);
		var_dump($result);
	}
} else {
	return $result;
}
