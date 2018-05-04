<?php

$id = 7643845;

$file_open = fopen(__DIR__ . "/tags-update.json", 'r');
while ($file_open) {
	$item = fgets($file_open);
	$item_str = json_decode($item, true);
//	var_dump($item_str);
	if ($item_str['update']['id'] == $id) {
		var_dump($item_str);
		break;
	}
}
