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
require_once(INCLUDES_PATH . 'SmartyMenu-1.1/libs/SmartyMenu.class.php');
require_once(INCLUDES_PATH . 'SmartyMenu-1.1/plugins/function.menu_init.php');
require_once(INCLUDES_PATH . 'SmartyMenu-1.1/plugins/function.menu.php');

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

$scriptAdd = '';

//Setups page lvls
global $page_lvl;
$page_lvl = array();

// Initialize Smarty
use Smarty\Smarty;
$smarty = new Smarty();
$smarty->setTemplateDir('templates/');
$smarty->setCompileDir('templates_c/');

// get auth type
require_once(INCLUDES_PATH . '/auth.php');

If ($config['allow_login'] || $admin_pages == 1) {
    if ($_SESSION['user_logged_in']) {
        $login_data = "Welcome " . $_SESSION['username'];
        $register_link = '<a href="index.php?n=logout">Logout</a>';
    } else {
        $login_data = '<form action="index.php" method="POST">
		  <input name="username" type="text" value="username" size="15" maxlength="45" onFocus="if(this.value==\'username\')this.value=\'\';">
                  <input name="password" type="password" value="password" size="15" onFocus="if(this.value==\'password\')this.value=\'\';"><br>
		  <input type="submit" name="login" value="Login" style="font-size:10px" class="button">
		  <input type="checkbox" checked="checked" name="autologin"> Remember Me!
                  </form>';
        $register_link = '<a href="index.php?n=register" class="button">Register</a> ';
    }
}
?>
