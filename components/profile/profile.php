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
$id = scrub_input($_SESSION['profile_id']);
if (!$_POST['submit']) {
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_profile
            WHERE id=%s", quote_smart($id));
    $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result)
            or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
    $form_action = "index.php?n=profile";
    $password = '<input name="password" type="password"  size="15" >';
    $password2 = '<input name="password2" type="password"  size="15" >';
    $email = '<input name="email" type="text" size="25" value="' . $data['email'] . '" maxlength="45">';
    $send = '<input type="submit" id="button" name="submit" value="Send">';
} else {
    if (isset($_POST['submit'])) {
        $password = scrub_input($_POST['password']);
        $password2 = scrub_input($_POST['password2']);
        $email = scrub_input($_POST['email']);
        if ($password != "" && $password = $password2) {
            $password = md5($password);
            $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET password=%s WHERE id=%s",
                            quote_smart($password), quote_smart($id));
            $result = $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__ . " Of " . __FILE__;
        }
        $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET email=%s WHERE id=%s",
                        quote_smart($email), quote_smart($id));
        $result = $db->sql_query($sql)
                or $errorMsg = "ERROR: " . mysql_error() . " @ Line " . __LINE__;
    }
    if (!isset($errorMsg))
        header("Location: index.php");
}
$smarty->assign(
        array(
            'form_action' => $form_action,
            'form_name' => "user",
            'password' => $password,
            'password2' => $password2,
            'email' => $email,
            'send' => $send,
            'change_password' => ADMIN_USER_ONLY_CHANGE_TEXT,
            'email_text' => ADMIN_USER_EMAIL_TEXT,
            'password_text' => ADMIN_USER_PASSWORD_TEXT,
            'passwordtwo_text' => ADMIN_USER_PASSWORD_TWO_TEXT
        )
);

include (BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . $config['template'] . '/register.htm');
?>
