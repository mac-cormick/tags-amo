<?php

// Получение списка сделок
function get_leads($subdomain, $limit_offset) {
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
    $leads = json_decode($out,TRUE);

    return $leads;
}

// Формирование массивов для апдейта сделок и добавления примечаний
function make_update_arrays($subdomain, $tags_names) {
    $update_arrays = [];
    $leads_result = true;
    $i = 0;

    while ($leads_result) {
        sleep(1);
        $limit_offset = $i*500;
        $i++;
        echo $i;

        $leads_result = get_leads($subdomain, $limit_offset);

        if (!$leads_result) {
            break;
        }

        $leads = $leads_result['_embedded']['items'];
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
            $tags_to_del = array_intersect($lead_tags_names, $tags_names);
            $leave_tags_arr = array_diff($lead_tags_names, $tags_names);
            foreach ($leave_tags_arr as $leave_tag) {
                $leave_tags .= $leave_tag . ',';
            }

            $note_text = "Удалены теги: ";
            foreach ($tags_to_del as $tag_to_del) {
                $note_text .= $tag_to_del . ', ';
            }

            if (count($tags_to_del) > 0) {
                $leads_update_array[] = array('id' => $lead_id, 'updated_at' => $lead_updated_at, 'tags' => $leave_tags);
                $notes_add_array[] = array('element_id' => $lead_id, 'element_type' => '2', 'note_type' => '4', 'text' => $note_text);
            }
        }
        $update_arrays[] = $leads_update_array;
        $update_arrays[] = $notes_add_array;
    }
    return $update_arrays;
}

function make_post($subdomain, $essence, $data_array) {
    $result = [];
    foreach ($data_array as $item) {
        sleep(1);
        $data = array(
            'update' => $item,
        );

        $link = 'https://'.$subdomain.'.amocrm.ru/api/v2/'.$essence.'/';
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
    return $result;
}
