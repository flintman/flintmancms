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
$sm->initMenu($config_sub);

$sm->initItem($item);
$sm->setItemText($item, ADMIN_MENU_USERS_CONFIGURE);
$sm->setItemLink($item, "admin.php?n=user", 0);
$item['new_window'] = 0;
$sm->addMenuItem($config_sub, $item);

$sm->initItem($item);
$sm->setItemText($item, ADMIN_MENU_GROUPS_CONFIGURE);
$sm->setItemLink($item, "admin.php?n=groups", 0);
$item['new_window'] = 0;
$sm->addMenuItem($config_sub, $item);

$sm->initItem($item);
$sm->setItemText($item, ADMIN_MENU_EMAIL_CONFIGURE);
$sm->setItemLink($item, "admin.php?n=email", 0);
$item['new_window'] = 0;
$sm->addMenuItem($config_sub, $item);

$sm->initItem($item);
$sm->setItemText($item, ADMIN_MENU_LINKS);
$sm->setItemLink($item, "admin.php?n=links", 0);
$item['new_window'] = 0;
$sm->addMenuItem($config_sub, $item);

$sm->initItem($item);
$sm->setItemText($item, ADMIN_MENU_EXIT_ADMIN);
$sm->setItemLink($item, "index.php", 0);
$item['new_window'] = 0;
$sm->addMenuItem($menu, $item);

if ($_SESSION['priv'] == "1") {
    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_MENU_ADMIN_HOME);
    $sm->setItemLink($item, "admin.php", 0);
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);

    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_MENU_CONFIGURE);
    $sm->setItemLink($item, "admin.php?n=config", 0);
    $item['new_window'] = 0;
    $sm->setItemSubmenu($item, $config_sub);
    $sm->addMenuItem($menu, $item);

    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_MENU_LOGS);
    $sm->setItemLink($item, "admin.php?n=logs", 0);
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);

    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_MENU_PAGES_ADMIN);
    $sm->setItemLink($item, "admin.php?n=page", 0);
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);

    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_MENU_PLUGINS_ADMIN);
    $sm->setItemLink($item, "admin.php?n=plugin", 0);
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);

    $sm->initItem($item);
    $sm->setItemText($item, ADMIN_MENU_HELP);
    $sm->setItemLink($item, "admin.php?n=help", 0);
    $item['new_window'] = 0;
    $sm->addMenuItem($menu, $item);
}
?>
