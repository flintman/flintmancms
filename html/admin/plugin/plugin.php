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
$form_action = '';
$active_plugins = array();
$inactive_plugins = array();
$content = '';

if (isset($_GET['action']) && $_GET['action'] == 'active') {
    $id = scrub_input($_GET['id'], ['type' => 'int']);
    if (!isset($_POST['submit'])) {
        $form_action = "admin.php?n=plugin&action=active&id=" . $id . "";
        $sql = sprintf("SELECT * FROM flintmancms_plugins WHERE id=%s",
                        quote_smart($id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result)
                or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
        if ($data['active'] == 0) {
            activate_plugins($data['name'], $id);
            header("Location: admin.php?n=plugin");
        } else {
            $content = "<table style='width: 95%' class='fborder'>
                <tr>
                    <td colspan='2' class='pluginheader'>".ADMIN_PLUGINS_UNINSTALL_OPTIONS_TEXT.$data['name']."</td>
                </tr>
                <tr>
                    <td class='pluginheader2' style='width:75%'>
			".ADMIN_PLUGINS_DELETE_TABLES_TEXT."<div>
                            ".ADMIN_PLUGINS_TABLE_INFO_TEXT."</div>
                    </td>
                    <td class='pluginheader2'>
			<select  name='delete_tables' class='form-control'>
			<option value='1'>".YES_TEXT."</option>
			<option value='0'>".NO_TEXT."</option>
			</select>
                    </td>
                </tr>
                    <td colspan='2' class='pluginheader' style='text-align:center'>
                <input class='button' type='submit' name='submit' value='".CONFIRM_TEXT."' />&nbsp;&nbsp;
                <input class='button' type='submit' name='uninstall_cancel' value='".BACK_TEXT."' onclick=\"location.href='admin.php?n=plugin'; return false;\"/></td>
                </tr>
                </table>";
        }
         $content .= '<input type="hidden" value="' . $data['name'] . '" name="name">';
         $content .= '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
    } elseif (isset($_POST['submit'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $name = $_POST['name'];
        $delete_tables = $_POST['delete_tables'];
        $id = $_GET['id'];
        $message = deactivate_plugins($id, $name, $delete_tables);

        if ($message == "Done")
            header("Location: admin.php?n=plugin");
        else
            $errorMsg = $message;
    }
} else {
    // Verify CSRF token for plugin activation/deactivation
    if (isset($_POST['submit']) && (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token']))) {
        die("CSRF token validation failed");
    }

    // Auto-discover plugins from folders and add to database if not present
    sync_plugins_from_folders();

    $sql = "SELECT * FROM flintmancms_plugins WHERE active ='1'";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {

        $variable_file = PLUGINS_PATH . $data['name'] . '/variable.php';

        // Check if the plugin folder and variable.php file exist
        if (!file_exists($variable_file)) {
            // Plugin folder doesn't exist or is misconfigured - skip it
            continue;
        }

        // Clear any previous plugin variables to avoid conflicts
        unset($plugin_name, $plugin_description, $plugin_version, $plugin_folder, $plugin_db_tables);

        // Use include() instead of require_once() to allow reloading the same file
        include($variable_file);

        // Verify required variables are set
        if (!isset($plugin_name) || !isset($plugin_description) || !isset($plugin_version)) {
            // Variable file didn't set required variables - skip this plugin
            continue;
        }

        $description = "<center>" . $plugin_description . " <br>" .VERSION_TEXT. $plugin_version . "</center>";
        array_push($active_plugins, array(
            'name' => $plugin_name,  // Use the display name from variable.php
            'descrption' => $description,
            'deactive' => '<a href="admin.php?n=plugin&action=active&id=' . $data['id'] . '">'.UNINSTALL_TEXT.'</a>',
            'config' => '<a href="admin.php?n=plugins&p=' . $data['name'] . '">Configure</a>'

        ));
    }

    // Free the result before the next query
    $db->sql_freeresult($result);

    // Build HTML table for active plugins (List.js)
    $active_plugins_html = '<div id="active-plugins-list">
        <input class="search user-search-bar form-control" placeholder="Search active plugins..." />
        <table id="active-plugins-table" class="listjs-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="sort" data-sort="name">Name</th>
                    <th>Configure</th>
                    <th class="sort" data-sort="descrption">Description</th>
                    <th>Uninstall</th>
                </tr>
            </thead>
            <tbody class="list">
';
    foreach ($active_plugins as $row) {
        $active_plugins_html .= '<tr>' .
            '<td class="name">' . htmlspecialchars($row['name']) . '</td>' .
            '<td>' . $row['config'] . '</td>' .
            '<td class="descrption">' . $row['descrption'] . '</td>' .
            '<td>' . $row['deactive'] . '</td>' .
            '</tr>';
    }
    $active_plugins_html .= '</tbody></table>';
    $active_plugins_html .= '<div id="active-plugins-pagination"><ul class="pagination"></ul></div></div>';
    $active_plugins_html .= '<script>
    window.addEventListener("DOMContentLoaded", function() {
        var pluginsList = document.getElementById("active-plugins-list");
        if (pluginsList) {
            var options = {
                valueNames: ["name", "descrption"],
                pagination: true,
                page: 10,
                searchClass: "search",
                listClass: "list",
            };
            var listObj = new List("active-plugins-list", options);
            var pagDiv = document.getElementById("active-plugins-pagination");
            var pagList = pluginsList.getElementsByClassName("pagination")[0];
            if (pagDiv && pagList) {
                pagDiv.appendChild(pagList);
            }
        }
    });
    </script>';

    $sql = "SELECT * FROM flintmancms_plugins WHERE active ='0'";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {

        $variable_file = PLUGINS_PATH . $data['name'] . '/variable.php';

        // Check if the plugin folder and variable.php file exist
        if (!file_exists($variable_file)) {
            // Plugin folder doesn't exist or is misconfigured - skip it
            continue;
        }

        // Clear any previous plugin variables to avoid conflicts
        unset($plugin_name, $plugin_description, $plugin_version, $plugin_folder, $plugin_db_tables);

        // Use include() instead of require_once() to allow reloading the same file
        include($variable_file);

        // Verify required variables are set
        if (!isset($plugin_name) || !isset($plugin_description) || !isset($plugin_version)) {
            // Variable file didn't set required variables - skip this plugin
            continue;
        }

        $description = "<center>" . $plugin_description . " <br>".VERSION_TEXT . $plugin_version . "</center>";
        array_push($inactive_plugins, array(
            'name' => $plugin_name,  // Use the display name from variable.php
            'descrption' => $description,
            'deactive' => '<a href="admin.php?n=plugin&action=active&id=' . $data['id'] . '">'.INSTALL_TEXT.'</a>'
        ));
    }

    // Free the result
    $db->sql_freeresult($result);

    // Build HTML table for inactive plugins (List.js)
    $inactive_plugins_html = '<div id="inactive-plugins-list">
        <input class="search user-search-bar form-control" placeholder="Search inactive plugins..." />
        <table id="inactive-plugins-table" class="listjs-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="sort" data-sort="name">Name</th>
                    <th>Description</th>
                    <th>Install</th>
                </tr>
            </thead>
            <tbody class="list">
';
    foreach ($inactive_plugins as $row) {
        $inactive_plugins_html .= '<tr>' .
            '<td class="name">' . htmlspecialchars($row['name']) . '</td>' .
            '<td class="descrption">' . $row['descrption'] . '</td>' .
            '<td>' . $row['deactive'] . '</td>' .
            '</tr>';
    }
    $inactive_plugins_html .= '</tbody></table>';
    $inactive_plugins_html .= '<div id="inactive-plugins-pagination"><ul class="pagination"></ul></div></div>';
    $inactive_plugins_html .= '<script>
    window.addEventListener("DOMContentLoaded", function() {
        var pluginsList = document.getElementById("inactive-plugins-list");
        if (pluginsList) {
            var options = {
                valueNames: ["name", "descrption"],
                pagination: true,
                page: 10,
                searchClass: "search",
                listClass: "list",
            };
            var listObj = new List("inactive-plugins-list", options);
            var pagDiv = document.getElementById("inactive-plugins-pagination");
            var pagList = pluginsList.getElementsByClassName("pagination")[0];
            if (pagDiv && pagList) {
                pagDiv.appendChild(pagList);
            }
        }
    });
    </script>';

    $plugins_back ='<a href="admin.php" class="button">'.BACK_TEXT.'</a>';

    $smarty->assign(
            array(
                'active_plugins' => $active_plugins_html,
                'inactive_plugins' => $inactive_plugins_html,
                'plugins_back'=> $plugins_back
            )
    );
}

$smarty->assign(
        array(
            'form_action' => $form_action,
            'content' => $content,
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/plugins.htm');
?>