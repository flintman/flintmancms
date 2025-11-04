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

/**
 * Sanitize and validate user input
 *
 * IMPORTANT: This function has been enhanced with additional validation while
 * maintaining backward compatibility with the original $html_allowed parameter.
 *
 * @param mixed $value The value to sanitize (string or array)
 * @param bool|array $options Either boolean for backward compatibility, or array of options:
 *                            - 'max_length' => int (default: 10000)
 *                            - 'allow_html' => bool (default: false)
 *                            - 'type' => string (text|email|url|int|float|alpha|alphanum)
 *                            - 'allowed_tags' => string (default: '' - no tags)
 * @return mixed Sanitized value (string, int, float, or array)
 *
 * Examples:
 *   scrub_input($input) - Basic sanitization (backward compatible)
 *   scrub_input($input, true) - Allow HTML (backward compatible - NOT RECOMMENDED)
 *   scrub_input($email, ['type' => 'email']) - Email validation
 *   scrub_input($age, ['type' => 'int']) - Integer validation
 *   scrub_input($bio, ['max_length' => 500]) - Length-limited text
 */
function scrub_input($value = "", $options = false) {
    // Backward compatibility: if $options is boolean, it's the old $html_allowed parameter
    if (is_bool($options)) {
        $html_allowed = $options;
        $options = [
            'allow_html' => $html_allowed,
            'max_length' => 10000,
            'type' => 'text',
            'allowed_tags' => $html_allowed ? '<br><a>' : ''
        ];
    } else {
        // New enhanced options
        $defaults = [
            'max_length' => 10000,
            'allow_html' => false,
            'type' => 'text', // text, email, url, int, float, alpha, alphanum
            'allowed_tags' => '' // Only used if allow_html is true
        ];
        $options = is_array($options) ? array_merge($defaults, $options) : $defaults;
    }

    // Handle arrays recursively
    if (is_array($value)) {
        return array_map(function($v) use ($options) {
            return scrub_input($v, $options);
        }, $value);
    }

    // Convert to string and trim whitespace
    $value = trim((string)$value);

    // Empty string check
    if ($value === '') {
        return $options['type'] === 'int' ? 0 : ($options['type'] === 'float' ? 0.0 : '');
    }

    // Type-specific validation
    switch ($options['type']) {
        case 'email':
            // Validate and sanitize email
            $value = filter_var($value, FILTER_SANITIZE_EMAIL);
            $validated = filter_var($value, FILTER_VALIDATE_EMAIL);
            return $validated !== false ? $validated : '';

        case 'url':
            // Validate and sanitize URL
            $value = filter_var($value, FILTER_SANITIZE_URL);
            $validated = filter_var($value, FILTER_VALIDATE_URL);
            return $validated !== false ? $validated : '';

        case 'int':
            // Validate integer
            $validated = filter_var($value, FILTER_VALIDATE_INT);
            return $validated !== false ? $validated : 0;

        case 'float':
            // Validate float
            $validated = filter_var($value, FILTER_VALIDATE_FLOAT);
            return $validated !== false ? $validated : 0.0;

        case 'alpha':
            // Only letters and spaces
            return preg_replace('/[^a-zA-Z\s]/', '', $value);

        case 'alphanum':
            // Only letters, numbers, and spaces
            return preg_replace('/[^a-zA-Z0-9\s]/', '', $value);

        case 'text':
        default:
            // Length validation (before tag stripping to prevent bypass)
            if (strlen($value) > $options['max_length']) {
                $value = substr($value, 0, $options['max_length']);
            }

            // HTML handling
            if ($options['allow_html'] && !empty($options['allowed_tags'])) {
                // Strip all tags except allowed ones
                $value = strip_tags($value, $options['allowed_tags']);
                // Don't escape if HTML is explicitly allowed
            } else {
                // Strip ALL tags
                $value = strip_tags($value);
                // Escape HTML special characters
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }

            return $value;
    }
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