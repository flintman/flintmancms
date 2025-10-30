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

function login() {

    global $groups;
    global $db;
    global $config;

    if (isset($_POST['username'])) {
        $username = strtolower(scrub_input($_POST['username']));
        $password = md5(scrub_input($_POST['password']));
        $remember_token = null;
    } elseif (isset($_COOKIE['username']) && isset($_COOKIE['remember_token'])) {
        $username = $_COOKIE['username'];
        $remember_token = $_COOKIE['remember_token'];
        $password = null;
    } else {
        logout();
    }

    $escaped_username = mysqli_real_escape_string($db->db_connect_id, $username);
    $sql = "SELECT * FROM " . DB_PREFIX . "_profile WHERE username='" . $escaped_username . "'";
    $result = $db->sql_query($sql)
        or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;

    $data = $db->sql_fetchrow($result);
    if ($config['allow_login'] == false && $data['permissions'] != '1') {
        $data = null;
    }
    if ($data) {
        if ($password !== null && $password == $data['password']) {
            // Password login, generate new token if autologin requested
            if (isset($_POST['autologin'])) {
                $new_token = bin2hex(random_bytes(32));
                setcookie('username', strtolower($data['username']), time() + 2629743);
                setcookie('remember_token', $new_token, time() + 2629743);
                // Save token in DB
                $escaped_token = mysqli_real_escape_string($db->db_connect_id, $new_token);
                $update_sql = "UPDATE " . DB_PREFIX . "_profile SET remember_token='" . $escaped_token . "' WHERE id=" . (int)$data['id'];
                $db->sql_query($update_sql);
            } else {
                // Remove any old token
                setcookie('remember_token', '', time() - 3600);
                $update_sql = "UPDATE " . DB_PREFIX . "_profile SET remember_token=NULL WHERE id=" . (int)$data['id'];
                $db->sql_query($update_sql);
            }
        } elseif ($remember_token !== null && $remember_token === $data['remember_token'] && !empty($remember_token)) {
            // Token login, rotate token
            $new_token = bin2hex(random_bytes(32));
            setcookie('username', strtolower($data['username']), time() + 2629743);
            setcookie('remember_token', $new_token, time() + 2629743);
            $escaped_token = mysqli_real_escape_string($db->db_connect_id, $new_token);
            $update_sql = "UPDATE " . DB_PREFIX . "_profile SET remember_token='" . $escaped_token . "' WHERE id=" . (int)$data['id'];
            $db->sql_query($update_sql);
        } else {
            // Invalid login
            return 0;
        }

        // set user variables
        $_SESSION['username'] = $data['username'];
        $_SESSION['user_logged_in'] = 1;
        $_SESSION['profile_id'] = $data['id'];
        $_SESSION['priv'] = $data['permissions'];
        $_SESSION['vercode'] = '';
        return 1;
    }
    return 0;
}

function logout() {

    // Remove token from DB if possible
    if (isset($_SESSION['profile_id']) && $_SESSION['profile_id'] > 0) {
        global $db;
        $update_sql = "UPDATE " . DB_PREFIX . "_profile SET remember_token=NULL WHERE id=" . (int)$_SESSION['profile_id'];
        $db->sql_query($update_sql);
    }

    // unset the session and remove all cookies
    unset($_SESSION['initiated']);
    unset($_SESSION['username']);
    unset($_SESSION['profile_id']);
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['priv']);
    unset($_SESSION['vercode']);
    setcookie('username', '', time() - 2629743);
    setcookie('remember_token', '', time() - 2629743);

    $_SESSION['initiated'] = true;
    $_SESSION['username'] = 'Anonymous';
    $_SESSION['session_logged_in'] = 0;
    $_SESSION['user_logged_in'] = 0;
    $_SESSION['profile_id'] = -1;
    $_SESSION['priv'] = 2;

}

session_start();
// good ole authentication
$_SESSION['name'] = $session_name;

// set session defaults
if (!isset($_SESSION['initiated'])) {
    if (isset($_COOKIE['username']) && isset($_COOKIE['remember_token'])) {
        login();
    } else {
        session_regenerate_id();
        $_SESSION['initiated'] = true;
        $_SESSION['username'] = 'Anonymous';
        $_SESSION['session_logged_in'] = 0;
        $_SESSION['user_logged_in'] = 0;
        $_SESSION['profile_id'] = -1;
        $_SESSION['priv'] = 2;
    }
}

if (isset($_POST['username']))
    login();

if (!$config['allow_login'] && $_SESSION['priv'] != '1')
    logout();
?>