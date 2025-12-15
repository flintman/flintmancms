<?php
if (!defined('IN_CMS')) { die('Direct access not permitted'); }
require_once __DIR__ . '/../variable.php';

global $db;

function taplist_admin_get_config($db) {
    $sql = "SELECT * FROM flintmancms_taplist_config ORDER BY id DESC LIMIT 1";
    $result = $db->sql_query($sql);
    if ($result) {
        return $db->sql_fetchrow($result);
    }
    return null;
}

function taplist_admin_save_config($db, $api_key, $folders, $title, $refresh_interval) {
    $folders_str = implode(',', $folders);
    $sql = "INSERT INTO flintmancms_taplist_config (api_key, selected_folders, title, refresh_interval) VALUES (?, ?, ?, ?)";
    $params = array($api_key, $folders_str, $title, $refresh_interval);
    $db->sql_prepare($sql, $params);
}

function taplist_admin_fetch_folders($api_key) {
    if (!$api_key) return array();
    $headers = array('X-API-KEY: ' . $api_key);
    $url = 'https://api.brewersfriend.com/v1/brewsessions';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if (!isset($data['brewsessions'])) return array();
    $folders = array();
    foreach ($data['brewsessions'] as $session) {
        if (!empty($session['folder_name'])) {
            $folders[] = $session['folder_name'];
        }
    }
    // Remove duplicates and sort
    $folders = array_unique($folders);
    sort($folders);
    return $folders;
}

$taplist_config = taplist_admin_get_config($db);
$api_key = $taplist_config['api_key'] ?? '';
$title = $taplist_config['title'] ?? 'Taplist';
$refresh_interval = $taplist_config['refresh_interval'] ?? 3600;
$selected_folders = isset($taplist_config['selected_folders']) ? explode(',', $taplist_config['selected_folders']) : array();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = trim($_POST['api_key'] ?? '');
    $title = trim($_POST['title'] ?? 'Taplist');
    $refresh_interval = intval($_POST['refresh_interval'] ?? 3600);
    $folders = isset($_POST['folders']) ? $_POST['folders'] : array();
    taplist_admin_save_config($db, $api_key, $folders, $title, $refresh_interval);
    $selected_folders = $folders;
    $message = 'Settings saved.';
    // After saving API key, fetch folders with the new key
    $all_folders = taplist_admin_fetch_folders($api_key);
} else {
    $all_folders = taplist_admin_fetch_folders($api_key);
}

// Assign variables for Smarty
global $smarty;
$smarty->assign(array(
    'form_action' => '',
    'content' => '', // Not used, but for compatibility
    'api_key' => $api_key,
    'title' => $title,
    'refresh_interval' => $refresh_interval,
    'all_folders' => $all_folders,
    'selected_folders' => $selected_folders,
    'message' => $message,
));

// Output page header and template
include(BASE_PATH . 'header.php');
$smarty->display(PLUGINS_PATH . '/taplist/template/admin.htm');
