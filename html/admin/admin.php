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
if (!defined('IN_ADMIN_CMS')) {
    die("ERROR - Hacking attempt");
}
//If nothing goes to frontpage
// page authentication
array_push($page_lvl, "Admin");
include(INCLUDES_PATH . 'authentication.php');

//Version Checking

$current_version = VERSION_NUMBER;
$site_version = SITE_VERSION;
$version_info = '';
if ($fsock = @fsockopen('www.flintmancomputers.com', 80, $errno, $errstr, 10)) {
    @fputs($fsock, "GET /flint_check/ver_check_FCMS.txt HTTP/1.1\r\n");
    @fputs($fsock, "HOST: www.flintmancomputers.com\r\n");
    @fputs($fsock, "Connection: close\r\n\r\n");

    $get_info = false;
    while (!@feof($fsock)) {
        if ($get_info) {
            $version_info .= @ fread($fsock, 1024);
        } else {
            if (@fgets($fsock, 1024) == "\r\n") {
                $get_info = true;
            }
        }
    }
    @fclose($fsock);
    $version_info = explode("\n", $version_info);
    $latest_major_version = (int) $version_info[0];
    $latest_minor_version = (int) $version_info[1];
    $latest_patch_revision = (int) $version_info[2];
    $latest_sub_major_version = (int) $version_info[4];
    $latest_sub_minor_version = (int) $version_info[5];
    $latest_sub_patch_version = (int) $version_info[6];
    $latest_version = (int) $version_info[0] . '.' . (int) $version_info[1] . '.' . (int) $version_info[2];

    if ($current_version == $latest_version && $current_version == $site_version) {
        $version_info = '<p style="color:green">' . ADMIN_UPTODATE_TEXT . '</p>';
    } elseif ($current_version != $site_version) {
        $version_info = '<p style="color:red">' . ADMIN_VERSION_ISSUE_TEXT . '</p>';
    } else {
        $version_info = '<div style="color:red">' . ADMIN_VERSION_OUTDATE_TEXT . '<br> ' . ADMIN_LATEST_TEXT .
                $latest_version . ADMIN_YOUR_VERSION_TEXT . $current_version . '<br>
            </div><br>';
    }
} else {
    if ($errstr) {
        $version_info = '<p style="color:red">Error: ' . $errstr . '</p>';
    } else {
        $version_info = '<p style="color:red">FSocket is Disabled</p>';
    }
}

$site_info = '

<table width="100%" border="1" cellpadding="5">
                <tr>
                    <td width="35%">'.ADMIN_SITE_VER_TEXT.'</td>
                    <td>' . SITE_VERSION . '</td>
                </tr>
                <tr>
                    <td>'.ADMIN_DATABASE_VER_TEXT.'</td>
                    <td>' . VERSION_NUMBER . '</td>
                </tr>
                <tr>
                    <td>'.ADMIN_PHP_VER_TEXT.'</td>
                    <td>' . PHP_VERSION . '</td>
                </tr>

                <tr>
                    <td>'.ADMIN_MYSQL_VER_TEXT.'</td>
                    <td>' . mysqli_get_server_info($db->db_connect_id) . '</td>
                </tr>

               </table>';


$log_info ='<table width="100%" border="1" cellpadding="5"><br>';

$sql = "SELECT * FROM " . DB_PREFIX . "_logs ORDER by id DESC LIMIT 10";
$result = $db->sql_query($sql);
While ($data = $db->sql_fetchrow($result)) {
     $timestamp = date('Y-m-d H:i:s', $data['timestamp']);
    $log_info .='<tr>
                    <td width="5%">'.$data['id'].'</td>
                    <td width="80%">' . $data['error'] . '</td>
                    <td width="15%">' . $timestamp . '</td>
                </tr>';
}

$log_info .='</table>';

$version_changes = '<table width="100%" border="1" cellpadding="5">';

$filename = array();

$filename = glob("docs/version_changes/*.txt");
arsort($filename);
//Pulls all text files in this folder and displays
foreach ($filename as $key => $val) {
    $file = $filename[$key];
    $contents = file($file);
    $content = implode($contents);
    $version_changes .= nl2br($content).' <br><br>';
}

$version_changes .= '</table>';

$smarty->assign(
        array(
            'site_info' => $site_info,
            'version_info' => $version_info,
            'log_info' => $log_info,
            'version_changes' => $version_changes,
            'version_check_text' => ADMIN_VERSION_CHECK_TEXT,
            'flint_stats_text' => ADMIN_FLINT_STAT_TEXT,
            'flint_log_text' => ADMIN_FLINT_LOG_TEXT,
            'flint_version_text' => ADMIN_FLINT_VERSION_TEXT
        )
);

// Start output of page
//
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . $config['template'] . '/admin/admin.htm');
?>
