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

define("IN_CMS", true);
$admin_pages = 1;
require_once('common.php');
extract($_GET);

$p = isset($_GET['p']) ? scrub_input($_GET['p'], ['type' => 'alphanum', 'max_length' => 50]) : '';
//Checks is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] != 1) {
    $errorMsg = LOGIN_TEXT;
    include(BASE_PATH . 'header.php');
} else {
//If nothing goes to frontpage
    // page authentication
    array_push($page_lvl, "Admin");
    include(INCLUDES_PATH . 'authentication.php');

    define("IN_ADMIN_CMS", true);
    if (empty($n) || $n == '') {
        include(ADMIN_PATH . 'admin.php');
    }elseif($n == 'plugins'){
        if (file_exists(PLUGINS_PATH .'/'.$p.'/admin')) {
            include(PLUGINS_PATH .'/'.$p.'/admin/admin.php');
        }else{
            header("Location: admin.php");
        }
    } else {
        if (file_exists(ADMIN_PATH . $n)) {
            include(ADMIN_PATH . $n . '/' . $n . '.php');
        }
    }

    include('./footer.php');
}
?>