<?php
/* * *************************************************************************
 *  FlintmanCMS AtlasCMMS Plugin - Admin Interface
 *
 *  PURPOSE:
 *  Provides admin interface for configuring Atlas CMMS API connection
 *  and viewing integrated data (assets, work orders).
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access
if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

// Admin-only access
array_push($page_lvl, 'Admin');
include(INCLUDES_PATH . 'authentication.php');

/* ========================================================================
 * INCLUDE THE API CLIENT
 * ======================================================================== */
include(dirname(__FILE__) . '/../api/ApiClient.php');

/* ========================================================================
 * INITIALIZE VARIABLES
 * ======================================================================== */
$form_action = '';
$content = '';
$action = isset($_GET['action']) ? scrub_input($_GET['action'], ['type' => 'alpha', 'max_length' => 20]) : 'settings';

/* ========================================================================
 * GET CURRENT CONFIGURATION
 * ======================================================================== */
$sql = "SELECT * FROM flintmancms_atlascmms_config WHERE id=1";
$result = $db->sql_query($sql);
$atlascmms_config = $db->sql_fetchrow($result);

if (!$atlascmms_config) {
    $sql = "INSERT INTO flintmancms_atlascmms_config (api_url, minio_url, api_key, auth_mode, is_active) " .
           "VALUES ('', '', '', 'api_key', 1)";
    $db->sql_query($sql);
    $atlascmms_config = array(
        'id' => 1,
        'api_key' => '',
        'api_url' => '',
        'minio_url' => '',
        'auth_mode' => 'api_key',
        'is_active' => 1,
        'test_status' => 'untested'
    );
}

/* ========================================================================
 * HANDLE ACTIONS
 * ======================================================================== */

// ACTION: SAVE SETTINGS
if (isset($_POST['action']) && $_POST['action'] == 'save_settings' && isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $api_url   = scrub_input($_POST['api_url'],   ['max_length' => 500]);
    $minio_url = scrub_input($_POST['minio_url'], ['max_length' => 500]);
    $api_key   = scrub_input($_POST['api_key'],   ['max_length' => 500]);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($api_url)) {
        $errorMsg = "API URL is required";
    } elseif (!filter_var($api_url, FILTER_VALIDATE_URL)) {
        $errorMsg = "Invalid API URL format";
    } elseif (!empty($minio_url) && !filter_var($minio_url, FILTER_VALIDATE_URL)) {
        $errorMsg = "Invalid MinIO URL format";
    } else {
        $sql = sprintf(
            "UPDATE flintmancms_atlascmms_config SET api_url=%s, minio_url=%s, api_key=%s, is_active=%s WHERE id=1",
            quote_smart($api_url),
            quote_smart($minio_url),
            quote_smart($api_key),
            quote_smart($is_active)
        );
        if ($db->sql_query($sql)) {
            $_SESSION['noty'] = "Settings saved successfully";
            $atlascmms_config['api_url']   = $api_url;
            $atlascmms_config['minio_url'] = $minio_url;
            $atlascmms_config['api_key']   = $api_key;
            $atlascmms_config['is_active'] = $is_active;
        }
    }
}

// ACTION: TEST CONNECTION
if (isset($_POST['test']) && $_POST['test'] == 'connection') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    $client = new AtlasCmmsApiClient($atlascmms_config['api_url'], $atlascmms_config['minio_url']);
    $client->setApiKey($atlascmms_config['api_key']);

    $result = $client->testConnection();

    $status = $result['success'] ? 'success' : 'failed';
    $sql = sprintf("UPDATE flintmancms_atlascmms_config SET test_status=%s, last_tested=NOW() WHERE id=1",
                   quote_smart($status));
    $db->sql_query($sql);
    $atlascmms_config['test_status'] = $status;
    $atlascmms_config['last_tested'] = date('Y-m-d H:i:s');

    if ($result['success']) {
        $_SESSION['noty'] = "Connection successful!";
    } else {
        $errorMsg = $result['message'];
    }
}

// SETTINGS FORM (the only admin view)
$form_action = "admin.php?n=plugins&p=atlascmms";

$content .= '<h3>AtlasCMMS Settings</h3>';
$content .= '<table width="100%" class="form-table">';

// API URL
$content .= '<tr>';
$content .= '<td width="200"><strong>API URL:</strong></td>';
$content .= '<td>';
$content .= '<input name="api_url" type="url" class="form-control" maxlength="500" style="width:400px" ';
$content .= 'value="' . htmlspecialchars($atlascmms_config['api_url'] ?? '', ENT_QUOTES, 'UTF-8') . '" required placeholder="https://workapi.example.com">';
$content .= '</td></tr>';

// MinIO URL
$content .= '<tr>';
$content .= '<td><strong>MinIO URL <small>(optional)</small>:</strong></td>';
$content .= '<td>';
$content .= '<input name="minio_url" type="url" class="form-control" maxlength="500" style="width:400px" ';
$content .= 'value="' . htmlspecialchars($atlascmms_config['minio_url'] ?? '', ENT_QUOTES, 'UTF-8') . '" placeholder="https://minio.example.com">';
$content .= '</td></tr>';

// API Key
$content .= '<tr>';
$content .= '<td><strong>API Key <small>(x-api-key)</small>:</strong></td>';
$content .= '<td>';
$content .= '<input name="api_key" type="password" class="form-control" maxlength="500" style="width:400px" id="api_key_input" ';
$content .= 'value="' . htmlspecialchars($atlascmms_config['api_key'] ?? '', ENT_QUOTES, 'UTF-8') . '" autocomplete="new-password">';
$content .= '<br><small>Used as <code>x-api-key</code> header. Also required when users sign in with email/password.</small>';
$content .= '</td></tr>';

// Active
$content .= '<tr>';
$content .= '<td><strong>Active:</strong></td>';
$content .= '<td><input type="checkbox" name="is_active" value="1" ' . (($atlascmms_config['is_active'] ?? 0) ? 'checked' : '') . '> Enabled</td>';
$content .= '</tr>';

// Connection status
$content .= '<tr><td><strong>Last Test:</strong></td><td>';
if (($atlascmms_config['test_status'] ?? '') === 'success') {
    $content .= '<span style="color:green">&#10003; Connected</span>';
} elseif (($atlascmms_config['test_status'] ?? '') === 'failed') {
    $content .= '<span style="color:red">&#10007; Failed</span>';
} else {
    $content .= '<span style="color:orange">Not tested</span>';
}
if (!empty($atlascmms_config['last_tested'])) {
    $content .= ' &nbsp;<small>' . htmlspecialchars($atlascmms_config['last_tested'], ENT_QUOTES, 'UTF-8') . '</small>';
}
$content .= '</td></tr>';

$content .= '</table><br>';
$content .= '<input type="hidden" name="action" value="save_settings">';
$content .= '<input type="submit" value="Save Settings" name="submit" class="btn btn-primary">';
$content .= ' &nbsp;<button type="submit" name="test" value="connection" class="button">Test Connection</button>';

/* ========================================================================
 * RENDER TEMPLATE
 * ======================================================================== */
$smarty->assign(array(
    'form_action' => $form_action,
    'content' => $content,
    'title' => 'AtlasCMMS Administration',
    'csrf_token' => generate_csrf_token()
));

include('header.php');
$smarty->display(dirname(__FILE__) . '/../template/page.htm');
?>
