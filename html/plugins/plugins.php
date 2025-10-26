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
if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}
$p = scrub_input($_GET['p']);

if ($config['maintain'] && ($_SESSION['priv'] < 900 ))
    header("Location: index.php?n=maintain");

if (empty($p) || $p == '') {
     header("Location: index.php");
} elseif (file_exists(PLUGINS_PATH . $p)) {
    include(PLUGINS_PATH . $p . '/' . $p . '.php');
} else {
      header("Location: index.php");
}
?>