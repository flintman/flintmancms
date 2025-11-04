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
array_push($page_lvl, 'Admin');
include(INCLUDES_PATH . 'authentication.php');

$photo = array();
$form_action = '';
$content = '';

if (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=add";
        $content = 'Enter Album Name : <input name="job_name" type="text" id="title" maxlength="255"><br>';
        $content .='Date Taken(xx/xx/xxxx) : <input name="date_taken" type="text" id="title" maxlength="255"><br>';
        $content .= '<input type="submit" value="Save" name="submit" class="button">';
    } elseif (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $date = $_POST['date_taken'];
        $job_name = $_POST['job_name'];
        $sql = sprintf("INSERT INTO " . DB_PREFIX . "_portfolio_portfolio VALUES('0',%s,%s)",
                quote_smart($job_name), quote_smart($date));
        $result = $db->sql_query($sql);
        if (!$result) {
            die("Could not execute query: " . $db->sql_error());
        }
        header("Location: admin.php?n=plugins&p=portfolio");
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'edit') {
        $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=edit&id=" . $id . "";
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio_portfolio WHERE id=%s",
                quote_smart($id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result);

        $content = 'Enter Album Name : <input name="job_name" type="text" value ="' . $data['name'] . '" id="title" maxlength="255" ><br>';
        $content .='Date Taken(xx/xx/xxxx) : <input name="date_taken" value ="' . $data['date_taken'] . '" type="text" id="title" maxlength="255"><br>';
        $content .= '<input type="submit" value="Save" name="submit" class="button">';
    } elseif (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $date = scrub_input($_POST['date_taken']);
        $job_name = scrub_input($_POST['job_name']);
        $sql = sprintf("UPDATE " . DB_PREFIX . "_portfolio_portfolio SET name=%s,date_taken=%s WHERE id=%s",
                quote_smart($job_name), quote_smart($date), quote_smart($id));
        $db->sql_query($sql);
        header("Location: admin.php?n=plugins&p=portfolio");
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'photo') {
    $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=photo&id=" . $id . "";

        $content .= '<label for="picfile">Select Image (JPG, PNG, or GIF, max 5MB):</label><br>';
        $content .= '<input type="file" name="picfile" id="picfile" accept="image/jpeg,image/png,image/gif" required><br><br>';
        $content .= '<input type="submit" value="Save" name="submit" class="button">';
    } elseif (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }

        // Validate uploaded file
        $validation = validate_upload($_FILES['picfile'], ['image/jpeg', 'image/png', 'image/gif'], 5242880);

        if (!$validation['valid']) {
            $errorMsg = "Upload failed: " . $validation['error'];
        } else {
            $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio_portfolio WHERE id=%s",
                    quote_smart($id));
            $result = $db->sql_query($sql);
            $data = $db->sql_fetchrow($result);

            // Use secure random filename from validation
            $filename = $validation['filename'];
            $uploadfile = "plugins/portfolio/images/" . $filename;

            if (is_writable("plugins/portfolio/images/")) {
                if (move_uploaded_file($_FILES['picfile']['tmp_name'], $uploadfile)) {
                    // Set secure file permissions (read-only for web server)
                    chmod($uploadfile, 0644);

                    $sql = sprintf("INSERT INTO " . DB_PREFIX . "_portfolio_photos VALUES('0',%s,%s)",
                            quote_smart($id), quote_smart($filename));
                    $db->sql_query($sql);
                    header("Location: admin.php?n=plugins&p=portfolio");
                } else {
                    $errorMsg = "File didn't upload - check directory permissions";
                }
            } else {
                $errorMsg = "The upload directory is not writable";
            }
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
        $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=delete&id=$id";
        $content = 'Are you Sure you want to Delete this?<br>';
        $content .= '<a href="admin" class="button">Back</a>&nbsp;&nbsp;&nbsp;';
        $content .= '<input type="submit" value="Delete" name="submit" class="button">';
    } elseif (isset($_POST['submit']) && $_POST['submit'] == 'Delete') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        // they confirmed
        $sql = sprintf("DELETE FROM " . DB_PREFIX . "_portfolio_portfolio WHERE id=%s",
                quote_smart($id));
        $db->sql_query($sql);
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio_photos WHERE portfolio_id=%s",
                quote_smart($id));
        $result = $db->sql_query($sql);
        while ($data = $db->sql_fetchrow($result)) {
            $sql2 = sprintf("DELETE FROM " . DB_PREFIX . "_portfolio_photos WHERE id=%s",
                    quote_smart($data['id']));
            $db->sql_query($sql2);
            $filename = $data['photo_name'];
            $uploadfile = "plugins/portfolio/images/" . basename($filename);
            unlink($uploadfile);
        }



        header("Location: admin.php?n=plugins&p=portfolio");
    }
} else {
    $sql = "SELECT * FROM " . DB_PREFIX . "_portfolio_portfolio";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {
        array_push($photo, array(
            'id' => $data['id'],
            'name' => $data['name'],
            'edit' => '<a href="admin.php?n=plugins&p=portfolio&action=edit&id=' . $data['id'] . '"> Edit </a>',
            'photo' => '<a href="admin.php?n=plugins&p=portfolio&action=photo&id=' . $data['id'] . '">Add photos</a>',
            'del' => '<a href="admin.php?n=plugins&p=portfolio&action=delete&id=' . $data['id'] . '"> Delete </a>'
        ));
    }
    $report->setMainAttributes('width="100%" cellpadding="0" cellspacing="0" border="0"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('id', 'Id', 'left');
    $report->addOutputColumn('name', 'Name', 'left');
    $report->addOutputColumn('edit', '', 'left');
    $report->addOutputColumn('photo', '', 'center');
    $report->addOutputColumn('del', '', 'left');
    $content = $report->getListFromArray($photo);
    $content .='<br><br><br><a href="admin.php" class="button">Back</a>&nbsp;&nbsp;&nbsp;';
    $content .='<a href="admin.php?n=plugins&p=portfolio&action=add" class="button">Add a Album to Portfolio</a><br>';
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
$smarty->display(PLUGINS_PATH . '/portfolio/template/page.htm');
?>