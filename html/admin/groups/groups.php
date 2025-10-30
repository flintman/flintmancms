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


$groups = array();
$content = '';
$group_back = '';
$form_action = '';

if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit'])) {
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_groups WHERE id =%s",
                        quote_smart($id));
        $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result)
        or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        $form_action = "admin.php?n=groups&action=edit&id=" . $id . "";
        if ($id == "2")
            $name = $data['name'] . '<input name="name" type="hidden" value="' . $data['name'] . '" maxlength="255" >';
        else {
            $name = '<input name="name" type="text" value="' . $data['name'] . '" maxlength="255" >';
        }

        //Gets all pages to set privliges on
        $sql = "SELECT * FROM " . DB_PREFIX . "_pages";
        $result = $db->sql_query($sql);
        $pages = "";
        $plugins = "";
        while ($data = $db->sql_fetchrow($result)) {
            if ($data['id'] != '1') {
                $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE
                    type='page' AND type_id=%s AND group_id=%s",
                                quote_smart($data['id']), quote_smart($id));
                $links = $db->sql_query($sql);
                $data_link = $db->sql_numrows($links);
                if ($data_link > 0) {
                    $pages .= '<input type="checkbox" name="p' . $data['id'] . '" value="1" checked>' . $data['title'] . '<br>';
                } else {
                    $pages .= '<input type="checkbox" name="p' . $data['id'] . '" value="1">' . $data['title'] . '<br>';
                }
            }
        }

        //Gets all pages to set privliges on
        $sql = "SELECT * FROM " . DB_PREFIX . "_plugins";
        $result = $db->sql_query($sql);
        while ($data = $db->sql_fetchrow($result)) {
            $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE
                    type='plugins' AND type_id=%s AND group_id=%s",
                            quote_smart($data['id']), quote_smart($id));
            $links = $db->sql_query($sql2);
            $data_link = $db->sql_numrows($links);

            if ($data_link > 0) {
                $plugins .= '<input type="checkbox" name="pg' . $data['id'] . '" value="1" Checked>' . $data['name'] . '<br>';
            } else {
                $plugins .= '<input type="checkbox" name="pg' . $data['id'] . '" value="1">' . $data['name'] . '<br>';
            }
        }

        $group_back = '<a href="admin.php?n=groups" class="button">' . BACK_TEXT . '</a>';
        $button = '<input type="submit" value="' . SAVE_TEXT . '" name="submit" class="button">';
    } else {
        if ($_POST['submit'] == "Save") {
            $name = scrub_input($_POST['name']);
            $id = scrub_input($_GET['id']);
            $sql = sprintf("UPDATE " . DB_PREFIX . "_groups SET
                    name=%s WHERE id=%s", quote_smart($name), quote_smart($id));
        $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
            $sql = "SELECT * FROM " . DB_PREFIX . "_pages";
            $result = $db->sql_query($sql);
            while ($data = $db->sql_fetchrow($result)) {
                $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE
                    type='page' AND type_id=%s AND group_id=%s",
                                quote_smart($data['id']), quote_smart($id));
                $links = $db->sql_query($sql2);
                $data_link = $db->sql_numrows($links);
                 $link_id = getlinkID('page', $data['id']);
                if (isset($_POST['p' . $data['id']])) {
                    if (!$data_link)
                        $sql3 = sprintf("INSERT INTO " . DB_PREFIX . "_group_links
                                VALUES('0',%s, 'page',%s,%s)",
                                        quote_smart($id), quote_smart($data['id']), quote_smart($link_id));
                }else {
                    $sql3 = sprintf("DELETE FROM " . DB_PREFIX . "_group_links WHERE type='page'
                        AND type_id=%s AND group_id=%s", quote_smart($data['id']), quote_smart($id));
                }
                $db->sql_query($sql3) or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
            }

            //Gets all pages to set privliges on
            $sql = "SELECT * FROM " . DB_PREFIX . "_plugins";
            $result = $db->sql_query($sql);
            while ($data = $db->sql_fetchrow($result)) {
                $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE
                    type='plugins' AND type_id=%s AND group_id=%s",
                                quote_smart($data['id']), quote_smart($id));
                $links = $db->sql_query($sql2);
                $data_link = $db->sql_numrows($links);
                $link_id = getlinkID('plugins', $data['id']);
                if (isset($_POST['pg' . $data['id']])) {
                    if (!$data_link)
                        $sql3 = sprintf("INSERT INTO " . DB_PREFIX . "_group_links
                                VALUES('0',%s, 'plugins',%s,%s)", quote_smart($id), quote_smart($data['id']),
                                quote_smart($link_id));
                } else {
                    $sql3 = sprintf("DELETE FROM " . DB_PREFIX . "_group_links WHERE type='plugins'
                        AND type_id=%s AND group_id=%s", quote_smart($data['id']), quote_smart($id));
                }
        $db->sql_query($sql3)
            or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
            }
        }


        if (!isset($errorMsg)) {
            header("Location: admin.php?n=groups");
            exit;
        }
    }
    if (!isset($pages)) $pages = '';
    if (!isset($plugins)) $plugins = '';
    if (!isset($button)) $button = '';
    $smarty->assign(
        array(
            'name' => $name,
            'pages' => $pages,
            'plugins' => $plugins,
            'button' => $button
        )
    );
}
elseif (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (!isset($_POST['submit'])) {
        $form_action = "admin.php?n=groups&action=add";
        $name = '<input name="name" type="text" size="45" maxlength="255">';
        $group_back = '<a href="admin.php?n=groups" class="button">' . BACK_TEXT . '</a>';
        $button = '<input type="submit" value="' . SAVE_TEXT . '" name="submit" class="button">';

        //Gets all pages to set privliges on
        $sql = "SELECT * FROM " . DB_PREFIX . "_pages";
        $result = $db->sql_query($sql);
        $pages = "";
        $plugins = "";
        while ($data = $db->sql_fetchrow($result)) {
            if ($data['id'] != '1')
                $pages .= '<input type="checkbox" name="p' . $data['id'] . '" value="1">' . $data['title'] . '<br>';
        }

        //Gets all pages to set privliges on
        $sql = "SELECT * FROM " . DB_PREFIX . "_plugins";
        $result = $db->sql_query($sql);
        while ($data = $db->sql_fetchrow($result)) {
            $plugins .= '<input type="checkbox" name="pg' . $data['id'] . '" value="1">' . $data['name'] . '<br>';
        }
    } else {
        if ($_POST['submit'] == "Save") {
            $name = scrub_input($_POST['name']);
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_groups (id, name) VALUES('0',%s)", quote_smart($name));

        $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;

            $last_id = $db->sql_nextid();
            $sql = "SELECT * FROM " . DB_PREFIX . "_pages";
            $result = $db->sql_query($sql);
            while ($data = $db->sql_fetchrow($result)) {

                if (isset($_POST['p' . $data['id']])) {
                    $sql2 = sprintf("INSERT INTO " . DB_PREFIX . "_group_links (id, group_id, type, type_id, link_id)
                            VALUES('0',%s, 'page',%s, 0)",
                                    quote_smart($last_id), quote_smart($data['id']));
            $db->sql_query($sql2)
                or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
                }
            }

            //Gets all pages to set privliges on
            $sql = "SELECT * FROM " . DB_PREFIX . "_plugins";
            $result = $db->sql_query($sql);
            while ($data = $db->sql_fetchrow($result)) {
                if (isset($_POST['pg' . $data['id']])) {
                    $sql2 = sprintf("INSERT INTO " . DB_PREFIX . "_group_links (id, group_id, type, type_id, link_id)
                            VALUES('0',%s, 'plugins',%s, 0)",
                                    quote_smart($last_id), quote_smart($data['id']));
            $db->sql_query($sql2)
                or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
                }
            }
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=groups");
    }
    $smarty->assign(
            array(
                'name' => $name,
                'pages' => $pages,
                'plugins' => $plugins,
                'button' => $button
            )
    );
}

elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit'])) {
        $form_action = "admin.php?n=groups&action=delete&id=" . $id . "";
        $content = QUESTION_DELETE_TEXT;
        $group_back = '<a href="admin.php?n=groups" class="button">' . BACK_TEXT . '</a>';
        $button = '<input type="submit" value="' . DELETE_TEXT . '" name="submit" class="button">';
        $smarty->assign(
                array(
                    'content' => $content,
                    'button' => $button
                )
        );
    } else {
        If ($_POST['submit'] == "Delete") {
            $sql = sprintf("DELETE FROM " . DB_PREFIX . "_groups WHERE id=%s",
                            quote_smart($id));
        $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
            $sql = sprintf("DELETE FROM " . DB_PREFIX . "_group_links WHERE group_id=%s",
                            quote_smart($id));
        $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=groups");
    }
} else {
    $sql = "SELECT * FROM " . DB_PREFIX . "_groups";
    $result = $db->sql_query($sql);
    While ($data = $db->sql_fetchrow($result)) {
        if ($data['id'] == 1) {
            array_push($groups, array(
                'name' => $data['name']
            ));
        } elseif ($data['id'] == 2) {
            array_push($groups, array(
                'name' => $data['name'],
                'edit' => '<a href="admin.php?n=groups&action=edit&id=' . $data['id'] . '">' . EDIT_TEXT . '</a>',
            ));
        } else {
            array_push($groups, array(
                'name' => $data['name'],
                'edit' => '<a href="admin.php?n=groups&action=edit&id=' . $data['id'] . '" >' . EDIT_TEXT . '</a>',
                'del' => '<a href="admin.php?n=groups&action=delete&id=' . $data['id'] . '" >' . DELETE_TEXT . '</a>'
            ));
        }
    }

    $report->setMainAttributes('width="450px" cellpadding="0" cellspacing="0" border="0"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('name', 'Name', 'left');
    $report->addOutputColumn('edit', '', 'left');
    $report->addOutputColumn('del', '', 'left');
    $content .= $report->getListFromArray($groups);
    $group_back .= '<a href="admin.php" class="button">' . BACK_TEXT . '</a>';
    $button = '<a href="admin.php?n=groups&action=add" class="button">' . ADMIN_GROUP_ADD_TEXT . '</a>';
    $smarty->assign(
            array(
                'content' => $content,
                'button' => $button
            )
    );
}

$smarty->assign(
        array(
            'form_action' => $form_action,
            'group_back' => $group_back
        )
);


//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/groups.htm');
?>