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
$user_array = array();
$content = '';
$form_action = "";

if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit'])) {
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_profile
            WHERE id =%s", quote_smart($id));
        $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result)
        or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        $form_action = "admin.php?n=user&action=edit&id=$id";
        $username = '<input name="username" type="text" value="' . $data['username'] . '" size="15" maxlength="45"">';
        $password = '<input name="password" type="password"  size="15" >';
        $password2 = '<input name="password2" type="password"  size="15">';
        $email = '<input name="email" type="text" size="15" value="' . $data['email'] . '" maxlength="45">';

        //Setups groups
        $groups = '<select name="groups">';
        $sql2 = "SELECT * FROM " . DB_PREFIX . "_groups";
        $result2 = $db->sql_query($sql2);
        While ($data2 = $db->sql_fetchrow($result2)) {
            $priv_id = $data2['id'];
            $priv_name = $data2['name'];
            if ($data['permissions'] == $priv_id)
                $groups .= "<option value=\"$priv_id\" selected>$priv_name</option>";
            else
                $groups .= "<option value=\"$priv_id\">$priv_name</option>";
        }
        $groups .= '</select>';

        //removes if user is number 1 admin
        if ($data['id'] == '1')
            $groups = '';

        $send = '<input type="submit" id="button" class="button" name="submit" value="Send">';
    } else {
        If (isset($_POST['submit'])) {
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                die("CSRF token validation failed");
            }
            $password = scrub_input($_POST['password']);
            $password2 = scrub_input($_POST['password2']);
            $username = scrub_input($_POST['username']);
            $email = scrub_input($_POST['email']);
            $groups = isset($_POST['groups']) ? scrub_input($_POST['groups']) : null;
            if ($password != "" && $password == $password2) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET password=%s WHERE id=%s",
                                quote_smart($hashed_password), quote_smart($id));
                $result = $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
            }
            // Only update permissions if groups is set and not empty
            if ($groups !== null && $groups !== '') {
                $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET username=%s,email=%s,permissions=%s WHERE id=%s",
                                quote_smart($username), quote_smart($email), quote_smart($groups), quote_smart($id));
            } else {
                $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET username=%s,email=%s WHERE id=%s",
                                quote_smart($username), quote_smart($email), quote_smart($id));
            }
            $result = $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error() .
                " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=user");
    }
    $smarty->assign(
            array(
                'form_action' => $form_action,
                'form_name' => "user",
                'username' => $username,
                'password' => $password,
                'password2' => $password2,
                'email' => $email,
                'send' => $send,
                'groups' => $groups
            )
    );

    include (BASE_PATH . 'header.php');
    $smarty->display(TEMPLATES_PATH . $config['template'] . '/admin/user_add-edit.htm');
} elseif (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (isset($_POST['submit'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $error_add = 0;
        $email = scrub_input($_POST['email']);
        $username = scrub_input($_POST['username']);
        $groups = scrub_input($_POST['groups']);
        $password = scrub_input($_POST['password']);
        $password2 = scrub_input($_POST['password2']);

        if ($email == "" || $username == "" || $password == "" || $password2 == "" || $password != $password2) {
            $error_add = 1;
        }
        if ($error_add == 0) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_profile
                    VALUES('0',%s,%s,%s,'" . time () . "',%s,'1','')",
                            quote_smart($username), quote_smart($hashed_password), quote_smart($email),
                            quote_smart($groups));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
            if (!isset($errorMsg))
                header("Location: admin.php?n=user");
        } else {
            $body = ADMIN_USER_ADD_ERROR_TEXT ." ". $db->sql_error();
        }

        $smarty->assign(
                array(
                    'body' => $body,
                )
        );

        include (BASE_PATH . 'header.php');
        $smarty->display(TEMPLATES_PATH . $config['template'] . '/page.htm');
    } else {
        $form_action = "admin.php?n=user&action=add";
        $username = '<input name="username" type="text" size="15" maxlength="45"  onkeyup="check_username(this.value)">';
        $password = '<input name="password" type="password"  size="15" >';
        $password2 = '<input name="password2" type="password"  size="15" onkeyup="check_password(this.value)">';

        //Setups groups
        $groups = '<select name="groups">';
        $sql = "SELECT * FROM " . DB_PREFIX . "_groups";
        $result = $db->sql_query($sql);
        While ($data = $db->sql_fetchrow($result)) {
            $priv_id = $data['id'];
            $priv_name = $data['name'];
            $groups .= "<option value=\"$priv_id\">$priv_name</option>";
        }
        $groups .= '</select>';

        $email = '<input name="email" type="text" size="15" maxlength="45"  onkeyup="check_email(this.value)">';
        $send = '<input type="submit" id="button" class="button" name="submit" value="Send">';
        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'form_name' => "register",
                    'username' => $username,
                    'password' => $password,
                    'password2' => $password2,
                    'email' => $email,
                    'groups' => $groups,
                    'send' => $send,
                    'on_submit' => 'onsubmit="return validate_reg();"'
                )
        );

        include (BASE_PATH . 'header.php');
        $smarty->display(TEMPLATES_PATH . $config['template'] . '/admin/user_add-edit.htm');
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = scrub_input($_GET['id']);
    if (!isset($_POST['submit'])) {
        $form_action = "admin.php?n=user&action=delete&id=" . $id . "";
        $user_back = '<a href="admin.php?n=user" class="button">' . BACK_TEXT . '</a>';
        $button = '<input type="submit" class="button" id="button" name="submit" value="'.DELETE_TEXT.'">';
        $content = QUESTION_DELETE_TEXT;
    } else {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        If ($_POST['submit'] == "Delete") {
            $sql = sprintf("DELETE FROM " . DB_PREFIX . "_profile WHERE id=%s",
                            quote_smart($id));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        if (!isset($errorMsg))
            header("Location: admin.php?n=user");
    }

    $smarty->assign(
            array(
                'form_action' => $form_action,
                'user_back' => $user_back,
                'button' => $button
            )
    );
} elseif (isset($_GET['action']) && $_GET['action'] == 'active') {
    $id = scrub_input($_GET['id']);
    $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET active='1' WHERE id=%s",
                    quote_smart($id));
    $result = $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error() .
        " @ Line " . __LINE__ . " Of " . __FILE__;
    if (!isset($errorMsg))
        header("Location: admin.php?n=user");
} elseif (isset($_GET['action']) && $_GET['action'] == 'deactive') {
    $id = scrub_input($_GET['id']);
    $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET active='0' WHERE id=%s",
                    quote_smart($id));
    $result = $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error() .
        " @ Line " . __LINE__ . " Of " . __FILE__;
    if (!isset($errorMsg))
        header("Location: admin.php?n=user");
} else {
    $sql = "SELECT * FROM " . DB_PREFIX . "_profile";
    $result = $db->sql_query($sql);
    While ($data = $db->sql_fetchrow($result)) {
        if ($data['id'] == 1) {
            array_push($user_array, array(
                'name' => $data['username'],
                'edit' => '<a href="admin.php?n=user&action=edit&id=' . $data['id'] . '">' . EDIT_TEXT . '</a>',
            ));
        } else {
            if ($data['active'] == 0)
                $state = '<a href="admin.php?n=user&action=active&id=' . $data['id'] . '">' . DEACTIVE_TEXT . '</a>';
            else
                $state= '<a href="admin.php?n=user&action=deactive&id=' . $data['id'] . '">' . ACTIVE_TEXT . '</a>';
            array_push($user_array, array(
                'name' => $data['username'],
                'edit' => '<a href="admin.php?n=user&action=edit&id=' . $data['id'] . '">' . EDIT_TEXT . '</a>',
                'del' => '<a href="admin.php?n=user&action=delete&id=' . $data['id'] . '">' . DELETE_TEXT . '</a>',
                'state' => $state
            ));
        }
    }

    $report->setMainAttributes('width="100%" cellpadding="0" cellspacing="0" border="0"');
    $report->setFieldHeadingAttributes('class="header"');
    $report->setRowAttributes('class="row1"', 'class="row2"', 'rowHover');
    $report->addOutputColumn('name', USERNAME_TEXT, 'left');
    $report->addOutputColumn('edit', EDIT_TEXT, 'left');
    $report->addOutputColumn('del', DELETE_TEXT, 'left');
    $report->addOutputColumn('state', STATE_TEXT, 'left');
    $content = $report->getListFromArray($user_array);
    $user_back = '<a href="admin.php" class="button">' . BACK_TEXT . '</a>';
    $button = '<a href="admin.php?n=user&action=add" class="button">' . ADMIN_USER_ADD_TEXT . '</a>';

    $smarty->assign(
            array(
                'user_back' => $user_back,
                'button' => $button
            )
    );
}

$smarty->assign(
        array(
            'content' => $content,
        )
);

// Use isset() to avoid undefined array key 'action' warning
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action != 'edit' && $action != 'add') {
//
// Start output of page
    include(BASE_PATH . 'header.php');
    $smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/user.htm');
}
?>