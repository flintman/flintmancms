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

//Pulls all text files in this folder and displays
foreach (glob("admin/help/*.txt") as $filename) {
    $file = $filename;
    $contents = file($file);
    $content = implode($contents);
    $string .= nl2br($content).' <br><br>';
}

$smarty->assign(
        array(
            'head' => ADMIN_HELP_TEXT,
            'help' => $string,
        )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/help.htm');

?>
