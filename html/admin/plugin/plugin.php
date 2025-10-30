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

$active_plugins = array();
$inactive_plugins = array();
$content = '';

if (isset($_GET['action']) && $_GET['action'] == 'add') {

    if (is_writable(PLUGINS_PATH)) {
        $content = ADMIN_PLUGINS_WRITE_TEXT.'<br>';
    } else {
        $content = ADMIN_PLUGINS_NOT_WRITE_TEXT.'<br>';
    }

    if (extension_loaded('zip')) {
        $pZipSupport = True;

        if (!$_POST['submit']) {

        } elseif ($_POST['submit'] == 'Save') {
            // unzip file
            $reply = unzip($_FILES['zipfile']['tmp_name'], PLUGINS_PATH, true, false);

            // execute SQL file
            $foldername = substr($_FILES['zipfile']['name'], 0, -4);

            If ($reply) {
                activate_plugins($foldername);

                $content .= ADMIN_PLUGINS_ALL_SET_TEXT;
            } else {
                $content .=ADMIN_PLUGINS_ISSUES_TEXT;
            }
        } else {
            $content .= ADMIN_PLUGINS_ZIP_TEXT;
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'active') {
    $id = scrub_input($_GET['id']);
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=plugin&action=active&id=" . $id . "";
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_plugins WHERE id=%s",
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
			<select  name='delete_tables'>
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
    } elseif (isset($_POST['submit'])) {
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
    $sql = "SELECT * FROM " . DB_PREFIX . "_plugins WHERE active ='1'";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {

        require_once(PLUGINS_PATH . $data['name'] . '/variable.php');

        $description = "<center>" . $plugin_description . " <br>" .VERSION_TEXT. $plugin_version . "</center>";
        array_push($active_plugins, array(
            'name' => ucfirst($data['name']),
            'descrption' => $description,
            'deactive' => '<a href="admin.php?n=plugin&action=active&id=' . $data['id'] . '">'.UNINSTALL_TEXT.'</a>',
            'config' => '<a href="admin.php?n=plugins&p=' . $data['name'] . '">Configure</a>'

        ));
    }
    $report->clearOutputColumns();
    $report->setMainAttributes('width="100%" cellpadding="0" cellspacing="0" border="1"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('name', '', 'left');
    $report->addOutputColumn('config', '', 'left');
    $report->addOutputColumn('descrption', '', 'left');
    $report->addOutputColumn('deactive', '', 'left');
    $active_plugins = $report->getListFromArray($active_plugins);

    $sql = "SELECT * FROM " . DB_PREFIX . "_plugins WHERE active ='0'";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {

        require_once(PLUGINS_PATH . $data['name'] . '/variable.php');

        $description = "<center>" . $plugin_description . " <br>".VERSION_TEXT . $plugin_version . "</center>";
        array_push($inactive_plugins, array(
            'name' => ucfirst($data['name']),
            'descrption' => $description,
            'deactive' => '<a href="admin.php?n=plugin&action=active&id=' . $data['id'] . '">'.INSTALL_TEXT.'</a>'
        ));
    }
    $report->clearOutputColumns();
    $report->setMainAttributes('width="100%" cellpadding="0" cellspacing="0" border="1"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('name', '', 'left');
    $report->addOutputColumn('descrption', '', 'left');
    $report->addOutputColumn('deactive', '', 'left');
    $inactive_plugins = $report->getListFromArray($inactive_plugins);

    $form_action = "admin.php?n=plugin&action=add";
    $add = ADMIN_PLUGINS_ZIP_TEXT.'<br>
                        <input type="file" name="zipfile" />';
    $save_button ='<input type="submit" name="submit" value="'.SAVE_TEXT.'" class="button" />';
    $plugins_back ='<a href="admin.php" class="button">'.BACK_TEXT.'</a>';

    $smarty->assign(
            array(
                'active_text' => ADMIN_PLUGINS_ACTIVE_TEXT,
                'active_plugins' => $active_plugins,
                'inactive_text' => ADMIN_PLUGINS_INACTIVE_TEXT,
                'inactive_plugins' => $inactive_plugins,
                'save_button' => $save_button,
                'plugins_back'=> $plugins_back,
                'add' => $add
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