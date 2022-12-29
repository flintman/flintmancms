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

// Now we create the top-level menu
SmartyMenu::initMenu($menu);


//Gets all pages names and puts in menu
SmartyMenu::initItem($item);
SmartyMenu::setItemText($item, "Home");
SmartyMenu::setItemLink($item, "index.php", 0);
SmartyMenu::addMenuItem($menu, $item);

if ($_SESSION['user_logged_in']) {
    //Gets all pages names and puts in menu
    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Profile");
    SmartyMenu::setItemLink($item, "index.php?n=profile" . $config['add_link'], 0);
    SmartyMenu::addMenuItem($menu, $item);
}

$sql = "SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link='0' ORDER BY `link_order` ASC";
$result = $db->sql_query($sql);

while ($data = $db->sql_fetchrow($result)) {
    $display = false;
    $submenu = false;
    SmartyMenu::initMenu($sub);
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
        SmartyMenu::initItem($item);
        SmartyMenu::setItemText($item, ucwords($data3['name']));
        SmartyMenu::setItemLink($item, $data3['link'] . $config['add_link'], 0);
        SmartyMenu::addMenuItem($sub, $item);
    }

    if ($data['active'] && $display) {
        SmartyMenu::initItem($item);
        SmartyMenu::setItemText($item, $data['name']);
        SmartyMenu::setItemLink($item, $data['link'] . $config['add_link'], $data['new_window']);
        if ($submenu)
            SmartyMenu::setItemSubmenu($item, $sub);
        SmartyMenu::addMenuItem($menu, $item);
    } elseif ($_SESSION['priv'] == "1") {
        SmartyMenu::initItem($item);
        SmartyMenu::setItemText($item, $data['name']);
        SmartyMenu::setItemLink($item, $data['link'] . $config['add_link'], $data['new_window']);
        if ($submenu)
            SmartyMenu::setItemSubmenu($item, $sub);
        SmartyMenu::addMenuItem($menu, $item);
    }
    $sub = '';
}
if ($_SESSION['priv'] == "1") {
    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Admin");
    SmartyMenu::setItemLink($item, "admin.php", 0);
    SmartyMenu::addMenuItem($menu, $item);
}
?>
