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

error_reporting(0);

require_once('../admin/config.php');

if (defined('DB_HOST'))
    header("Location: upgrade.php");

function print_header() {
    echo '<html><head><title>Welcome to the Flintman\'s CMS 1.x.x Installation</title>
         <link rel="stylesheet" type="text/css" href="../templates/basic/style/style.css">
         </head><body><div align="center"><div id="content" style="color:ffffff; font-size:11px;">
         <div id="contentBody"><br><br>Welcome to the Flintman\'s CMS 1.x.x Installation</div><br>
	<div id="contentBody">Thank you for choosing Flintman CMS. In order to complete this install please fill
    out the details requested below. <br><strong>Please note that the database you install into should already exist.</strong></div><br>';
}

function step($header, $c1, $c2, $c3, $c4, $content) {
    echo '<table width="850" border="0" cellspacing="3" cellpadding="1" style="font-size:11px; color:#ffffff; border:1px solid #cccccc; background-color:#000000">
			  <tr valign="top">
				<td width="25%"><div align="left">Installation Progress</div></td>
				<td width="75%" colspan="2" scope="col"><div align="left">' . $header . '</div></td>
			  </tr>
			  <tr valign="top">
				<td width="25%" scope="col"><div align="left"></div></td>
				<td colspan="2" rowspan="7" scope="col">
					' . $content . '
				</td>
			  </tr>
			   <tr valign="top">
				<td width="25%" scope="col"><div align="left" style="color:' . $c1 . '">1. Initialization</div></td>
			  </tr>
			  <tr valign="top">
				<td width="25%" scope="col"><div align="left" style="color:' . $c2 . '">2. Configuration</div></td>
			  </tr>
			  <tr valign="top">
				<td width="25%" scope="col"><div align="left" style="color:' . $c3 . '">3. Install Tables</div></td>
			  </tr>
			  <tr valign="top">
				<td width="25%" scope="col"><div align="left" style="color:' . $c4 . '">4. Finalize</div></td>
			  </tr>
			  <tr valign="top">
				<td width="25%" scope="col"><div align="left"></div></td>
			  </tr>
			</table>';
}

print_header();

if (!isset($_GET['s'])) {

    $error = 0;
    // initial step
    // check if config is writeable
    if (!is_writeable('../admin/config.php')) {
        $error = 1;
        $content = '<font color=red>Error: <strong>config.php</strong> is not writeable by the server. Set proper permissions and try again.';
    } else {
        $content = '<font color=#00ff00>Success: <strong>config.php</strong> is writeable by the server.</font>';
    }

    if (!is_writeable('../templates_c/')) {
        $error = 1;
        $content = '<font color=red>Error: <strong>templates_c</strong> is not writeable by the server. Set proper permissions and try again.';
    } else {
        $content = '<font color=#00ff00>Success: <strong>templates_c</strong> is writeable by the server.</font>';
    }

    if (!is_dir('./sql')) {
        $error = 1;
        $content .= '<br><font color=red>Error: directory <strong>sql</strong> does not exist!</font>';
    } else {
        $content .= '<br><font color=#00ff00>Success: directory <strong>sql</strong> exists.</font>';
    }

    if ($error == 0) {
        $content .= '<br><form action="install.php?s=2" method="POST"><input type="submit" name="submit" value="Continue" class="mainoption"></form>';
    }

    step('Step 1: Initialization', 'red', 'white', 'white', 'white', $content);
} else if ($_GET['s'] == 2) {
    $content = '<form action="install.php?s=3" method="POST">';
    $content .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
    $content .= '<tr><td width="40%"><div align="right" style="font-size:11px; color:white">Database Name:</td><td width="60%"><input type="text" name="name" class="post"></td></tr>';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white">Database Server Hostname:</td><td><input type="text" name="hostname" class="post" value="localhost"></td></tr>';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white">Database Server Username:</td><td><input type="text" name="username" class="post"></td></tr>';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white">Database Server Password:</td><td><input type="text" name="password" class="post"></td></tr>';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white">FlintmanCMS Table Prefix:</td><td><input type="text" name="prefix" class="post" value="flintman"></td></tr>';
    $content .= '</table>';
    $content .= '<br><br><div align="center"><strong>Please verify all information before hitting submit! Failure to do so could cause unforseen failure and support will not be given!</strong>';
    $content .= '<br><br><input type="submit" value="continue" class="mainoption">';
    step('Step 2: Configuration', 'lime', 'red', 'white', 'white', $content);
} else if ($_GET['s'] == 3) {
    $name = $_POST['name'];
    $hostname = $_POST['hostname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $prefix = $_POST['prefix'];
    $sql = '';

    // config file
    $output = "<?php\n
    Define(\"DB_PREFIX\",\"" . $prefix . "\");\n
    Define(\"DB_NAME\",\"" . $name . "\");\n
    Define(\"DB_USER\",\"" . $username . "\");\n
    Define(\"DB_PASS\",\"" . $password . "\");\n
    Define(\"DB_HOST\",\"" . $hostname . "\");\n";

    $fd = fopen('../admin/config.php', 'w+');
    fwrite($fd, $output);
    fclose($fd);

    // database connection
    if (!($link = mysql_connect($hostname, $username, $password)))
        die("<font color=red>Error connecting to database. Press your browsers BACK button to try again.</font>");

    mysql_select_db($name);
    if (!$fd = fopen('./sql/install/install.sql', 'r'))
        die("<font color=red>Error opening upgrade schema.");

    if ($fd) {
        while (!feof($fd)) {
            $line = fgetc($fd);
            $sql .= $line;

            if ($line == ';') {
                $sql = substr(str_replace('`prefix', '`' . $prefix, $sql), 0, -1);
                mysql_query($sql) or die("Error installing<br>Query: $sql<br>Reported: " . mysql_error());
                $sql = '';
            }
        }
        fclose($fd);
    }

    $content = '<font color=#00ff00>If you are seeing this then no errors occured during table installation!</font>';
    $content .= '<br><br><form action="install.php?s=4" method="POST"><input type="submit" value="Continue" name="submit" class="post"></form>';

    mysql_close($link);

    step('Step 3: Install Tables', 'lime', 'lime', 'red', 'white', $content);
} else if ($_GET['s'] == 4) {
    $content = '<form action="install.php?s=done" method="POST">';
    $content .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
    $content .= '<tr><td width="25%"><div align="right" style="font-size:11px; color:white">Username:</td><td width="75%"><input type="text" name="username" class="post"></td></tr>';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white">Password:</td><td><input type="text" name="password" class="post"></td></tr>';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white">E-mail:</td><td><input type="text" name="email" class="post"></td></tr>';
    $content .= '</table>';
    $content .= '<br><br><div align="center"><strong>Please verify all information before hitting submit! Failure to do so could cause unforseen failure and support will not be given!</strong>';
    $content .= '<br><br><input type="submit" value="continue" class="mainoption">';
    step('Step 4: Authorization Setup', 'lime', 'lime', 'lime', 'red', $content);
} else if ($_GET['s'] == 'done') {
    require_once('../admin/config.php');

    $email = $_POST['email'];
    $user = $_POST['username'];
    $pass = md5($_POST['password']);
    $time = time();

    $link = mysql_connect(DB_HOST, DB_USER, DB_PASS);
    mysql_select_db(DB_NAME);
    mysql_query("INSERT INTO " . DB_PREFIX . "_profile VALUES('0','$user','$pass','$email','$time','1','1','')") or die(mysql_error());
    mysql_close($link);

    $content = 'Setup is now complete. Be sure to remove the install/ directory and click <a href="../index.php">here</a> when you have done so.';

    step('Finished!', 'lime', 'lime', 'lime', 'lime', $content);
}
?>