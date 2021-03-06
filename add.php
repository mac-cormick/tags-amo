<?php

// Авторизация
$user = array(
	'USER_LOGIN'=>'amolyakov@team.amocrm.com', #Ваш логин (электронная почта)
	'USER_HASH'=>'691c2c8c35794e95be679e7a21d40c40' #Хэш для доступа к API (смотрите в профиле пользователя)
);
$subdomain = 'testovii3';

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


$tags = ['закрытый','Партнёр','Партнер', 'tag', 'tag1', '2454-234', 'new-tag', '4341-qwer', 'qwerqwe!', 'tag-test', '34534', 'теги удаление', 'постоянные клиенты', 'покупочки', 'семинар3', 'tag34', 'tag1-25', '2454-34'];

// Добавление сделок с рандомными тегами из массива тегов
for ($i=0; $i<1; $i++) {
	sleep(1);
	$leads = [];
	for($x=0; $x<50; $x++) {
		$lead_name = md5(uniqid(rand(), true));
		$tags_rand_arr = array_rand($tags, 7);
		$tags_str = '';
		foreach ($tags_rand_arr as $item) {
			$tags_str .= $tags[$item] . ',';
		}
		$leads[] = array('name' => $lead_name, 'tags' => $tags_str);
	}
	$data = array (
		'add' => $leads,
	);

	$link = 'https://'.$subdomain.'.amocrm.ru/api/v2/leads';

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

	if (is_array($result)) {
		echo count($result["_embedded"]["items"]) . " сделок добавлено\n";
	}

//	echo '<pre>';
//	var_dump($result);
//	echo '</pre>';
}


