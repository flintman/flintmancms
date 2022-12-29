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
// commons
define("IN_CMS", true);
$admin_pages = 0;
if (file_exists('install/install.php')) {
    header("Location: install/install.php");
    exit;
}
require_once('common.php');

$n = scrub_input($_GET['n']);

if ($config['maintain'] && $n != 'logout') {
    include(COMPONENTS_PATH . 'maintain/maintain.php');
} else {

    if (empty($n) || $n == '') {
        header("Location: ".$config['frt_page']."");
    } else {
        if ($n == 'plugins') {
            include(PLUGINS_PATH . 'plugins.php');
        } elseif ($n == 'logout') {
            logout();
            header("Location: index.php");
        }
        // load called component
        elseif (file_exists(COMPONENTS_PATH . $n)) {
            include(COMPONENTS_PATH . $n . '/' . $n . '.php');
        }
    }
}
include('./footer.php');
?>
