<?php

ini_set('max_execution_time', 1800);

require_once 'auth.php';
require_once 'functions.php';

$subdomain = 'newtestdemo';
$tags_names = ['34534', 'new!']; // Теги к удалению

$update_arrays = make_update_arrays($subdomain, $tags_names);
$leads_to_update = $update_arrays[0];
$notes_to_add = $update_arrays[0];

// Удаление тегов из сделок
$updated_leads = make_post($subdomain, 'leads', $leads_to_update);

// Добавление примечаний в сделки
$added_notes = make_post($subdomain, 'notes', $notes_to_add);