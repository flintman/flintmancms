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
require_once '../../common.php';


if (isset($_GET['u'])) {
    $username = scrub_input($_GET['u'], ['type' => 'alphanum', 'max_length' => 45]);
    $sql = sprintf("SELECT * FROM flintmancms_profile WHERE username=%s",
            quote_smart($username));
    $result = $db->sql_query($sql);
    $data = $db->sql_numrows();
    if($data){
        echo "Username Already used";
    }  else {
        echo "Good";
    }
}
if (isset($_GET['e'])) {
    $email = scrub_input($_GET['e'], ['type' => 'email']);
    $sql = sprintf("SELECT * FROM flintmancms_profile WHERE email=%s",
            quote_smart($email));
    $result = $db->sql_query($sql);
    $data = $db->sql_numrows();
    if($data){
        echo "Email Already used";
    }  else {
        echo "Good";
    }
}
?>
