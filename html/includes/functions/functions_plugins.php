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


if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true) {

    if (function_exists("zip_open")) {

        if (!is_resource(zip_open($src_file))) {
            $src_file = dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . $src_file;
        }

        if (is_resource($zip = zip_open($src_file))) {
            $splitter = ($create_zip_name_dir === true) ? "." : "/";
            if ($dest_dir === false)
                $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter)) . "/";

            // Create the directories to the destination dir if they don't already exist
            create_dirs($dest_dir);

            // For every file in the zip-packet
            while ($zip_entry = zip_read($zip)) {
                // Now we're going to create the directories in the destination directories
                // If the file is not in the root dir
                $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                if ($pos_last_slash !== false) {
                    // Create the directory where the zip-entry should be saved (with a "/" at the end)
                    create_dirs($dest_dir . substr(zip_entry_name($zip_entry), 0, $pos_last_slash + 1));
                }

                // Open the entry
                if (zip_entry_open($zip, $zip_entry, "r")) {

                    // The name of the file to save on the disk
                    $file_name = $dest_dir . zip_entry_name($zip_entry);

                    // Check if the files should be overwritten or not
                    if ($overwrite === true || $overwrite === false && !is_file($file_name)) {
                        // Get the content of the zip entry
                        $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

                        if (!is_dir($file_name)) {
                            // Fix to make the file copy work with PHP < 5.0
                            if (function_exists('file_put_contents')) {
                                file_put_contents($file_name, $fstream);
                            } else {
                                $file = fopen($file_name, 'w');
                                fwrite($file, $fstream);
                                fclose($file);
                            }
                        }

                        // Set the rights
                        if (file_exists($file_name)) {
                            chmod($file_name, 0777);
                            //echo "<span style=\"color:#1da319;\">file saved: </span>".$file_name."<br />";
                        } else {
                            //echo "<span style=\"color:red;\">file not found: </span>".$file_name."<br />";
                        }
                    }

                    // Close the entry
                    zip_entry_close($zip_entry);
                }
            }
            // Close the zip-file
            zip_close($zip);
        } else {
            //echo "No Zip Archive Found.";
            return false;
        }

        return true;
    } else {
        if (version_compare(phpversion(), "5.2.0", "<"))
            $infoVersion = "(use PHP 5.2.0 or later)";

        echo "You need to install/enable the php_zip.dll extension $infoVersion";
    }
}

function create_dirs($path) {
    if (!is_dir($path)) {
        $directory_path = "";
        $directories = explode("/", $path);
        array_pop($directories);

        foreach ($directories as $directory) {
            $directory_path .= $directory . DIRECTORY_SEPARATOR;
            if (!is_dir($directory_path)) {
                mkdir($directory_path);
                chmod($directory_path, 0777);
            }
        }
    }
}

function install_sql($folder) {
    global $db;
    $sql_file = PLUGINS_PATH . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'install.sql';
    if (!file_exists($sql_file)) return "SQL file not found";
    $fd = fopen($sql_file, 'r');
    $errorMsg = '';
    if ($fd) {
        $sql_error = 0;
        $sql = '';
        while (($line = fgets($fd)) !== false) {
            $trim = trim($line);
            // Skip comments and MySQL directives
            if ($trim === '' || strpos($trim, '--') === 0 || strpos($trim, '/*') === 0 || strpos($trim, '#') === 0 || strpos($trim, '/*!') === 0) {
                continue;
            }
            $sql .= $line;
            if (substr(trim($line), -1) == ';') {
                $query = str_replace('`prefix', '`flintmancms', $sql);
                $db->sql_query($query) or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
                $sql = '';
            }
        }
        fclose($fd);
    }
    if ($errorMsg)
        return $errorMsg;
    else
        return "Done";
}

function plugin_db_setup($folder) {
    global $db;
    $errorMsg = '';
    require_once (PLUGINS_PATH . $folder . '/variable.php');
    $sql = sprintf("SELECT * FROM flintmancms_plugins WHERE name=%s",
                    quote_smart($folder));
    $result = $db->sql_query($sql);
    $data = $db->sql_numrows($result);
    if ($data > 0) {
        $sql = sprintf("UPDATE flintmancms_plugins SET active='1' WHERE name=%s",
                        quote_smart($folder));
    } else {
        $sql = sprintf("INSERT INTO flintmancms_plugins VALUES('0',%s,'1',%s)",
                        quote_smart($folder), quote_smart($plugin_version));
    }
    $db->sql_query($sql) or
            $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;

    //Adding in Menu Items
    $url = 'index.php?n=plugins&p=' . $folder;
    $count = count_links('0');
    $sql = sprintf("INSERT INTO flintmancms_links VALUES('0',%s,%s,%s,'0','1','0')",
                    quote_smart(ucfirst($folder)), quote_smart($url), quote_smart($count));
    $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line "
            . __LINE__ . " Of " . __FILE__;

    if ($errorMsg)
        return $errorMsg;
    else
        return "Done";
}

function deactivate_plugins($id, $name, $delete_tables) {
    global $db;
    $errorMsg = '';
    require_once (PLUGINS_PATH . $name . '/variable.php');
    $x = 0;
    //Deletes tables if user called to and checkes if there is even tables to Drop
    if ($delete_tables) {
        $count = count($plugin_db_tables);
        if ($count > '0') {
            while ($x < $count) {
                $sql = "DROP TABLE flintmancms_" . $plugin_db_tables[$x];
                $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line "
                        . __LINE__ . " Of " . __FILE__;
                $x++;
            }
        }
    }

    $sql = sprintf("UPDATE flintmancms_plugins SET active='0' WHERE id=%s",
                    quote_smart($id));
    $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line "
            . __LINE__ . " Of " . __FILE__;
    $url = 'index.php?n=plugins&p=' . $name;
    $sql = sprintf("DELETE FROM flintmancms_links WHERE link=%s",
                    quote_smart($url));
    $db->sql_query($sql)
            or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    if ($errorMsg)
        return $errorMsg;
    else
        return "Done";
}

function activate_plugins($name, $id=0) {
    global $db;
    $errorMsg = '';
    // Remove echo to avoid headers already sent before header() calls
    // echo "Activating Plugin: " . $name . "<br>";
    require_once (PLUGINS_PATH . $name . '/variable.php');
    $has_tables = 0;
    $x = 0;
    $count = count($plugin_db_tables);
    // Remove echo to avoid headers already sent before header() calls
    // echo "Checking for required database tables...<br>";
    if ($count > '0') {
        while ($x < $count) {
            $table_name = "flintmancms_" . $plugin_db_tables[$x];
            $check_sql = "SHOW TABLES LIKE '" . $table_name . "'";
            $result = $db->sql_query($check_sql);
            if ($db->sql_numrows($result) > 0) {
                // Table exists
                $has_tables = 1;
            } else {
                // Table does not exist
                $has_tables = 0;
                break;
            }
            $x++;
        }
    }
    if (!$has_tables) {
        $return = install_sql($name);
        if ($return != "Done")
            $errorMsg = $return;
    }
    $return = plugin_db_setup($name);
    if ($return != "Done")
        $errorMsg = $return;

    if ($errorMsg)
        return $errorMsg;
    else
        return "Done";
}

function delete_directory($dirname) {
    if (is_dir($dirname))
        $dir_handle = opendir($dirname);
    if (!$dir_handle)
        return false;
    while ($file = readdir($dir_handle)) {
        if ($file != "." && $file != "..") {
            if (!is_dir($dirname . "/" . $file))
                unlink($dirname . "/" . $file);
            else
                delete_directory($dirname . '/' . $file);
        }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
}

function upgradesql(){

}

?>