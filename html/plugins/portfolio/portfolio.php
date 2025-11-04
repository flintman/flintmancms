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

$scriptAdd .= '<link rel="stylesheet" href="plugins/portfolio/scripts/thumbnailviewer.css" type="text/css" />

<script src="plugins/portfolio/scripts/thumbnailviewer.js" type="text/javascript"></script>';

$page_lvl = isset($page_lvl) ? $page_lvl : array();
$p = isset($_GET['p']) ? scrub_input($_GET['p'], ['type' => 'alphanum', 'max_length' => 50]) : '';
$sql = sprintf("SELECT * FROM " . DB_PREFIX . "_plugins WHERE name=%s",
                quote_smart($p));
$result = $db->sql_query($sql);
$ids = $db->sql_fetchrow($result);
$sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE type='plugins' AND type_id=%s",
                quote_smart($ids['id']));
$result = $db->sql_query($sql2);
while ($data = $db->sql_fetchrow($result)) {
    $sql3 = sprintf("SELECT * FROM " . DB_PREFIX . "_groups WHERE id=%s",
                    quote_smart($data['group_id']));
    $result2 = $db->sql_query($sql3);
    $data2 = $db->sql_fetchrow($result2)
            or $errorMsg = "ERROR: " . $db->sql_error(). " @ Line " . __LINE__ . " Of " . __FILE__;
    ;
    array_push($page_lvl, $data2['name']);
}
include(INCLUDES_PATH . 'authentication.php');


$id = isset($_GET['id']) ? scrub_input($_GET['id'], ['type' => 'int']) : null;

if (isset($_GET['action']) && $_GET['action'] == 'view') {
    $body = '<table width="100%"><tr>';
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio_portfolio WHERE id=%s",
            quote_smart($id));
    $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result);

    // Escape user data for XSS protection
    $safe_name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
    $safe_date = htmlspecialchars($data['date_taken'], ENT_QUOTES, 'UTF-8');

    $title = $safe_name . "<br><font size='-2'>Photos taken on " . $safe_date . "</font>";
    $title .='<br><font size="-2" color="blue"><a href="index.php?n=plugins&p=portfolio" class="button">Back</a><br>
					Click Picture to Zoom</font>';
    $y = 1;
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio_photos WHERE portfolio_id=%s",
            quote_smart($id));
    $result2 = $db->sql_query($sql);

    while ($data2 = $db->sql_fetchrow($result2)) {
        $safe_photo_name = htmlspecialchars($data2['photo_name'], ENT_QUOTES, 'UTF-8');
        $body.=' <a href="plugins/portfolio/images/' . $safe_photo_name . '" rel="thumbnail"><img src="plugins/portfolio/images/' . $safe_photo_name . '" style="width: 120px; height: 120px" /></a> ';
        if ($y == 3) {
            $y = 0;
            $body .= '</tr><tr>';
        }
        $y++;
    }
    $body .= '</tr></table>';
} else {

    $body = '<table width="100%"><tr>';

    $title = 'Select Album below';
    $sql = "SELECT * FROM " . DB_PREFIX . "_portfolio_portfolio";
    $result = $db->sql_query($sql);
    $y = 1;

    while ($data = $db->sql_fetchrow($result)) {
        $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_portfolio_photos WHERE portfolio_id=%s",
                quote_smart($data['id']));
        $result2 = $db->sql_query($sql2);
        $data2 = $db->sql_fetchrow($result2);
        $total = $db->sql_numrows($result2);
        if ($total > 0) {
            // Escape user data for XSS protection
            $safe_album_name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
            $tooltip_content = '<center>This is album <b>' . $safe_album_name . '. </b><br>Click to Enter album</center>';
            $body .= "<td><center><a onMouseover=\"ddrivetip('" . $tooltip_content . "', 250)\";
                        onMouseout=\"hideddrivetip()\" href='index.php?n=plugins&p=portfolio&action=view&id=" . (int)$data['id'] . "'>
                            <img src=\"plugins/portfolio/images/" . htmlspecialchars($data2['photo_name'], ENT_QUOTES, 'UTF-8') . "\" style=\"width: 120px; height: 120px\" />
                                </a></center></td>";
            if ($y == 3) {
                $y = 0;
                $body .= '</tr><tr>';
            }
            $y++;
        }
    }
    $body .= '</tr></table>';
}



$smarty->assign(
        array(
            'title' => $title,
            'body' => $body,
        )
);




//
// Start output of page
//
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
?>