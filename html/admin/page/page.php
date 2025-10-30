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

$pages = array();
$content = '';

if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $page_id = scrub_input($_GET['page_id']);
    if (!isset($_POST['submit'])) {
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_pages WHERE id =%s",
                        quote_smart($page_id));
        $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result) or
        $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line " . __LINE__ . " Of " . __FILE__;
        $form_action = "admin.php?n=page&action=edit&page_id=" . $page_id . "";

        if ($data['active'] == '0')
            $active_page = '<input type="checkbox" name="active" value="1">';
        else
            $active_page = '<input type="checkbox" name="active" value="1" checked>';

        if ($data['show_title'] == '0')
            $display_title = '<input type="checkbox" name="display" value="1">';
        else
            $display_title = '<input type="checkbox" name="display" value="1" checked>';


        $page_title = '<input name="title" type="text"  value="' . $data['title'] . '" maxlength="255" >';
        $page_content = '<textarea name="page_text" cols="80" rows="20" style="width:100%">' . $data['context'] . '</textarea>';
        $toggle = "<a href=\"javascript:toggleEditor('page_text');\">".ADMIN_PAGE_REMOVE_TEXT."</a>";
        $page_back = '<a href="admin.php?n=page" class="button">'.BACK_TEXT.'</a>';
        $button = '<input type="submit" value="'.SAVE_TEXT.'" name="submit" class="button">';

        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'active_page' => $active_page,
                    'display_title' => $display_title,
                    'page_title' => $page_title,
                    'page_content' => $page_content,
                    'button' => $button,
                    'page_back' => $page_back,
                    'toggle' => $toggle
                )
        );
    }else {
        If ($_POST['submit'] == "Save") {
            $title = scrub_input($_POST['title']);
            $page_text = $_POST['page_text'];

            if (isset($_POST['display']))
                $display = 1;
            else
                $display = 0;
            if (isset($_POST['active']))
                $active = 1;
            else
                $active = 0;
            $sql = sprintf("UPDATE " . DB_PREFIX . "_pages SET
                    context=%s,title=%s,show_title=%s,active=%s WHERE id=%s",
                            quote_smart($page_text), quote_smart($title), quote_smart($display),
                            quote_smart($active), quote_smart($page_id));
            $db->sql_query($sql) or
                    $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=page");
    }
}
elseif (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (!isset($_POST['submit'])) {
        $form_action = "admin.php?n=page&action=add";

        $active_page = '<input type="checkbox" name="active" value="1">';
        $display_title = '<input type="checkbox" name="display" value="1">';
        $page_title = '<input name="title" type="text" SIZE=45 maxlength="255">';
        $page_content = '<textarea name="content" cols="60" rows="20" wrap="PHYSICAL" ></textarea>';
        $toggle = "<a href=\"javascript:toggleEditor('page_text');\">".ADMIN_PAGE_REMOVE_TEXT."</a>";
        $page_back = '<a href="admin.php?n=page" class="button">'.BACK_TEXT.'</a>';
        $button = '<input type="submit" value="'.SAVE_TEXT.'" name="submit" class="button">';

        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'active_page' => $active_page,
                    'display_title' => $display_title,
                    'page_title' => $page_title,
                    'page_content' => $page_content,
                    'button' => $button,
                    'page_back' => $page_back,
                    'toggle' => $toggle
                )
        );
    } else {
        if ($_POST['submit'] == "Save") {
            $title = scrub_input($_POST['title']);
            $content = scrub_input($_POST['content']);

            if (isset($_POST['display']))
                $display = 1;
            else
                $display = 0;
            if (isset($_POST['active']))
                $active = 1;
            else
                $active = 0;
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_pages VALUES('0',%s,%s,%s,%s)",
                            quote_smart($title), quote_smart($content), quote_smart($active), quote_smart($display));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line " . __LINE__ . " Of " . __FILE__;

            //Adding in Menu Items
        $url = $db->sql_nextid();
    $url = $db->sql_nextid();
            $url = 'index.php?n=page&page_id=' . $url;
            $count = count_links('0');
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_links VALUES('0',%s,%s,%s,'0',%s,'0')",
                            quote_smart($title), quote_smart($url), quote_smart($active), quote_smart($count));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=page");
    }
}

elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $page_id = scrub_input($_GET['page_id']);
    if (!isset($_POST['submit'])) {
        $form_action = "admin.php?n=page&action=delete&page_id=" . $page_id . "";

        $content = 'Are you sure you want to Delete?<br><br>';
        $page_back = '<a href="admin.php?n=page" class="button">'.BACK_TEXT.'</a>';

        $button = '<input type="submit" value="'.DELETE_TEXT.'" name="submit" class="button">';

        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'button' => $button,
                    'page_back' => $page_back,
                )
        );
    } else {
        if ($_POST['submit'] == "Delete") {
            $sql = sprintf("DELETE FROM " . DB_PREFIX . "_pages WHERE id=%s",
                            quote_smart($page_id));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line " . __LINE__ . " Of " . __FILE__;
            $url = 'index.php?n=page&page_id=' . $page_id;
            $sql = sprintf("DELETE FROM " . DB_PREFIX . "_links WHERE link=%s",
                            quote_smart($url));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=page");
    }
} else {
    $sql = "SELECT * FROM " . DB_PREFIX . "_pages";
    $result = $db->sql_query($sql);
    While ($data = $db->sql_fetchrow($result)) {
        if ($data['id'] == 1) {
            array_push($pages, array(
                'name' => $data['title'],
                'edit' => '<a href="admin.php?n=page&action=edit&page_id=' . $data['id'] . '">'.EDIT_TEXT.'</a>',
            ));
        } else {
            array_push($pages, array(
                'name' => $data['title'],
                'edit' => '<a href="admin.php?n=page&action=edit&page_id=' . $data['id'] . '">'.EDIT_TEXT.'</a>',
                'del' => '<a href="admin.php?n=page&action=delete&page_id=' . $data['id'] . '">'.DELETE_TEXT.'</a>'
            ));
        }
    }

    $report->setMainAttributes('width="100%" cellpadding="0" cellspacing="0" border="0"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('name', 'Name', 'left');
    $report->addOutputColumn('edit', '', 'left');
    $report->addOutputColumn('del', '', 'left');
    $content = $report->getListFromArray($pages);
    $page_back ='<a href="admin.php" class="button">'.BACK_TEXT.'</a>';
    $button ='<a href="admin.php?n=page&action=add" class="button">'.ADMIN_PAGE_ADD_TEXT.'</a>';

    $smarty->assign(
            array(
                'button' => $button,
                'page_back' => $page_back,
            )
    );
}


$smarty->assign(
        array(
            'content' => $content,
            'head' => ADMIN_PAGE_HEADER
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/page.htm');
?>