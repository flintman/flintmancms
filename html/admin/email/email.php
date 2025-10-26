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

$form_action = '';
$button = '';
$save = '';

if (isset($_POST['submit'])) {
    $message = scrub_input($_POST['message']);
    if ($config['sendviaSTMP']) {
        require_once (INCLUDES_PATH . 'scripts/email.php');
        $smtp = new Mail(
            $config['SMTP_host'],         // server
            $config['SMTP_user'],         // username
            $config['SMTP_pass'],         // password
            $config['email_admin'],       // from_user
            $config['SMTP_hostport']      // port (optional)
        );

        if ($smtp->mailit($config['email_admin'], $config['site_name'], $message)) {
            $content = 'Email Was sent';
        } else {
            $content = $smtp->printError();
        }
    } else {
        mail($config['email_admin'], $config['site_name'], $message);
        $content = ADMIN_SEND_MESSAGE_TEXT;
    }
    $page_back = '<a href="admin.php" class="button">' . BACK_TEXT . '</a>';
} elseif (isset($_POST['save'])) {
    if (isset($_POST['email_type']))
        $email_type = 1;
    else
        $email_type = 0;

    if (isset($_POST['email_mode']))
        $email = 1;
    else
        $email = 0;

    $email_host = scrub_input($_POST['email_host']);
    $email_port = scrub_input($_POST['email_port']);
    $email_user = scrub_input($_POST['email_user']);
    $email_pass = scrub_input($_POST['email_pass']);
    $admin_email = scrub_input($_POST['admin_email']);

    update_config($email_type, "sendviaSTMP");
    update_config($email_host, "SMTP_host");
    update_config($email_port, "SMTP_hostport");
    update_config($email_user, "SMTP_user");
    update_config($email_pass, "SMTP_pass");
    update_config($email, "email_errors");
    update_config($admin_email, "email_admin");
    header("Location: admin.php?n=email");
}else {
    $form_action = "admin.php?n=email";
    if ($config['email_errors'] == '0')
        $email_mode = '<input type="checkbox" name="email_mode" value="1">';
    else
        $email_mode = '<input type="checkbox" name="email_mode" value="1" checked>';

    if ($config['sendviaSTMP'])
        $email_send_type = '<input type="checkbox" name="email_type" value="1" checked>';
    else
        $email_send_type = '<input type="checkbox" name="email_type" value="1">';
    $email_host = '<input type="text" name="email_host" value="' . $config['SMTP_host'] . '">';
    $email_port = '<input type="text" name="email_port" value="' . $config['SMTP_hostport'] . '">';
    $email_user = '<input type="text" name="email_user" value="' . $config['SMTP_user'] . '">';
    $email_pass = '<input type="text" name="email_pass" value="' . $config['SMTP_pass'] . '">';
    $admin_email = '<input name="admin_email" type="text" value="' . $config['email_admin'] . '">';
    $save = '<input type="submit" value="' . SAVE_TEXT . '" name="save" class="button">';
    $content = '<textarea name="message" cols="40" rows="3"></textarea>';
    $page_back = '<a href="admin.php" class="button">' . BACK_TEXT . '</a>';
    $button = '<input type="submit" value="' . SEND_TEXT . '" name="submit" class="button">';

    $smarty->assign(
            array(
                'email_send_type' => $email_send_type,
                'email_host' => $email_host,
                'email_port' => $email_port,
                'email_user' => $email_user,
                'email_pass' => $email_pass,
                'admin_email' => $admin_email,
                'email_mode' => $email_mode,
                'admin_type_message_text' => ADMIN_TYPE_MESSAGE_TEXT,
                'email_type_text' => ADMIN_USE_SMTP_TEXT,
                'email_host_text' => ADMIN_SMTP_HOST_TEXT,
                'email_port_text' => ADMIN_SMTP_PORT_TEXT,
                'email_user_text' => ADMIN_SMTP_USER_TEXT,
                'email_pass_text' => ADMIN_SMTP_PASS_TEXT,
                'admin_email_text' => ADMIN_EMAIL_TEXT,
                'admin_email_errors_text' => ADMIN_EMAIL_ERRORS_TEXT,
            )
    );
}

$smarty->assign(
        array(
            'form_action' => $form_action,
            'content' => $content,
            'button' => $button,
            'save' => $save,
            'page_back' => $page_back,
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/email.htm');
?>