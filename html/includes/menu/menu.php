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


$sm = new SmartyMenu();
$sm->initMenu($menu);

//Gets all pages names and puts in menu

$sm->initItem($item);
$sm->setItemText($item, HOME_TEXT);
$sm->setItemLink($item, "index.php");
$item['new_window'] = 0;
$sm->addMenuItem($menu, $item);

if ($_SESSION['user_logged_in']) {
    $sm->initItem($item);
    $sm->setItemText($item, PROFILE_TEXT);
    $sm->setItemLink($item, "index.php?n=profile" . $config['add_link']);
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);
}

$sql = "SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link='0' ORDER BY `link_order` ASC";
$result = $db->sql_query($sql);

while ($data = $db->sql_fetchrow($result)) {
    $display = false;
    $submenu = false;
    $sm->initMenu($sub);
    $display = check_menu($_SESSION['priv'], $data['id']);

    if (!$display) {
        $check = strpos($data['link'], "page");
        $checktwo = strpos($data['link'], "plugins");
        if (!$check && !$checktwo)
            $display = true;
    }

    $sql3 = sprintf("SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link=%s",
                    quote_smart($data['id']));
    $result3 = $db->sql_query($sql3);
    while ($data3 = $db->sql_fetchrow($result3)) {
        $submenu = true;
        $sm->initItem($item);
        $sm->setItemText($item, ucwords($data3['name']));
        $sm->setItemLink($item, $data3['link'] . $config['add_link']);
        $item['new_window'] = isset($data3['new_window']) ? $data3['new_window'] : 0;
        $sm->addMenuItem($sub, $item);
    }

    if ($data['active'] && $display) {
        $sm->initItem($item);
        $sm->setItemText($item, $data['name']);
        $sm->setItemLink($item, $data['link'] . $config['add_link']);
        $item['new_window'] = isset($data['new_window']) ? $data['new_window'] : 0;
        if ($submenu)
            $sm->setItemSubmenu($item, $sub);
        $sm->addMenuItem($menu, $item);
    } elseif ($_SESSION['priv'] == "1") {
        $sm->initItem($item);
        $sm->setItemText($item, $data['name']);
        $sm->setItemLink($item, $data['link'] . $config['add_link']);
        $item['new_window'] = isset($data['new_window']) ? $data['new_window'] : 0;
        if ($submenu)
            $sm->setItemSubmenu($item, $sub);
        $sm->addMenuItem($menu, $item);
    }
    $sub = '';
}
if ($_SESSION['priv'] == "1") {
    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_TEXT);
    $sm->setItemLink($item, "admin.php");
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);
}
?>
