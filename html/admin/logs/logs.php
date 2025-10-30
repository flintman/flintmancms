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

// page authentication
array_push($page_lvl, "Admin");
include(INCLUDES_PATH . 'authentication.php');

$logs = array();

$sql = "SELECT * FROM " . DB_PREFIX . "_logs ORDER by id DESC";
$result = $db->sql_query($sql);
While ($data = $db->sql_fetchrow($result)) {

    if (isset($_POST[$data['id']])) {
        $sql2 = sprintf("DELETE FROM " . DB_PREFIX . "_logs WHERE id=%s",
                        quote_smart($data['id']));
        $db->sql_query($sql2)
                or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    } else {

        array_push($logs, array(
            'id' => $data['id'],
            'error' => $data['error'],
            'time' => date('Y-m-d H:i:s', $data['timestamp']),
            'check' => '<input type="checkbox" name="' . $data['id'] . '" value="1">'
        ));
    }
}

$report->setMainAttributes('width="450px" cellpadding="0" cellspacing="0" border="0"');
$report->setFieldHeadingAttributes('class="header"');
$report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
$report->addOutputColumn('id', 'Name', 'left');
$report->addOutputColumn('error', 'Error', 'center');
$report->addOutputColumn('time', 'Time', 'left');
$report->addOutputColumn('check', '', 'left');
$content = $report->getListFromArray($logs);
$button = '<input type="submit" value="'.DELETE_TEXT.'" name="submit" class="button">';
$logs_back ='<a href="admin.php" class="button">'.BACK_TEXT.'</a>';

$smarty->assign(
        array(
            'form_action' => "admin.php?n=logs",
            'content' => $content,
            'button' => $button,
            'logs_back' => $logs_back
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/logs.htm');
?>
