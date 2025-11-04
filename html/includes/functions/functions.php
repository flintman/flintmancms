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

// Include Mail class for email functionality

if (!defined('HEADER_SEPARATOR')) {
    define('HEADER_SEPARATOR', ' | ');
}

function email($to, $message) {
    global $config;
    $mail = new Mail(
                $config['SMTP_host'],        // SMTP server
                $config['SMTP_user'],     // Username
                $config['SMTP_pass'],                   // Password (replace with real password)
                $config['SMTP_user'],     // From email
                $config['SMTP_hostport'],                         // Port
                $config['SMTP_encryption'],                       // Encryption (SSL for port 465, not STARTTLS)
                'Website Admin',        // From name
            );
        if ($mail->mailit($to, $config['site_name'], $message)) {
            $content = 'Email Was sent';
        } else {
            $content = $mail->printError();
        }
}

function dsSlash($string) {
    // get_magic_quotes_gpc() was removed in PHP 8.0, always apply addslashes
    return addslashes($string);
}

function formatHeader($str, $arg) {
    if ($arg == 0) // left side
        return HEADER_SEPARATOR . $str;
    if ($arg == 1) // right side
        return $str . HEADER_SEPARATOR;
}

function count_links($sublink) {
    global $db;
    //Gets total number of menu items
    $count = 1;
     $result = $db->sql_query("SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link = '$sublink'");

    While ($data = $db->sql_fetchrow($result)) {
        $count++;
    }
    return $count;
}

function quote_smart($value = "", $nullify = false, $conn = null) {
    //reset default if second parameter is skipped
    $nullify = ($nullify === null) ? (false) : ($nullify);
    // magic_quotes_gpc is removed in PHP 7+, so just use the value directly
    // $value = (get_magic_quotes_gpc()) ? (stripslashes($value)) : ($value);
    //check for null/unset/empty strings (takes advantage of short-circuit evals to avoid a warning)
    if ((!isset($value)) || (is_null($value)) || ($value === "")) {
        $value = ($nullify) ? ("NULL") : ("''");
    } else {
        if (is_string($value)) {
            // value is a string and should be quoted; use mysqli_real_escape_string if possible
            global $db;
            if (isset($db) && isset($db->db_connect_id)) {
                $escaped = $db->db_connect_id->real_escape_string($value);
                $value = "'" . $escaped . "'";
            } else {
                $value = "'" . addslashes($value) . "'";
            }
        } else {
            //value is not a string; if not numeric, bail with error
            $value = (is_numeric($value)) ? ($value) : ("'ERROR: unhandled datatype in quote_smart'");
        }
    }
    return $value;
}

function scrub_input($value = "", $html_allowed = false) {
    $value = strip_tags((string)$value, '<br><a>');

    if (!$html_allowed)
        $value = htmlspecialchars($value);

    return $value;
}

function update_config($value, $name) {
    global $db;
    $sql = sprintf("UPDATE " . DB_PREFIX . "_config SET value=%s WHERE name=%s",
                    quote_smart($value), quote_smart($name));
    $db->sql_query($sql) or $errorMsg = "ERROR: " . $db->sql_error()['message'] . " @ Line "
        . __LINE__ . " Of " . __FILE__;
}

//Checks to see if you can display the menu link
function check_menu($priv, $link_id) {
    global $db;
    $canview = 0;
    $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE group_id=%s",
                    quote_smart($priv));
    $result2 = $db->sql_query($sql2);
    while ($data2 = $db->sql_fetchrow($result2)) {
        if ($data2['link_id'] == $link_id) {
            $canview = 1;
            break;
        } else {
            $canview = 0;
        }
    }

    if ($canview)
        return true;
    else
        return false;
}

//Gets link ID
function getlinkID($type, $type_id) {
    global $db;
    if ($type == "plugins") {
        $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_plugins WHERE id=%s",
                        quote_smart($type_id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result);
        if (!$data) return null;
        $link ='index.php?n=plugins&p='.$data['name'];
    } else {
        $link = 'index.php?n=page&page_id=' . $type_id;
    }
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_links WHERE link=%s",
                    quote_smart($link));
    $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result);
    if (!$data) return null;
    return $data['id'];
}

/**
 * Comprehensive file upload validation
 * Protects against malicious file uploads, validates file types, sizes, and generates safe filenames
 *
 * @param array $file The $_FILES array element for the uploaded file
 * @param array $allowed_types Array of allowed MIME types (default: image types)
 * @param int $max_size Maximum file size in bytes (default: 5MB)
 * @return array ['valid' => bool, 'error' => string|null, 'filename' => string|null, 'mime' => string|null]
 */
function validate_upload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file) || !isset($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension'
        ];
        $error = isset($error_messages[$file['error']])
            ? $error_messages[$file['error']]
            : 'Unknown upload error: ' . $file['error'];
        return ['valid' => false, 'error' => $error];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large (max ' . round($max_size / 1048576, 2) . 'MB)'];
    }

    // Check file size is not zero
    if ($file['size'] == 0) {
        return ['valid' => false, 'error' => 'File is empty'];
    }

    // Validate MIME type using fileinfo
    if (!function_exists('finfo_open')) {
        return ['valid' => false, 'error' => 'Server configuration error: fileinfo extension missing'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }

    // For images, perform additional validation
    if (strpos($mime, 'image/') === 0) {
        $img_info = @getimagesize($file['tmp_name']);
        if ($img_info === false) {
            return ['valid' => false, 'error' => 'Not a valid image file'];
        }

        // Verify the MIME type matches getimagesize result
        $image_mime = $img_info['mime'];
        if ($image_mime !== $mime) {
            return ['valid' => false, 'error' => 'Image MIME type mismatch'];
        }
    }

    // Generate safe random filename
    $ext_map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    $safe_ext = isset($ext_map[$mime]) ? $ext_map[$mime] : 'bin';
    $safe_name = bin2hex(random_bytes(16)) . '.' . $safe_ext;

    return [
        'valid' => true,
        'filename' => $safe_name,
        'mime' => $mime,
        'error' => null
    ];
}

?>