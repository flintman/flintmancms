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

if ($_GET['action'] == 'add') {
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=add";
        $content = 'Enter Album Name : <input name="job_name" type="text" id="title" maxlength="255"><br>';
        $content .='Date Taken(xx/xx/xxxx) : <input name="date_taken" type="text" id="title" maxlength="255"><br>';
        $content .= '<input type="submit" value="Save" name="submit" class="button">';
    } elseif ($_POST['submit'] == 'Save') {
        $date = $_POST['date_taken'];
        $job_name = $_POST['job_name'];
        $sql = sprintf("INSERT INTO " . DB_PREFIX . "_portfolio VALUES('0',%s,%s)",
                quote_smart($job_name), quote_smart($date));
        $result = mysql_query($sql)
                or die("Could not exexute query");
        header("Location: admin.php?n=plugins&p=portfolio");
    }
} elseif ($_GET['action'] == 'edit') {
        $id = scrub_input($_GET['id']);
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=edit&id=" . $id . "";
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio WHERE id=%s",
                quote_smart($id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result);

        $content = 'Enter Album Name : <input name="job_name" type="text" value ="' . $data['name'] . '" id="title" maxlength="255" ><br>';
        $content .='Date Taken(xx/xx/xxxx) : <input name="date_taken" value ="' . $data['date_taken'] . '" type="text" id="title" maxlength="255"><br>';
        $content .= '<input type="submit" value="Save" name="submit" class="button">';
    } elseif ($_POST['submit'] == 'Save') {
        $date = scrub_input($_POST['date_taken']);
        $job_name = scrub_input($_POST['job_name']);
        $sql = sprintf("UPDATE " . DB_PREFIX . "_portfolio SET name=%s,date_taken=%s WHERE id=%s",
                quote_smart($job_name), quote_smart($date), quote_smart($id));
        $db->sql_query($sql);
        header("Location: admin.php?n=plugins&p=portfolio");
    }
} elseif ($_GET['action'] == 'photo') {
    $id = scrub_input($_GET['id']);
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=photo&id=" . $id . "";

        $content .="<INPUT type =\"file\" NAME=\"picfile\">";
        $content .= '<input type="submit" value="Save" name="submit" class="button">';
    } elseif ($_POST['submit'] == 'Save') {
        $x = 1;
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio WHERE id=%s",
                quote_smart($id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result);
        $job_name = $data['name'];
        $filename = "$job_name$x.jpg";
        While (is_file("plugins/portfolio/images/" . $filename)) {
            $x++;
            $filename = "$job_name$x.jpg";
        }
        $uploadfile = "plugins/portfolio/images/" . basename($filename);
        if (is_writable("plugins/portfolio/images/")) {
            if (move_uploaded_file($_FILES['picfile']['tmp_name'], $uploadfile)) {
                $sql = sprintf("INSERT INTO " . DB_PREFIX . "_photos VALUES('0',%s,%s)",
                        quote_smart($id), quote_smart($filename));
                $db->sql_query($sql);
                header("Location: admin.php?n=plugins&p=portfolio");
            } else {
                $errorMsg = "File didn't Upload ";
            }
        } else {
            $errorMsg = "The path is Not Writable<br><br>";
        }
    }
} elseif ($_GET['action'] == 'delete') {
        $id = scrub_input($_GET['id']);
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=portfolio&action=delete&id=$id";
        $content = 'Are you Sure you want to Delete this?<br>';
        $content .= '<a href="admin" class="button">Back</a>&nbsp;&nbsp;&nbsp;';
        $content .= '<input type="submit" value="Delete" name="submit" class="button">';
    } elseif ($_POST['submit'] == 'Delete') {
        // they confirmed
        $sql = sprintf("DELETE FROM " . DB_PREFIX . "_portfolio WHERE id=%s",
                quote_smart($id));
        $db->sql_query($sql);
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_photos WHERE portfolio_id=%s",
                quote_smart($id));
        $result = $db->sql_query($sql);
        while ($data = $db->sql_fetchrow($result)) {
            $sql2 = sprintf("DELETE FROM " . DB_PREFIX . "_photos WHERE id=%s",
                    quote_smart($data['id']));
            $db->sql_query($sql2);
            $filename = $data['photo_name'];
            $uploadfile = "plugins/portfolio/images/" . basename($filename);
            unlink($uploadfile);
        }



        header("Location: admin.php?n=plugins&p=portfolio");
    }
} else {
    $sql = "SELECT * FROM " . DB_PREFIX . "_portfolio";
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
    $report->addOutputColumn('id', 'Id', '', 'left');
    $report->addOutputColumn('name', 'Name', '', 'left');
    $report->addOutputColumn('edit', '', '', 'left');
    $report->addOutputColumn('photo', '', '', 'center');
    $report->addOutputColumn('del', '', '', 'left');
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