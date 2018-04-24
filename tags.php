<?php

ini_set('max_execution_time', 1800);

// Авторизация
$user = array(
	'USER_LOGIN'=>'amolyakov@team.amocrm.com',
	'USER_HASH'=>'691c2c8c35794e95be679e7a21d40c40'
);
$subdomain = 'newtestdemo';
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
	echo 'Авторизация прошла успешно';
} else {
	echo 'Ошибка авторизации';
}

$tags_names = ['34534', 'tag-test'];
$leads_result = true;
$i = 0;
$leads_update_array = [];
$notes_add_array = [];

while ($leads_result) {
	sleep(1);
	$limit_offset = $i*500;
	$i++;
	echo $i;

	// Получение списка сделок

	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/leads?limit_rows=500&limit_offset='.$limit_offset;
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

//	echo '<pre>';
//	var_dump($leads_result);
//	echo '</pre>';

	$leads = $leads_result['_embedded']['items'];

//	echo '<pre>';
//	var_dump($leads);
//	echo '</pre>';

	foreach ($leads as $lead) {
		$lead_id = $lead['id'];
		$lead_updated_at = time();
		$lead_tags_names = [];
		$leave_tags = '';
		$lead_tags = $lead['tags'];

		foreach ($lead_tags as $lead_tag) {
			$lead_tags_names[] = $lead_tag['name'];
		}
		$tags_to_del = array_intersect($lead_tags_names, $tags_names);
		$leave_tags_arr = array_diff($lead_tags_names, $tags_names);
		foreach ($leave_tags_arr as $leave_tag) {
			$leave_tags .= $leave_tag . ',';
		}
//		echo '<pre>';
//		echo $lead_id . '<br> Теги к удалению<br>';
//		var_dump($tags_to_del);
//		echo '<br> Теги оставляем<br>';
//		var_dump($leave_tags);
//		echo '</pre>';
		$note_text = "Удалены теги: ";
		foreach ($tags_to_del as $tag_to_del) {
			$note_text .= $tag_to_del . ', ';
		}
		//	echo '<pre>';
		//	var_dump($leave_tags);
		//	echo '</pre>';
		if (count($tags_to_del) > 0) {
			$leads_update_array[] = array('id' => $lead_id, 'updated_at' => $lead_updated_at, 'tags' => $leave_tags);
			$notes_add_array[] = array('element_id' => $lead_id, 'element_type' => '2', 'note_type' => '4', 'text' => $note_text);
		}
	}
}

echo '<pre>';
echo '<br> UPDATE-LEADS - <br>';
var_dump($leads_update_array);
echo '<br> NOTES-ADD - <br>';
var_dump($notes_add_array);
echo '</pre>';

//// Удаление тегов из сделок
//if (count($leads_update_array) > 0) {
//	$data = array (
//		'update' => $leads_update_array,
//	);
//
//	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/leads/';
//
//	//Curl options
//	$curl = curl_init();
//	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
//	curl_setopt($curl, CURLOPT_USERAGENT, "amoCRM-API-client/2.0");
//	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
//	curl_setopt($curl, CURLOPT_URL, $link);
//	curl_setopt($curl, CURLOPT_HEADER,false);
//	curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__)."/cookie.txt");
//	curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__)."/cookie.txt");
//	$out = curl_exec($curl);
//	curl_close($curl);
//	$result = json_decode($out,TRUE);
//
////		echo '<pre>';
////		var_dump($result);
////		echo '</pre>';
//
//
//	// Добавление примечаний в сделки
//
//	$data = array (
//		'add' => $notes_add_array,
//	);
//	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/notes';
//
//	//Curl options
//	$curl = curl_init();
//	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
//	curl_setopt($curl, CURLOPT_USERAGENT, "amoCRM-API-client/2.0");
//	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
//	curl_setopt($curl, CURLOPT_URL, $link);
//	curl_setopt($curl, CURLOPT_HEADER,false);
//	curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__)."/cookie.txt");
//	curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__)."/cookie.txt");
//	$out = curl_exec($curl);
//	curl_close($curl);
//	$result = json_decode($out,TRUE);
//
//	echo '<pre>';
//	var_dump($result);
//	echo '</pre>';
//}