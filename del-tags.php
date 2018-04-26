<?php

ini_set('max_execution_time', 1800);

// Авторизация
$user = array(
	'USER_LOGIN'=>'amolyakov@team.amocrm.com',
	'USER_HASH'=>'691c2c8c35794e95be679e7a21d40c40'
);
$subdomain = 'newdemonew';
$auth = 'https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';
$ch = curl_init();
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_USERAGENT,'amoCRM-API-client/2.0');
curl_setopt($ch,CURLOPT_URL,$auth);
curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'POST');
curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($user));
curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
curl_setopt($ch,CURLOPT_HEADER,false);
curl_setopt($ch,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt');
curl_setopt($ch,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt');
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
$out = curl_exec($ch);
curl_close($ch);

$Response=json_decode($out,true);
$Response=$Response['response'];

if (isset($Response['auth']))
{
	echo 'Авторизация прошла успешно<br>';
} else {
	echo 'Ошибка авторизации<br>';
}

$tags_names = ['теги удаление', 'постоянные клиенты', 'покупочки', 'семинар3']; // Теги к удалению
$leads_result = true;
$i = 0;

$leads_update = []; // Массив для апдейта всех сделок, в кот найдены теги
$notes_add = [];  // Массив для добавления примечаний в сделки

while ($leads_result) {
	sleep(1);
	$limit_offset = $i*50;
	$i++;
	echo $i;

	// Получение списка сделок по 500

	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/leads?limit_rows=50&limit_offset='.$limit_offset;
	echo $link;
	$headers[] = "Accept: application/json";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_USERAGENT, "amoCRM-API-client/2.0");
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_URL, $link);
	curl_setopt($curl, CURLOPT_HEADER,false);
	curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__)."/cookie.txt");
	curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__)."/cookie.txt");
	$out = curl_exec($curl);
	curl_close($curl);
	$leads_result = json_decode($out,TRUE);

	if (!$leads_result) {
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

	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/leads/';
	$headers[] = "Accept: application/json";
	//Curl options
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT, "amoCRM-API-client/2.0");
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($curl, CURLOPT_URL, $link);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . "/cookie.txt");
	curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/cookie.txt");
	$out = curl_exec($curl);
	curl_close($curl);
	$result = json_decode($out, TRUE);
}

// Добавление примечаний в сделки
foreach ($notes_add as $notes_add_item) {
	sleep(1);
	$data = array (
		'add' => $notes_add_item,
	);
	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/notes';
	$headers[] = "Accept: application/json";
	//Curl options
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_USERAGENT, "amoCRM-API-client/2.0");
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($curl, CURLOPT_URL, $link);
	curl_setopt($curl, CURLOPT_HEADER,false);
	curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__)."/cookie.txt");
	curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__)."/cookie.txt");
	$out = curl_exec($curl);
	curl_close($curl);
	$result = json_decode($out,TRUE);
}
