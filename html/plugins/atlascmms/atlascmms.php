<?php
/* =========================================================================
 * FlintmanCMS AtlasCMMS Plugin — Entry Point
 *
 * Thin dispatcher: boots auth, config, API client, then delegates all
 * HTML rendering to AtlasCmmsRenderer (functions/AtlasCmmsRenderer.php).
 * Helper functions live in functions/common.php.
 * JS split: atlascmms.js (core), atlascmms-search.js, atlascmms-billing.js
 * ========================================================================= */

if (!defined('IN_CMS')) { die("ERROR - Hacking attempt"); }

/* ---- Resources ---------------------------------------------------------- */
$scriptAdd .= '<link rel="stylesheet" href="plugins/atlascmms/css/atlascmms.css?v=17" type="text/css" />';
$scriptAdd .= '<script src="plugins/atlascmms/js/atlascmms.js?v=23"          type="text/javascript"></script>';
$scriptAdd .= '<script src="plugins/atlascmms/js/atlascmms-search.js?v=4"    type="text/javascript"></script>';
$scriptAdd .= '<script src="plugins/atlascmms/js/atlascmms-billing.js?v=1"   type="text/javascript"></script>';

/* ---- Load helpers & API client ------------------------------------------ */
include dirname(__FILE__) . '/functions/common.php';
include dirname(__FILE__) . '/api/ApiClient.php';

/* ---- CMS permission check ----------------------------------------------- */
$p = isset($_GET['p']) ? scrub_input($_GET['p'], ['type' => 'alphanum', 'max_length' => 50]) : '';

$sql    = sprintf("SELECT * FROM flintmancms_plugins WHERE name=%s", quote_smart($p));
$result = $db->sql_query($sql);
$ids    = $db->sql_fetchrow($result);

$page_lvl = isset($page_lvl) ? $page_lvl : [];
if ($ids) {
    $sql2   = sprintf("SELECT * FROM flintmancms_group_links WHERE type='plugins' AND type_id=%s", quote_smart($ids['id']));
    $result = $db->sql_query($sql2);
    while ($data = $db->sql_fetchrow($result)) {
        $sql3    = sprintf("SELECT * FROM flintmancms_groups WHERE id=%s", quote_smart($data['group_id']));
        $result2 = $db->sql_query($sql3);
        $data2   = $db->sql_fetchrow($result2);
        if ($data2) { array_push($page_lvl, $data2['name']); }
    }
}

include INCLUDES_PATH . 'authentication.php';

/* ---- Init --------------------------------------------------------------- */
$title  = 'AtlasCMMS Integration';
$body   = '';
$action = isset($_GET['action']) ? scrub_input($_GET['action'], ['max_length' => 30]) : 'workorders';

/* ---- DB config ---------------------------------------------------------- */
$sql              = "SELECT * FROM flintmancms_atlascmms_config WHERE id=1 AND is_active=1";
$result           = $db->sql_query($sql);
$atlascmms_config = $db->sql_fetchrow($result);

if (!$atlascmms_config) {
    $body = '<div class="alert alert-warning">AtlasCMMS is not configured. Please contact an administrator.</div>';
    $smarty->assign('body', $body);
    $smarty->assign('title', $title);
    include BASE_PATH . 'header.php';
    $smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
    return;
}

/* ---- Session auth ------------------------------------------------------- */

// Logout
if ($action === 'logout') {
    unset($_SESSION['atlascmms_token'], $_SESSION['atlascmms_auth_mode']);
    $body = '<script>window.location.href="?n=plugins&p=atlascmms";</script>';
    $smarty->assign('body', $body);
    $smarty->assign('title', $title);
    include BASE_PATH . 'header.php';
    $smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
    return;
}

// Login form submission
if (isset($_POST['atlascmms_login_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $loginError = 'Invalid request. Please try again.';
    } else {
        $auth_type = (isset($_POST['auth_type']) && $_POST['auth_type'] === 'signin') ? 'signin' : 'api_key';

        if ($auth_type === 'api_key') {
            $provided_key = scrub_input($_POST['api_key'] ?? '', ['max_length' => 500]);
            if (empty($provided_key)) {
                $loginError = 'Please enter an API key.';
            } else {
                $testClient = new AtlasCmmsApiClient($atlascmms_config['api_url'], $atlascmms_config['minio_url']);
                $testClient->setApiKey($provided_key);
                $testResult = $testClient->testConnection();
                if ($testResult['success']) {
                    session_regenerate_id(true);
                    $_SESSION['atlascmms_token']     = $provided_key;
                    $_SESSION['atlascmms_auth_mode'] = 'api_key';
                    $body = '<script>window.location.href="?n=plugins&p=atlascmms&action=workorders";</script>';
                    $smarty->assign('body', $body);
                    $smarty->assign('title', $title);
                    include BASE_PATH . 'header.php';
                    $smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
                    return;
                } else {
                    $loginError = 'API key invalid or connection failed: '
                                . htmlspecialchars($testResult['message'], ENT_QUOTES, 'UTF-8');
                }
            }
        } else {
            $email    = scrub_input($_POST['email']    ?? '', ['type' => 'email', 'max_length' => 200]);
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            if (empty($email) || empty($password)) {
                $loginError = 'Please enter your email and password.';
            } else {
                $signInClient = new AtlasCmmsApiClient($atlascmms_config['api_url'], $atlascmms_config['minio_url']);
                if (!empty($atlascmms_config['api_key'])) {
                    $signInClient->setApiKey($atlascmms_config['api_key']);
                }
                $signInResult = $signInClient->login($email, $password);
                if (!empty($signInResult['accessToken'])) {
                    session_regenerate_id(true);
                    $_SESSION['atlascmms_token']     = $signInResult['accessToken'];
                    $_SESSION['atlascmms_auth_mode'] = 'bearer';
                    $body = '<script>window.location.href="?n=plugins&p=atlascmms&action=workorders";</script>';
                    $smarty->assign('body', $body);
                    $smarty->assign('title', $title);
                    include BASE_PATH . 'header.php';
                    $smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
                    return;
                } else {
                    $loginError = 'Sign in failed. Check your email and password.';
                }
            }
        }
    }
}

// Show login form if not authenticated
if (empty($_SESSION['atlascmms_token'])) {
    $title      = 'AtlasCMMS Login';
    $loginError = $loginError ?? '';
    $csrf       = generate_csrf_token();
    $self       = htmlspecialchars('?n=plugins&p=atlascmms', ENT_QUOTES, 'UTF-8');

    $body  = '<div class="atlascmms-login-wrap"><div class="atlascmms-login-box">';
    $body .= '<h2 class="atlascmms-login-title">AtlasCMMS</h2>';
    if ($loginError) {
        $body .= '<div class="alert alert-danger">' . $loginError . '</div>';
    }

    $body .= '<div class="login-tabs">';
    $body .= '<button type="button" class="login-tab login-tab-active" onclick="atlascmmsShowTab(\'api_key\')">API Key</button>';
    $body .= '<button type="button" class="login-tab" onclick="atlascmmsShowTab(\'signin\')">Sign In</button>';
    $body .= '</div>';

    $body .= '<form method="post" action="' . $self . '" id="atlascmms-form-api_key" class="atlascmms-login-form">';
    $body .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">';
    $body .= '<input type="hidden" name="auth_type" value="api_key">';
    $body .= '<label class="login-label">API Key</label>';
    $body .= '<input type="password" name="api_key" class="login-input" placeholder="Enter your API key" autocomplete="current-password" required>';
    $body .= '<button type="submit" name="atlascmms_login_submit" value="1" class="login-btn">Connect</button>';
    $body .= '</form>';

    $body .= '<form method="post" action="' . $self . '" id="atlascmms-form-signin" class="atlascmms-login-form" style="display:none">';
    $body .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">';
    $body .= '<input type="hidden" name="auth_type" value="signin">';
    $body .= '<label class="login-label">Email</label>';
    $body .= '<input type="email" name="email" class="login-input" placeholder="you@example.com" autocomplete="email" required>';
    $body .= '<label class="login-label">Password</label>';
    $body .= '<input type="password" name="password" class="login-input" placeholder="Password" autocomplete="current-password" required>';
    $body .= '<button type="submit" name="atlascmms_login_submit" value="1" class="login-btn">Sign In</button>';
    $body .= '</form>';

    $body .= '</div></div>';
    $body .= '<script>function atlascmmsShowTab(t){';
    $body .= 'document.querySelectorAll(".atlascmms-login-form").forEach(function(f){f.style.display="none";});';
    $body .= 'document.querySelectorAll(".login-tab").forEach(function(b){b.classList.remove("login-tab-active");});';
    $body .= 'document.getElementById("atlascmms-form-"+t).style.display="block";';
    $body .= 'event.target.classList.add("login-tab-active");}</script>';

    $smarty->assign('body', $body);
    $smarty->assign('title', $title);
    include BASE_PATH . 'header.php';
    $smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
    return;
}

/* ---- Init API client & renderer ----------------------------------------- */
$client = new AtlasCmmsApiClient($atlascmms_config['api_url'], $atlascmms_config['minio_url']);
if ($_SESSION['atlascmms_auth_mode'] === 'api_key') {
    $client->setApiKey($_SESSION['atlascmms_token']);
} else {
    $client->setToken($_SESSION['atlascmms_token']);
}

include dirname(__FILE__) . '/functions/AtlasCmmsRenderer.php';
$renderer = new AtlasCmmsRenderer($client);

/* ---- Action dispatch ---------------------------------------------------- */
if ($action === 'asset_detail' && isset($_GET['id'])) {
    $id    = intval($_GET['id']);
    $title = 'Asset';
    $body  = atlascmms_nav('asset_detail') . $renderer->renderAssetDetail($id, $title);

} elseif ($action === 'assets' || $action === 'list') {
    $title = 'Assets';
    $page  = isset($_GET['page']) ? max(0, intval($_GET['page'])) : 0;
    $body  = atlascmms_nav('assets') . $renderer->renderAssets($page);

} elseif ($action === 'workorders') {
    $title            = 'Work Orders';
    $page             = isset($_GET['page']) ? max(0, intval($_GET['page'])) : 0;
    $perPage          = 50;
    $allowed_statuses = ['', 'OPEN', 'IN_PROGRESS', 'ON_HOLD', 'COMPLETED'];
    $statusFilter     = isset($_GET['status']) ? scrub_input($_GET['status'], ['max_length' => 20]) : '';
    if (!in_array($statusFilter, $allowed_statuses)) { $statusFilter = ''; }
    $body = atlascmms_nav('workorders') . $renderer->renderWorkOrders($page, $perPage, $statusFilter);

} elseif ($action === 'workorder_detail' && isset($_GET['id'])) {
    $id   = intval($_GET['id']);
    $title = 'Work Order';
    $body  = atlascmms_nav('workorder_detail') . $renderer->renderWorkOrderDetail($id, $title);

} elseif ($action === 'asset_workorders' && isset($_GET['asset_id'])) {
    $assetId = intval($_GET['asset_id']);
    $title   = 'Work Orders for Asset #' . $assetId;
    $body    = atlascmms_nav('asset_workorders') . $renderer->renderAssetWorkOrders($assetId);

} else {
    $title = 'AtlasCMMS';
    $body  = atlascmms_nav('workorders');
    if (!$client->isAuthenticated()) {
        $body .= '<div class="alert alert-warning">AtlasCMMS API is not configured. Please contact an administrator.</div>';
    } else {
        $body .= '<script>window.location.href="?n=plugins&p=atlascmms&action=workorders";</script>';
    }
}

/* ---- Output ------------------------------------------------------------- */
$smarty->assign('title', $title);
$smarty->assign('body', $body);
include BASE_PATH . 'header.php';
$smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
?>
