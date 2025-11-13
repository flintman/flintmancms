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

// Verify CSRF token if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
}

$sql = "SELECT * FROM flintmancms_logs ORDER by id DESC";
$result = $db->sql_query($sql);
While ($data = $db->sql_fetchrow($result)) {

    if (isset($_POST[$data['id']])) {
        $sql2 = sprintf("DELETE FROM flintmancms_logs WHERE id=%s",
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
    // Build HTML table for List.js
    $content = '<form method="post" action="admin.php?n=logs" id="logs-form">';
    $content .= '<div id="logs-list">
        <input class="search user-search-bar form-control" placeholder="Search logs..." />
        <table id="logs-table" class="listjs-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="sort" data-sort="id">ID</th>
                    <th class="sort" data-sort="error">Error</th>
                    <th class="sort" data-sort="time">Time</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody class="list">
    ';
    foreach ($logs as $row) {
        $content .= '<tr>' .
            '<td class="id">' . $row['id'] . '</td>' .
            '<td class="error">' . htmlspecialchars($row['error']) . '</td>' .
            '<td class="time">' . $row['time'] . '</td>' .
            '<td>' . $row['check'] . '</td>' .
            '</tr>';
    }
    $content .= '</tbody></table>';
    $content .= '<div id="logs-pagination"><ul class="pagination"></ul></div></div>';
    $content .= '<script>
        window.addEventListener("DOMContentLoaded", function() {
            var logsListContainer = document.getElementById("logs-list");
            var options = {
                valueNames: ["id", "error", "time"],
                pagination: true,
                page: 10,
                searchClass: "search",
                listClass: "list"
            };
            var listObj = new List("logs-list", options);
            var pagDiv = document.getElementById("logs-pagination");
            var pagList = logsListContainer.getElementsByClassName("pagination")[0];
            if (pagDiv && pagList) {
                pagDiv.appendChild(pagList);
            }
        });
    </script>';
    $button = '<input type="submit" value="'.DELETE_TEXT.'" name="submit" class="button" onclick="return confirm(\'Are you sure you want to delete the selected logs?\');">';
    $logs_back ='<a href="admin.php" class="button">'.BACK_TEXT.'</a>';
    $content .= $button;
    $content .= '</form>';

$smarty->assign(
        array(
            'form_action' => "admin.php?n=logs",
            'content' => $content,
            'logs_back' => $logs_back
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/logs.htm');
?>
