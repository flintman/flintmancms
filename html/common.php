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

//setup of directorys
define('PLUGINS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR);
define('COMPONENTS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR);
define('LANGUAGES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR);
define('TEMPLATES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR);
define('ADMIN_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);
define('FUNCTIONS_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR);
define('BASE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('SITE_VERSION','1.0.3');

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
$sql = "SELECT * FROM " . DB_PREFIX . "_config";
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

$sql = "SELECT * FROM " .DB_PREFIX. "_version ORDER BY version_number DESC";
$result = $db->sql_query($sql);
$data = $db->sql_fetchrow($result);

define('VERSION', $data['version_desc']);
define('VERSION_NUMBER', $data['version_number']);

$session_name = md5($config['site_name']);

// Start session early for CSRF protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
