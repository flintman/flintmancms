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

$links = array();

if ($_GET['action'] == 'edit') {

    $link_id = scrub_input($_GET['link_id']);
    if (!$_POST['submit']) {
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link=%s ORDER BY `link_order`",
                        quote_smart($link_id));
        $result = $db->sql_query($sql)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        While ($data = $db->sql_fetchrow($result)) {
            array_push($links, array(
                'name' => $data['name'],
                'up' => '<a href="admin.php?n=links&action=up&link_id=' . $data['id'] . '&order=' . $data['link_order'] . '&sublink=' . $link_id . '&e=y">' . UP_TEXT . '</a>',
                'down' => '<a href="admin.php?n=links&action=down&link_id=' . $data['id'] . '&order=' . $data['link_order'] . '&sublink=' . $link_id . '&e=y">' . DOWN_TEXT . '</a>',
                'edit' => '<a href="admin.php?n=links&action=edit&link_id=' . $data['id'] . '">' . EDIT_TEXT . '</a>',
                'del' => '<a href="admin.php?n=links&action=delete&link_id=' . $data['id'] . '">' . DELETE_TEXT . '</a>'
            ));
        }
        $report->setMainAttributes('width="450px" cellpadding="0" cellspacing="0" border="0"');
        $report->setFieldHeadingAttributes('class="header"');
        $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
        $report->addOutputColumn('name', 'Name', 'left');
        $report->addOutputColumn('up', '', 'left');
        $report->addOutputColumn('down', '', 'left');
        $report->addOutputColumn('edit', '', 'left');
        $report->addOutputColumn('del', '', 'left');
        $content = $report->getListFromArray($links);

        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_links WHERE id =%s",
                        quote_smart($link_id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result) or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;

        $form_action = "admin.php?n=links&action=edit&link_id=" . $link_id . "";

        if ($data['active'] == '0')
            $active_link = '<input type="checkbox" name="active" value="1">';
        else
            $active_link = '<input type="checkbox" name="active" value="1" checked>';

        if ($data['new_window'] == '0')
            $newwindow = '<input type="checkbox" name="window" value="1">';
        else
            $newwindow = '<input type="checkbox" name="window" value="1" checked>';

        $link_sub = '<select name="sublink">';
        $sql2 = "SELECT * FROM " . DB_PREFIX . "_links";
        $link_sub .= "<option value='0'></option>";
        $result2 = $db->sql_query($sql2);
        While ($data2 = $db->sql_fetchrow($result2)) {
            $name_sub = $data2['name'];
            $id = $data2['id'];
            if ($data['sub_link'] == $id)
                $link_sub .= "<option value=\"$id\" selected>$name_sub</option>";
            else
                $link_sub .= "<option value=\"$id\">$name_sub</option>";
        }
        $link_sub .='</select>';

        $link_name = '<input name="name" type="text" SIZE=45 value="' . $data['name'] . '">';
        $link_link = '<input name="url" type="text" SIZE=45  value="' . $data['link'] . '">';
        $link_back = '<a href="admin.php?n=links" class="button">' . BACK_TEXT . '</a>';
        $button = '<input type="submit" value="' . SAVE_TEXT . '" name="submit" class="button">';

        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'active_link' => $active_link,
                    'newwindow' => $newwindow,
                    'link_name' => $link_name,
                    'link_link' => $link_link,
                    'link_back' => $link_back,
                    'sublink_link' => $link_sub,
                    'button' => $button,
                    'active_text' => ADMIN_LINK_ACTIVATE_TEXT,
                    'window_text' => ADMIN_LINK_OPEN_TEXT,
                    'name_text' => ADMIN_LINK_NAME_TEXT,
                    'url_text' => ADMIN_LINK_URL_TEXT,
                    'sublink_text' => ADMIN_LINK_SUB_TEXT
                )
        );
    } else {
        if ($_POST['submit'] == "Save") {
            $name = scrub_input($_POST['name']);
            $url = $_POST['url'];
            $sublink = scrub_input($_POST['sublink']);
            if (isset($_POST['window']))
                $window = 1;
            else
                $window = 0;
            if (isset($_POST['active']))
                $active = 1;
            else
                $active = 0;

            $sql = sprintf("UPDATE " . DB_PREFIX . "_links SET name=%s,new_window=%s,link=%s,
                    active=%s,sub_link=%s WHERE id=%s", quote_smart($name), quote_smart($window),
                            quote_smart($url), quote_smart($active), quote_smart($sublink), quote_smart($link_id));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=links");
    }
}
elseif ($_GET['action'] == 'add') {
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=links&action=add";

        $active_link = '<input type="checkbox" name="active" value="1">';
        $link_name = '<input type="text" SIZE=45 name="name" >';
        $link_link = '<input name="url" SIZE=45 type="text">';
        $newwindow = '<input type="checkbox" name="window" value="1">';
        $link_sub = '<select name="sublink">';
        $sql = "SELECT * FROM " . DB_PREFIX . "_links";
        $link_sub .= "<option value='0'></option>";
        $result = $db->sql_query($sql);
        While ($data = $db->sql_fetchrow($result)) {
            $name = $data['name'];
            $id = $data['id'];
            $link_sub .= "<option value=\"$id\">$name</option>";
        }
        $link_sub .='</select>';
        $link_back = '<a href="admin.php?n=links" class="button">' . BACK_TEXT . '</a>';
        $button = '<input type="submit" value="' . SAVE_TEXT . '" name="submit" class="button">';

        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'active_link' => $active_link,
                    'newwindow' => $newwindow,
                    'link_name' => $link_name,
                    'link_link' => $link_link,
                    'link_back' => $link_back,
                    'sublink_link' => $link_sub,
                    'button' => $button,
                    'active_text' => ADMIN_LINK_ACTIVATE_TEXT,
                    'window_text' => ADMIN_LINK_OPEN_TEXT,
                    'name_text' => ADMIN_LINK_NAME_TEXT,
                    'url_text' => ADMIN_LINK_URL_TEXT,
                    'sublink_text' => ADMIN_LINK_SUB_TEXT
                )
        );
    } else {
        If ($_POST['submit'] == "Save") {
            $name = scrub_input($_POST['name']);
            $url = $_POST['url'];
            $sublink = scrub_input($_POST['sublink']);

            if (isset($_POST['active']))
                $active = 1;
            else
                $active = 0;
            if (isset($_POST['window']))
                $window = 1;
            else
                $window = 0;

            $count = count_links($sublink);
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_links VALUES('0',%s,%s,%s,%s,%s,%s)",
                            quote_smart($name), quote_smart($url), quote_smart($active), quote_smart($window),
                            quote_smart($count), quote_smart($sublink));

            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=links");
    }
}

elseif ($_GET['action'] == 'delete') {
    $link_id = scrub_input($_GET['link_id']);
    if (!$_POST['submit']) {
        $form_action = "admin.php?n=links&action=delete&link_id=" . $link_id . "";

        $content = QUESTION_DELETE_TEXT;
        $link_back = '<a href="admin.php?n=links" class="button">' . BACK_TEXT . '</a>';

        $button = '<input type="submit" value="Delete" name="submit" class="button">';
        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'link_back' => $link_back,
                    'button' => $button,
                )
        );
    } else {
        if ($_POST['submit'] == "Delete") {
            $sql = sprintf("DELETE FROM " . DB_PREFIX . "_links WHERE id=%s",
                            quote_smart($link_id));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=links");
    }
} elseif ($_GET['action'] == 'up') {
    $link_id = scrub_input($_GET['link_id']);
    $order = scrub_input($_GET['order']);
    $order_minus = scrub_input($_GET['order']) - 1;
    $sublink = scrub_input($_GET['sublink']);

    if ($order == 1) {
        if (isset($_GET['e']))
            header("Location: admin.php?n=links&action=edit&link_id=" . $sublink);
        else
            header("Location: admin.php?n=links");
    } else {
        $sql = sprintf("UPDATE " . DB_PREFIX . "_links SET link_order=%s WHERE link_order=%s AND sub_link=%s",
                        quote_smart($order), quote_smart($order_minus), quote_smart($sublink));
        $db->sql_query($sql)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        $sql = sprintf("UPDATE " . DB_PREFIX . "_links SET link_order=%s WHERE id=%s",
                        quote_smart($order_minus), quote_smart($link_id));
        $db->sql_query($sql)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        if (!isset($errorMsg)) {
            if (isset($_GET['e']))
                header("Location: admin.php?n=links&action=edit&link_id=" . $sublink);
            else
                header("Location: admin.php?n=links");
        }
    }
} elseif ($_GET['action'] == 'down') {
    $link_id = scrub_input($_GET['link_id']);
    $order = scrub_input($_GET['order']);
    $order_plus = scrub_input($_GET['order']) + 1;
    $sublink = scrub_input($_GET['sublink']);
    $count = count_links($sublink);
    if ($order >= ($count)) {
        if (isset($_GET['e']))
            header("Location: admin.php?n=links&action=edit&link_id=" . $sublink);
        else
            header("Location: admin.php?n=links");
    } else {
        $sql = sprintf("UPDATE " . DB_PREFIX . "_links SET link_order=%s WHERE link_order=%s AND sub_link=%s",
                        quote_smart($order), quote_smart($order_plus), quote_smart($sublink));
        $db->sql_query($sql)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        $sql = sprintf("UPDATE " . DB_PREFIX . "_links SET link_order=%s WHERE id=%s",
                        quote_smart($order_plus), quote_smart($link_id));
        $db->sql_query($sql)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        if (!isset($errorMsg)) {
            if (isset($_GET['e']))
                header("Location: admin.php?n=links&action=edit&link_id=" . $sublink);
            else
                header("Location: admin.php?n=links");
        }
    }
} else {
    $sql = "SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link='0' ORDER BY `link_order`";
    $result = $db->sql_query($sql)
            or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
    While ($data = $db->sql_fetchrow($result)) {
        array_push($links, array(
            'name' => $data['name'],
            'up' => '<a href="admin.php?n=links&action=up&link_id=' . $data['id'] . '&order=' . $data['link_order'] . '&sublink=0">' . UP_TEXT . '</a>',
            'down' => '<a href="admin.php?n=links&action=down&link_id=' . $data['id'] . '&order=' . $data['link_order'] . '&sublink=0">' . DOWN_TEXT . '</a>',
            'edit' => '<a href="admin.php?n=links&action=edit&link_id=' . $data['id'] . '">' . EDIT_TEXT . '</a>',
            'del' => '<a href="admin.php?n=links&action=delete&link_id=' . $data['id'] . '">' . DELETE_TEXT . '</a>'
        ));
    }

    $report->setMainAttributes('width="450px" cellpadding="0" cellspacing="0" border="0"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('name', 'Name', 'left');
    $report->addOutputColumn('up', '', 'left');
    $report->addOutputColumn('down', '', 'left');
    $report->addOutputColumn('edit', '', 'left');
    $report->addOutputColumn('del', '', 'left');
    $content = $report->getListFromArray($links);
    $link_back = '<a href="admin.php" class="button">' . BACK_TEXT . '</a>';
    $button = '<a href="admin.php?n=links&action=add" class="button">' . ADMIN_LINK_ADD_TEXT . '</a>';
    $smarty->assign(
            array(
                'form_action' => $form_action,
                'link_back' => $link_back,
                'button' => $button,
            )
    );
}


$smarty->assign(
        array(
            'content' => $content,
            'head' => ADMIN_LINK_HEADER
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/links.htm');
?>