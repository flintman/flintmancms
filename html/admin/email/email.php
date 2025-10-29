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
$content = '';
$page_back = BACK_TEXT ;

if (isset($_POST['submit'])) {
    $message = scrub_input($_POST['message']);
    email($config['email_admin'], $message);
    $content = ADMIN_SEND_MESSAGE_TEXT;
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
    $email_encryption = scrub_input($_POST['email_encryption']);
    $admin_email = scrub_input($_POST['admin_email']);

    update_config($email_type, "sendviaSTMP");
    update_config($email_host, "SMTP_host");
    update_config($email_port, "SMTP_hostport");
    update_config($email_user, "SMTP_user");
    update_config($email_pass, "SMTP_pass");
    update_config($email_encryption, "SMTP_encryption");
    update_config($email, "email_errors");
    update_config($admin_email, "email_admin");
    header("Location: admin.php?n=email");
}else {
    $form_action = "admin.php?n=email";
    // Assign raw values and checked/selected states for template rendering
    $email_mode_checked = ($config['email_errors'] == '1');
    $email_send_type_checked = ($config['sendviaSTMP'] == '1');
    $email_host_value = $config['SMTP_host'];
    $email_port_value = $config['SMTP_hostport'];
    $email_user_value = $config['SMTP_user'];
    $email_pass_value = $config['SMTP_pass'];
    $admin_email_value = $config['email_admin'];
    $current_encryption = isset($config['SMTP_encryption']) ? $config['SMTP_encryption'] : 'none';
    $save_text = SAVE_TEXT;
    $send_text = SEND_TEXT;

    $smarty->assign(
        array(
            'email_mode_checked' => $email_mode_checked,
            'email_send_type_checked' => $email_send_type_checked,
            'email_host_value' => $email_host_value,
            'email_port_value' => $email_port_value,
            'email_user_value' => $email_user_value,
            'email_pass_value' => $email_pass_value,
            'admin_email_value' => $admin_email_value,
            'current_encryption' => $current_encryption,
            'save_text' => $save_text,
            'send_text' => $send_text,
            'admin_type_message_text' => ADMIN_TYPE_MESSAGE_TEXT,
            'email_type_text' => ADMIN_USE_SMTP_TEXT,
            'email_host_text' => ADMIN_SMTP_HOST_TEXT,
            'email_port_text' => ADMIN_SMTP_PORT_TEXT,
            'email_user_text' => ADMIN_SMTP_USER_TEXT,
            'email_pass_text' => ADMIN_SMTP_PASS_TEXT,
            'email_encryption_text' => 'SMTP Encryption',
            'admin_email_text' => ADMIN_EMAIL_TEXT,
            'admin_email_errors_text' => ADMIN_EMAIL_ERRORS_TEXT,
        )
    );
}

$smarty->assign(
        array(
            'form_action' => $form_action,
            'page_back' => $page_back,
            'content' => $content
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/email.htm');
?>