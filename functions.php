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
		return 'Error: '.$code;
	} else {
		$out = json_decode($out, TRUE);
		return $out;
	}
}
