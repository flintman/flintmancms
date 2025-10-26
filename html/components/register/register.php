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

if (!$config['allow_login'])
    Header("Location: index.php");

if (isset($_GET['active'])) {
    $hash = scrub_input($_GET['active']);
    $username = scrub_input($_GET['id']);
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_profile
            WHERE username=%s", quote_smart($username));
    $result = $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    $data = $db->sql_fetchrow($result);

    if ($data['hash'] === $hash) {
        $sql = sprintf("UPDATE " . DB_PREFIX . "_profile SET active='1' WHERE id=%s",
                        quote_smart($data['id']));
        $result = $db->sql_query($sql)
                or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
        $body = "<a href=\"index.php\">" . COMP_NOW_ACTIVE_TEXT . "</a> ";
    } else {
        $body = "Hack Attempt Contact the Admin";
    }
    $smarty->assign(
            array(
                'body' => $body,
            )
    );

    include (BASE_PATH . 'header.php');
    $smarty->display(TEMPLATES_PATH . $config['template'] . '/page.htm');
} else {

    if (isset($_POST['submit'])) {

        $email = scrub_input($_POST['email']);
        $username = scrub_input($_POST['username']);
        $password = scrub_input($_POST['password']);
        $password2 = scrub_input($_POST['password2']);
        $code = scrub_input($_POST['vercode']);

        if ($email == "" || $username == "" || $password == "" || $password2 == "" || $password != $password2) {
            $error = 1;
        }
        if ($code != $_SESSION['vercode']){
            $error = 1;
            $errorMsg = ERROR_CODE;
        }
        if (!isset($error)) {
            $password = md5($password);
            $hash_code = md5(time() . $email . $username);
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_profile VALUES('0',%s,%s,%s,'" . time() . "',
                %s,'0',%s)", quote_smart($username), quote_smart($password), quote_smart($email),
                            quote_smart($config['default_priv']), quote_smart($hash_code));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;

            $message = "Welcome to " . $config['site_name'] . ". <br>
                        Please click the link to active your account. <br>
                        <a href=\"" . $_SERVER['HTTP_HOST'] . "/index.php?n=register&id=$username&active=$hash_code\"><b>Activate</b></a> ";

            email($_POST['email'], $config['site_name'], $message);

            $body = COMP_ACTIVE_LINK_TEXT;
        } else {
            $body = COMP_PASSWORD_MATCH_TEXT;
        }

        $smarty->assign(
                array(
                    'body' => $body,
                )
        );

        include (BASE_PATH . 'header.php');
        $smarty->display(TEMPLATES_PATH . $config['template'] . '/page.htm');
    } else {
        $form_action = "index.php?n=register";
        $username = '<input name="username" type="text" size="15" maxlength="45" onkeyup="check_username(this.value)">';
        $password = '<input name="password" type="password"  size="15">';
        $password2 = '<input name="password2" type="password"  size="15" onkeyup="check_password(this.value)">';
        $email = '<input name="email" type="text" size="15" maxlength="45" onkeyup="check_email(this.value)">';
        $code = '<input type="text" name="vercode" />';
        $send = '<input type="submit" id="button" name="submit" value="Send">';
        $smarty->assign(
                array(
                    'form_action' => $form_action,
                    'form_name' => "register",
                    'username' => $username,
                    'password' => $password,
                    'password2' => $password2,
                    'email' => $email,
                    'send' => $send,
                    'on_submit' => 'onsubmit="return validate_reg();"',
                    'username_text' => ADMIN_USER_USERNAME_TEXT,
                    'email_text' => ADMIN_USER_EMAIL_TEXT,
                    'password_text' => ADMIN_USER_PASSWORD_TEXT,
                    'passwordtwo_text' => ADMIN_USER_PASSWORD_TWO_TEXT,
                    'code_text' => REG_CODE_TEXT. ' <img src="includes/createimg.php">',
                    'code' => $code
                )
        );

        include(BASE_PATH . 'header.php');
        $smarty->display(TEMPLATES_PATH . $config['template'] . '/register.htm');
    }
}
?>
