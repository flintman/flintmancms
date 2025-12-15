<?php
if (!defined('IN_CMS')) { die('Direct access not permitted'); }

require_once __DIR__ . '/variable.php';

function taplist_get_config($db) {
    $sql = "SELECT * FROM flintmancms_taplist_config ORDER BY id DESC LIMIT 1";
    $result = $db->sql_query($sql);
    if ($result) {
        return $db->sql_fetchrow($result);
    }
    return null;
}

function taplist_fetch_beers($api_key, $selected_folders) {
    $beers = array();
    if (!$api_key || empty($selected_folders)) return $beers;
    $folders = explode(',', $selected_folders);
    $headers = array('X-API-KEY: ' . $api_key);
    $url = 'https://api.brewersfriend.com/v1/brewsessions';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if (!isset($data['brewsessions'])) return $beers;
    foreach ($data['brewsessions'] as $session) {
        if (in_array($session['folder_name'], $folders)) {
            $brew_id = $session['id'];
            $brew_url = 'https://api.brewersfriend.com/v1/brewsessions/' . $brew_id;
            $ch2 = curl_init($brew_url);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            $brew_response = curl_exec($ch2);
            curl_close($ch2);
            $brew_data = json_decode($brew_response, true);
            if (isset($brew_data['brewsessions'][0])) {
                $brew = $brew_data['brewsessions'][0];
                $recipe = isset($brew['recipe']) ? $brew['recipe'] : array();
                $beers[] = array(
                    'title' => $recipe['title'] ?? 'Unknown',
                    'stylename' => $recipe['stylename'] ?? 'Unknown',
                    'abv_alt' => floatval($brew['current_stats']['abv_alt'] ?? 0),
                    'ibutinseth' => intval($recipe['ibutinseth'] ?? 0),
                    'srmmorey' => intval($recipe['srmmorey'] ?? 0),
                    'userdate' => $brew['userdate'] ?? 'Unknown',
                    'id' => intval($recipe['id'] ?? 0),
                );
            }
        }
    }
    return $beers;
}

function taplist_srm_color($srm) {
    $srm_colors = array(
        1 => '#FEEFB3', 2 => '#FDD49E', 3 => '#FDBB84', 4 => '#FDB168', 5 => '#E9A84F',
        6 => '#E38F3D', 7 => '#DB7C26', 8 => '#C96E1F', 10 => '#BE5B1F', 13 => '#A84D15',
        17 => '#8E3E10', 20 => '#7C2D12', 30 => '#6B1E14', 999 => '#5A0E16'
    );
    foreach ($srm_colors as $limit => $color) {
        if ($srm <= $limit) return $color;
    }
    return '#5A0E16';
}

function taplist_show() {
    global $db, $smarty, $config;
    $taplist_config = taplist_get_config($db);
    if (!$taplist_config || !$taplist_config['api_key']) {
        $smarty->assign(array(
            'title' => 'Taplist',
            'beers' => array(),
            'srm_colors' => array(),
            'error' => 'No API key configured. Please set up in admin.'
        ));
        include(BASE_PATH . 'header.php');
        $smarty->display(__DIR__ . '/template/taplist.htm');
        return;
    }
    $beers = taplist_fetch_beers($taplist_config['api_key'], $taplist_config['selected_folders']);
    $title = htmlspecialchars($taplist_config['title'] ?? 'Taplist');
    // SRM color map for Smarty
    $srm_colors = array(
        1 => '#FEEFB3', 2 => '#FDD49E', 3 => '#FDBB84', 4 => '#FDB168', 5 => '#E9A84F',
        6 => '#E38F3D', 7 => '#DB7C26', 8 => '#C96E1F', 10 => '#BE5B1F', 13 => '#A84D15',
        17 => '#8E3E10', 20 => '#7C2D12', 30 => '#6B1E14', 999 => '#5A0E16'
    );
    $smarty->assign(array(
        'title' => $title,
        'beers' => $beers,
        'srm_colors' => $srm_colors,
        'error' => empty($beers) ? 'No beers found for the selected folders.' : ''
    ));
    include(BASE_PATH . 'header.php');
    $smarty->display(__DIR__ . '/template/taplist.htm');
}

taplist_show();