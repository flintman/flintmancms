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

if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

function email($to, $subject, $message) {
    global $config;


    $mheaders = 'From: ' . $config['site_name'] . '<' . $config['admin_email'] . '>' . "\r\n" .
            'Reply-To: ' . $config['admin_email'] . "\r\n" .
            'Return-Path: <' . $config['admin_email'] . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $mheaders);
}

function dsSlash($string) {
    if (get_magic_quotes_gpc() == 1) {
        return ($string);
    } else {
        return(addslashes($string));
    }
}

function formatHeader($str, $arg) {
    if ($arg == 0) // left side
        return HEADER_SEPARATOR . $str;
    if ($arg == 1) // right side
        return $str . HEADER_SEPARATOR;
}

function count_links($sublink) {
    global $db;
    //Gets total number of menu items
    $count = 1;
     $result = $db->sql_query("SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link = '$sublink'");
 
    While ($data = $db->sql_fetchrow($result)) {
        $count++;
    }
    return $count;
}

function quote_smart($value = "", $nullify = false, $conn = null) {
    //reset default if second parameter is skipped
    $nullify = ($nullify === null) ? (false) : ($nullify);
    //undo slashes for poorly configured servers
    $value = (get_magic_quotes_gpc()) ? (stripslashes($value)) : ($value);
    //check for null/unset/empty strings (takes advantage of short-circuit evals to avoid a warning)
    if ((!isset($value)) || (is_null($value)) || ($value === "")) {
        $value = ($nullify) ? ("NULL") : ("''");
    } else {
        if (is_string($value)) {
            //value is a string and should be quoted; determine best method based on available extensions
            if (function_exists('mysql_real_escape_string')) {
                $value = "'" . (((isset($conn)) && (is_resource($conn))) ? (mysql_real_escape_string($value, $conn)) : (mysql_real_escape_string($value))) . "'";
            } else {
                $value = "'" . mysql_escape_string($value) . "'";
            }
        } else {
            //value is not a string; if not numeric, bail with error
            $value = (is_numeric($value)) ? ($value) : ("'ERROR: unhandled datatype in quote_smart'");
        }
    }
    return $value;
}

function scrub_input($value = "", $html_allowed = false) {
    $value = strip_tags($value, '<br><a>');

    if (!$html_allowed)
        $value = htmlspecialchars($value);

    return $value;
}

function update_config($value, $name) {
    global $db;
    $sql = sprintf("UPDATE " . DB_PREFIX . "_config SET value=%s WHERE name=%s",
                    quote_smart($value), quote_smart($name));
    $db->sql_query($sql) or $errorMsg = "ERROR: " . mysql_error() . " @ Line "
            . __LINE__ . " Of " . __FILE__;
}

//Checks to see if you can display the menu link
function check_menu($priv, $link_id) {
    global $db;
    $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE group_id=%s",
                    quote_smart($priv));
    $result2 = $db->sql_query($sql2);
    while ($data2 = $db->sql_fetchrow($result2)) {
        if ($data2['link_id'] == $link_id) {
            $canview = 1;
            break;
        } else {
            $canview = 0;
        }
    }

    if ($canview)
        return true;
    else
        return false;
}

//Gets link ID
function getlinkID($type, $type_id) {
    global $db;
    if ($type == "plugins") {
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_plugins WHERE id=%s",
                        quote_smart($type_id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        $link ='index.php?n=plugins&p='.$data['name'];
    } else {
        $link = 'index.php?n=page&page_id=' . $type_id;
    }
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_links WHERE link=%s",
                    quote_smart($link));
    $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result)
            or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
    return $data['id'];
}

?>