<?php

ini_set('max_execution_time', 1800);

// Авторизация
$user = array(
	'USER_LOGIN'=>'amolyakov@team.amocrm.com', #Ваш логин (электронная почта)
	'USER_HASH'=>'691c2c8c35794e95be679e7a21d40c40' #Хэш для доступа к API (смотрите в профиле пользователя)
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

if(isset($Response['auth'])) #Флаг авторизации доступен в свойстве "auth"
{
	echo "Авторизация прошла успешно\n";
} else {
	echo "Ошибка авторизации\n";
}

$headers[] = "Accept: application/json";
$leads_array = [];

for ($i=0; $i<5; $i++) {
	sleep(1);
	$offset = $i*500;
	$i++;

	$link = 'https://newdemonew.amocrm.ru/api/v2/leads?limit_rows=500&limit_offset='.$offset;

//Curl options
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
	$result = json_decode($out,TRUE);

	$leads = $result['_embedded']['items'];
	echo count($leads) . " Сделок\n";
	$leads_array[] = $leads;
}

$update_array = [];

foreach ($leads_array as $leads_array_item) {
	foreach ($leads_array_item as $item) {
		$lead_id = $item['id'];
		$time = time();
		$tags = 'tag,tag1,34534';

		$update_array[] = array('id' => $lead_id, 'updated_at' => $time, 'tags' => $tags);
	}
	echo count($update_array) . " Сделок  к апдейту\n";
}

$data = array (
	'update' => $update_array,
);
$link = "https://newdemonew.amocrm.ru/api/v2/leads/";

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

var_dump($result);


