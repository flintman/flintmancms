<?php
/* * *************************************************************************
 *  Copyright (C) 2010  William Bellavance
 *                      Flintman Computers
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * ************************************************************************* */

define('SITE_VERSION','2.0.0');

// Security Headers - Set before any output
if (!headers_sent()) {
    // Prevent clickjacking attacks
    header("X-Frame-Options: SAMEORIGIN");

    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");

    // XSS Protection for legacy browsers
    header("X-XSS-Protection: 1; mode=block");

    // Referrer Policy - control information sent in referrer header
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Remove PHP version information
    header_remove("X-Powered-By");

    // Content Security Policy - adjust as needed for your requirements
    // Note: 'unsafe-inline' and 'unsafe-eval' are needed for TinyMCE and Smarty templates
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self';");

    // HSTS - HTTP Strict Transport Security (only when ENABLE_HSTS=true and using HTTPS)
    $enableHSTS = getenv('ENABLE_HSTS');
    if ($enableHSTS === 'true' || $enableHSTS === '1') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
}

//setup of directorys
define('PLUGINS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR);
define('COMPONENTS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR);
define('LANGUAGES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR);
define('TEMPLATES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR);
define('ADMIN_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);
define('FUNCTIONS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR);
define('BASE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Class includes
require_once(ADMIN_PATH . 'config.php');
require_once(INCLUDES_PATH . 'mysql.php');
require_once(FUNCTIONS_PATH . 'functions.php');
require_once(FUNCTIONS_PATH . 'functions_plugins.php');
require_once(INCLUDES_PATH . 'report.php');
require_once(INCLUDES_PATH . 'smarty-5.6.0/libs/Smarty.class.php');
require_once(INCLUDES_PATH . 'PHPMailer/src/Exception.php');
require_once(INCLUDES_PATH . 'PHPMailer/src/PHPMailer.php');
require_once(INCLUDES_PATH . 'PHPMailer/src/SMTP.php');
require_once(INCLUDES_PATH . 'scripts/email.php');

// reports for all data listing
$report = new ReportList;

// database connection
global $db;
$db = new sql_db(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$db->db_connect_id) {
    $errorMsg = "There seems to be a problem with the MySQL server, sorry for the inconvenience.<br>
        <br>We should be back shortly.";
}

//Gets Config Data
global $config;
$sql = "SELECT * FROM flintmancms_config";
$result = $db->sql_query($sql);
while ($data = $db->sql_fetchrow($result)) {
    $config[$data['name']] = $data['value'];
}

//Setup of the language files
$language_directory = LANGUAGES_PATH. $config['language'].'/*.php';

foreach (glob($language_directory) as $filename)
{
    include $filename;
}

$sql = "SELECT * FROM flintmancms_version ORDER BY version_number DESC";
$result = $db->sql_query($sql);
$data = $db->sql_fetchrow($result);

define('VERSION', $data['version_desc']);
define('VERSION_NUMBER', $data['version_number']);

$session_name = md5($config['site_name']);

// Start session with security configurations (settings configured in php.ini via Dockerfile)
// - session.cookie_httponly=1: Prevents JavaScript access to cookies (XSS protection)
// - session.cookie_samesite=Strict: Prevents CSRF attacks
// - session.use_strict_mode=1: Prevents session fixation
// - session.cookie_secure: Set to 1 in production with HTTPS
if (session_status() === PHP_SESSION_NONE) {
    session_name($session_name);
    session_start();
}

// Session validation function
function validate_session() {
    // Get configuration from environment variables with defaults
    $max_lifetime = (int)(getenv('SESSION_MAX_LIFETIME') ?: 86400);  // 24 hours default
    $inactivity_timeout = (int)(getenv('SESSION_INACTIVITY_TIMEOUT') ?: 7200);  // 2 hours default
    $refresh_interval = (int)(getenv('SESSION_REFRESH_INTERVAL') ?: 3600);  // 1 hour default

    // Create session fingerprint on first access
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = hash('sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
    }

    // Verify fingerprint matches current request
    $current_fingerprint = hash('sha256',
        ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
        ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
    );

    if ($_SESSION['fingerprint'] !== $current_fingerprint) {
        // Session hijacking detected
        session_destroy();
        session_start();
        die("Session validation failed. Please log in again.");
    }

    // Check session age (maximum lifetime)
    if (time() - $_SESSION['created'] > $max_lifetime) {
        session_destroy();
        session_start();
        header("Location: index.php?n=login&msg=session_expired");
        exit;
    }

    // Check for inactivity timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactivity_timeout) {
        session_destroy();
        session_start();
        header("Location: index.php?n=login&msg=session_timeout");
        exit;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();

    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_refresh']) ||
        (time() - $_SESSION['last_refresh']) > $refresh_interval) {
        session_regenerate_id(true);
        $_SESSION['last_refresh'] = time();
    }
}

// Validate session for logged-in users
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] == 1) {
    validate_session();
}

$scriptAdd = '';

//Setups page lvls
global $page_lvl;
$page_lvl = array();

// Initialize Smarty
use Smarty\Smarty;
$smarty = new Smarty();
$smarty->setTemplateDir('templates/');
$smarty->setCompileDir('templates_c/');

// XSS Protection: Auto-escaping disabled by default
// This CMS builds HTML dynamically, so we escape user data at output points instead
// Use htmlspecialchars() when outputting user-controlled data in PHP
// Use {$variable|escape} in templates for user input

// CSRF Protection Functions
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// get auth type
require_once(INCLUDES_PATH . '/auth.php');

global $admin_pages;
if ($config['allow_login'] || $admin_pages == 1) {
    $is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'];
    $username = $is_logged_in ? $_SESSION['username'] : '';
    $smarty->assign([
        'is_logged_in' => $is_logged_in,
        'allow_login' => $admin_pages == 1 ? true : $config['allow_login'],
        'username' => $username,
        'csrf_token' => generate_csrf_token(),
    ]);
}
?>
