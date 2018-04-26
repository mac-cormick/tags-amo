<?php

ini_set('max_execution_time', 1800);
require_once 'functions.php';

$subdomain = 'newdemonew';
$login = 'amolyakov@team.amocrm.com';
$hash = '691c2c8c35794e95be679e7a21d40c40';
$tags_names = ['tag', 'tag1', 'tag-test']; // Теги к удалению

// Авторизация
$result = auth($subdomain, $login, $hash);

if ($result === TRUE) {

	$rows = 3;
	$offset = 3;
	$leads_result = true;
	$i = 0;

	$update_array = []; // Массив массивов сделок к апдейту и примечаний к добавлению

	while ($leads_result) {
		sleep(1);
		$notes_add_array = [];
		$leads_update_array = [];
		$limit_offset = $i*$offset;
		$i++;

		// Получение списка сделок
		$leads_result = get_leads($subdomain, $rows, $limit_offset);

		if (!is_array($leads_result)) {
			break;
		}

		$leads = $leads_result['_embedded']['items']; // Массив сделок

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
		$update_array[] = array('leads' => $leads_update_array, 'notes' => $notes_add_array);
	}

// Удаление тегов и добавление примечаний
	foreach ($update_array as $update_array_item) {
		echo count($update_array_item['leads']).' - ';
		echo count($update_array_item['notes']).'<br>';
		sleep(1);
		$leads_data = array(
			'update' => $update_array_item['leads'],
		);
		$leads_result = del_tags($subdomain, $leads_data);
		echo '<pre>';
		var_dump($leads_result);
		echo '</pre>';
		echo '<br>';
		$notes_data = array(
			'add' => $update_array_item['notes'],
		);
		$notes_result = notes_add($subdomain, $notes_data);
		echo '<pre>';
		var_dump($notes_result);
		echo '</pre>';
		echo '<hr>';
	}
} else {
	return $result;
}
