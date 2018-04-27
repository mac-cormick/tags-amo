<?php

ini_set('max_execution_time', 1800);

// Авторизация
$user = array(
	'USER_LOGIN'=>'amolyakov@team.amocrm.com', #Ваш логин (электронная почта)
	'USER_HASH'=>'691c2c8c35794e95be679e7a21d40c40' #Хэш для доступа к API (смотрите в профиле пользователя)
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

if(isset($Response['auth'])) #Флаг авторизации доступен в свойстве "auth"
{
	echo 'Авторизация прошла успешно';
} else {
	echo 'Ошибка авторизации';
}

$data = array (
	'add' =>
		array (
			0 =>
				array (
					'element_id' => '354339',
					'element_type' => '2',
					'note_type' => '25',
					'params' => array('text' => 'test','service' => 'test_service'),
				),
		),
);
$link = "https://newtestdemo.amocrm.ru/api/v2/notes";

$headers[] = "Accept: application/json";

//Curl options
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
curl_setopt($curl, CURLOPT_USERAGENT, "amoCRM-API-client-undefined/2.0");
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
