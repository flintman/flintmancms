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


/* backup the db OR just a table */

function backup_tables($host, $user, $pass, $name, $tables = '*') {

    $link = mysql_connect($host, $user, $pass);
    mysql_select_db($name, $link);

    //get all of the tables
    if ($tables == '*') {
        $tables = array();
        $result = mysql_query('SHOW TABLES');
        while ($row = mysql_fetch_row($result)) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    //cycle through
    foreach ($tables as $table) {
        $result = mysql_query('SELECT * FROM ' . $table);
        $num_fields = mysql_num_fields($result);

        $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE ' . $table));
        $return.= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysql_fetch_row($result)) {
                $return.= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return.= '"' . $row[$j] . '"';
                    } else {
                        $return.= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return.= ',';
                    }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n\n";
    }
    //save file
    $handle = fopen('./backup/db-backup.sql', 'w+');
    fwrite($handle, $return);
    fclose($handle);
}

function print_header() {
    echo '<html><head><title>Welcome to the Flintman\'s CMS 1.x.x Upgrade</title>
         <link rel="stylesheet" type="text/css" href="../templates/basic/style/style.css">
         </head><body><div align="center"><div id="content" style="color:ffffff; font-size:11px;">
         <div id="contentBody"><br><br>Welcome to the Flintman\'s CMS 1.x.x Upgrade</div><br>
	<div id="contentBody">Thank you for choosing Flintman CMS. In order to complete this upgrate please fill
    out the details requested below. <br><strong>Please note that the database will be backed up to /install/backup
    folder prior to any changes</strong></div><br>';
}

function step($header, $c1, $c2, $c3, $content) {
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
				<td width="25%" scope="col"><div align="left"></div></td>
			  </tr>
			</table>';
}

print_header();
require_once '../admin/config.php';

if (!isset($_GET['s'])) {
    $error = 0;

    if (!is_writeable('./backup/')) {
        $error = 1;
        $content = '<font color=red>Error: <strong>/install/backup</strong> is not writeable by the server. Set proper permissions and try again.';
    } else {
        $content = '<font color=#00ff00>Success: <strong>/install/backup</strong> is writeable by the server.</font>';
    }

    if (!is_dir('./sql')) {
        $error = 1;
        $content .= '<br><font color=red>Error: directory <strong>sql</strong> does not exist!</font>';
    } else {
        $content .= '<br><font color=#00ff00>Success: directory <strong>sql</strong> exists.</font>';
    }

    if ($error == 0) {
        $content .= '<br><form action="upgrade.php?s=2" method="POST"><input type="submit" name="submit" value="Continue" class="mainoption"></form>';
    }

    step('Step 1: Initialization', 'red', 'white', 'white', $content);
} else if ($_GET['s'] == 2) {
    $dir = 'sql/upgrade/';
    $dh = opendir($dir);
    while (false != ($filename = readdir($dh))) {
        $files[] = $filename;
    }
    sort($files);
    array_shift($files);
    array_shift($files);
    backup_tables(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $link = mysql_connect(DB_HOST, DB_USER, DB_PASS);
    mysql_select_db(DB_NAME);
    $sql = "SELECT * FROM " . DB_PREFIX . "_version ORDER BY version_number DESC";
    $result = mysql_query($sql);
    $data = mysql_fetch_array($result);
    $version = $data['version_number'] . '.sql';
    $key = array_search($version, $files);
    if (isset($files[$key]) && $version == $files[$key]) {
        $type = 'You will be upgrading from ' . $data['version_number'];
        $type .= '<input type="hidden" name="type" class="post" value="' . $files[$key] . '">';
    }


    mysql_close($link);

    if (!isset($type)) {
        header("Location: upgrade.php?s=done");
    }

    $content = '<form action="upgrade.php?s=3" method="POST">';
    $content .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
    $content .= '<tr><td><div align="right" style="font-size:11px; color:white"></td><td>' . $type . '</td></tr>';
    $content .= '</table>';
    $content .= '<br><br><div align="center"><strong>Please verify Version before hitting submit! Failure to do so could cause unforseen failure and support will not be given!</strong>';
    $content .= '<br><br><input type="submit" value="continue" class="mainoption">';
    step('Step 2: Configuration', 'lime', 'red', 'white', $content);
} else if ($_GET['s'] == 3) {
    $sql_file = $_POST['type'];
    $sql = '';


    // database connection
    if (!($link = mysql_connect(DB_HOST, DB_USER, DB_PASS)))
        die("<font color=red>Error connecting to database. Press your browsers BACK button to try again.</font>");

    mysql_select_db(DB_NAME);
    if (!$fd = fopen('./sql/upgrade/' . $sql_file, 'r'))
        die("<font color=red>Error opening SQL.");

    if ($fd) {
        while (!feof($fd)) {
            $line = fgetc($fd);
            $sql .= $line;

            if ($line == ';') {
                $sql = substr(str_replace('`prefix', '`' . DB_PREFIX, $sql), 0, -1);
                mysql_query($sql) or die("Error installing<br>Query: $sql<br>Reported: " . mysql_error());
                $sql = '';
            }
        }
        fclose($fd);
    }

    $content = '<font color=#00ff00>If you are seeing this then no errors occured during table installation!</font>';
    $content .= '<br><br><form action="upgrade.php?s=done" method="POST"><input type="submit" value="Continue" name="submit" class="post"></form>';

    mysql_close($link);

    step('Step 3: Install Tables', 'lime', 'lime', 'red', $content);
} else if ($_GET['s'] == 'done') {

    $content = 'Setup is now complete. Be sure to remove the install/ directory and click <a href="../index.php">here</a> when you have done so.';

    step('Finished!', 'lime', 'lime', 'lime', $content);
}
?>