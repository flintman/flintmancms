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

    if (isset($_POST['username'])) {
        $username = strtolower(scrub_input($_POST['username']));
        $password = md5(scrub_input($_POST['password']));
    } elseif (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
        $username = $_COOKIE['username'];
        $password = $_COOKIE['password'];
    } else {
        logout();
    }

    $sql = "SELECT * FROM " . DB_PREFIX . "_profile";
    $result = $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    ;

    while ($data = $db->sql_fetchrow($result)) {

        if ($username == strtolower($data['username']) && $password == $data['password']) {

            if (isset($_POST['autologin'])) {

                // they want automatic logins so set the cookie
                // set to expire in one month
                setcookie('username', strtolower($data['username']), time() + 2629743);
                setcookie('password', $data['password'], time() + 2629743);
            }

            // set user variables
            $_SESSION['username'] = $data['username'];
            $_SESSION['user_logged_in'] = 1;
            $_SESSION['profile_id'] = $data['id'];
            $_SESSION['priv'] = $data['permissions'];
            $_SESSION['vercode'] = '';


            return 1;
        }
    }
    return 0;
}

function logout() {

    // unset the session and remove all cookies
    unset($_SESSION['initiated']);
    unset($_SESSION['username']);
    unset($_SESSION['profile_id']);
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['priv']);
    unset($_SESSION['vercode']);
    setcookie('username', '', time() - 2629743);
    setcookie('password', '', time() - 2629743);
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
    if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
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