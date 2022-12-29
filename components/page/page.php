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

$text = "";
if (!isset($_GET['page_id'])) {
    $page_id = '1';
} else {
    $page_id = scrub_input($_GET['page_id']);
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE
        type='page' AND type_id=%s", quote_smart($page_id));
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {
        $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_groups WHERE id=%s", quote_smart($data['group_id']));
        $result2 = $db->sql_query($sql2);
        $data2 = $db->sql_fetchrow($result2) or
                $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        ;
        array_push($page_lvl, $data2['name']);
    }
    if ($page_id != '1')
        include(INCLUDES_PATH . 'authentication.php');
}


$sql = sprintf("SELECT * FROM " . DB_PREFIX . "_pages WHERE id=%s", quote_smart($page_id));
//Gets Page Info
$result = $db->sql_query($sql);
$data = $db->sql_fetchrow($result) or
        $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;

//If not Active Goes to Maintain 
if ($data['active'] == 0 && $_SESSION['priv'] != "1")
    header("Location: index.php?n=maintain");
if ($data['show_title'] != 0)
    $text = $data['title'];
$body = $data['context'];

$smarty->assign(
        array(
            'text' => $text,
            'body' => $body,
        )
);

//
// Start output of page
//
include(BASE_PATH . 'header.php');

$smarty->display(TEMPLATES_PATH . $config['template'] . '/page.htm');
?>