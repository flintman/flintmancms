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

$smarty->assign(
        array(
            'disclamer' => $config['disclamer'] . '<br> <a href="http://flintmancomputers.com">FlintmanCMS</a>  ' . VERSION,
        )
);

$smarty->display('../templates/' . $config['template'] . '/footer.htm');

// some debugging, changes every patch
if ($config['debug'] == 1) {
    $out = '<center><div align="left">';
    $out .= '<span class="headmedred">Config Data... <br><br>';
    $out .= "Templates = " . $config['template'] . "<br>";
    $out .= "username = " . $_SESSION['username'] . "<br>";
    $out .= "Sesson type = " . $_SESSION['priv'] . "<br>";
    $out .= "Initiated ? = " . $_SESSION['initiated'] . "<br>";
    $out .= '<br><br>Userdata array listing... <br><br>';
    $out .= "Maintance = " . $config['maintain'] . "<br>";
    $out .= '</span></div></center>';
    print_r($out);
    print_r($page_lvl);
}

?>