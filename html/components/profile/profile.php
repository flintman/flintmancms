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
$id = scrub_input($_SESSION['profile_id'], ['type' => 'int']);
if (!isset($_POST['submit']) || !$_POST['submit']) {
    $sql = sprintf("SELECT * FROM flintmancms_profile
            WHERE id=%s", quote_smart($id));
    $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result)
            or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    $email = $data['email'];
    $username = $data['username'];
} else {
    if (isset($_POST['submit'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $password = scrub_input($_POST['password']);
        $password2 = scrub_input($_POST['password2']);
        $email = scrub_input($_POST['email'], ['type' => 'email']);
        if ($password != "" && $password == $password2) {
            $password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $sql = sprintf("UPDATE flintmancms_profile SET password=%s WHERE id=%s",
                            quote_smart($password), quote_smart($id));
            $result = $db->sql_query($sql)
                    or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
        } elseif ($password != "") {
            $errorMsg = PASSWORD_MISMATCH_TEXT;
        }
        $sql = sprintf("UPDATE flintmancms_profile SET email=%s WHERE id=%s",
                        quote_smart($email), quote_smart($id));
        $result = $db->sql_query($sql)
                or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__;
    }
    if (!isset($errorMsg)) {
        header("Location: index.php");
    }
}
$smarty->assign(
        array(
            'username' => $username,
            'email' => $email,
            'csrf_token' => generate_csrf_token()
        )
);

include (BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . $config['template'] . '/profile.htm');
?>
