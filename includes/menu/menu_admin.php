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


SmartyMenu::initMenu($config_sub);

SmartyMenu::initItem($item);
SmartyMenu::setItemText($item, "Users Configure");
SmartyMenu::setItemLink($item, "admin.php?n=user", 0);
SmartyMenu::addMenuItem($config_sub, $item);

SmartyMenu::initItem($item);
SmartyMenu::setItemText($item, "Groups Configure");
SmartyMenu::setItemLink($item, "admin.php?n=groups", 0);
SmartyMenu::addMenuItem($config_sub, $item);


SmartyMenu::initItem($item);
SmartyMenu::setItemText($item, "Email Configure");
SmartyMenu::setItemLink($item, "admin.php?n=email", 0);
SmartyMenu::addMenuItem($config_sub, $item);


SmartyMenu::initItem($item);
SmartyMenu::setItemText($item, "Menu Links");
SmartyMenu::setItemLink($item, "admin.php?n=links", 0);
SmartyMenu::addMenuItem($config_sub, $item);



//Gets all pages names and puts in menu
SmartyMenu::initItem($item);
SmartyMenu::setItemText($item, "Exit Admin");
SmartyMenu::setItemLink($item, "index.php", 0);
SmartyMenu::addMenuItem($menu, $item);

if ($_SESSION['priv'] == "1") {
    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Admin Home");
    SmartyMenu::setItemLink($item, "admin.php", 0);
    SmartyMenu::addMenuItem($menu, $item);

    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Configure");
    SmartyMenu::setItemLink($item, "admin.php?n=config", 0);
    SmartyMenu::setItemSubmenu($item, $config_sub);
    SmartyMenu::addMenuItem($menu, $item);


    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Logs");
    SmartyMenu::setItemLink($item, "admin.php?n=logs", 0);
    SmartyMenu::addMenuItem($menu, $item);

    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Pages Admin");
    SmartyMenu::setItemLink($item, "admin.php?n=page", 0);
    SmartyMenu::addMenuItem($menu, $item);

    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Plugins Admin");
    SmartyMenu::setItemLink($item, "admin.php?n=plugin", 0);
    SmartyMenu::addMenuItem($menu, $item);

    SmartyMenu::initItem($item);
    SmartyMenu::setItemText($item, "Help");
    SmartyMenu::setItemLink($item, "admin.php?n=help", 0);
    SmartyMenu::addMenuItem($menu, $item);
}
?>
