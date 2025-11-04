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
    $hash = scrub_input($_GET['active'], ['type' => 'alphanum', 'max_length' => 64]);
    $username = scrub_input($_GET['id'], ['type' => 'alphanum', 'max_length' => 45]);
    $sql = sprintf("SELECT * FROM flintmancms_profile
            WHERE username=%s", quote_smart($username));
    $result = $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    $data = $db->sql_fetchrow($result);

    if ($data['hash'] === $hash) {
        $sql = sprintf("UPDATE flintmancms_profile SET active='1' WHERE id=%s",
                        quote_smart($data['id']));
        $result = $db->sql_query($sql)
                or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
        $body = "<a href=\"index.php\">" . COMP_NOW_ACTIVE_TEXT . "</a> ";
    } else {
        $body = HACK_ATTEMPT_TEXT;
        $errorMsg = "Hacking attempt during activation for user: " . $username;
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

        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }

        $email = scrub_input($_POST['email'], ['type' => 'email']);
        $username = scrub_input($_POST['username'], ['type' => 'alphanum', 'max_length' => 45]);
        $password = scrub_input($_POST['password']);
        $password2 = scrub_input($_POST['password2']);
        $code = scrub_input($_POST['vercode'], ['type' => 'alphanum', 'max_length' => 10]);

        if ($email == "" || $username == "" || $password == "" || $password2 == "" || $password != $password2) {
            $error = 1;
        }
        if ($code != $_SESSION['vercode']){
            $error = 1;
            $errorMsg = ERROR_CODE;
        }
        if (!isset($error)) {
            $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $hash_code = md5(time() . $email . $username);
            $sql = sprintf("INSERT INTO flintmancms_profile VALUES('0',%s,%s,%s,'" . time() . "',
                %s,'0',%s)", quote_smart($username), quote_smart($password), quote_smart($email),
                            quote_smart($config['default_priv']), quote_smart($hash_code));
            $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;

            $message = WELCOME_TO_TEXT . " " . $config['site_name'] . ". <br>
                       " . COMP_CLICK_ACTIVATE_TEXT . " <br>
                        <a href=\"" . $_SERVER['HTTP_HOST'] . "/index.php?n=register&id=$username&active=$hash_code\"><b>Activate</b></a> ";

            email($email, $message);

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
        $smarty->assign(
                array(
                    'username' => $username,
                    'on_submit' => 'onsubmit="return validate_reg();"',
                    'code_text' => REG_CODE_TEXT. ' <img src="includes/createimg.php">',
                    'csrf_token' => generate_csrf_token()
                )
        );

        include(BASE_PATH . 'header.php');
        $smarty->display(TEMPLATES_PATH . $config['template'] . '/register.htm');
    }
}
?>
