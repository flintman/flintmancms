
<?php
// Basic admin menu array structure for Smarty foreach in footer.htm
$menu = array();

$config_sub = array(
    array('text' => ADMIN_MENU_USERS_CONFIGURE, 'link' => 'admin.php?n=user', 'new_window' => 0),
    array('text' => ADMIN_MENU_GROUPS_CONFIGURE, 'link' => 'admin.php?n=groups', 'new_window' => 0),
    array('text' => ADMIN_MENU_EMAIL_CONFIGURE, 'link' => 'admin.php?n=email', 'new_window' => 0),
    array('text' => ADMIN_MENU_LINKS, 'link' => 'admin.php?n=links', 'new_window' => 0)
);

$menu[] = array('text' => ADMIN_MENU_EXIT_ADMIN, 'link' => 'index.php', 'new_window' => 0);

if ($_SESSION['priv'] == "1") {
    $menu[] = array('text' => ADMIN_MENU_ADMIN_HOME, 'link' => 'admin.php', 'new_window' => 0);
    $menu[] = array(
        'text' => ADMIN_MENU_CONFIGURE,
        'link' => 'admin.php?n=config',
        'new_window' => 0,
        'submenu' => $config_sub
    );
    $menu[] = array('text' => ADMIN_MENU_LOGS, 'link' => 'admin.php?n=logs', 'new_window' => 0);
    $menu[] = array('text' => ADMIN_MENU_PAGES_ADMIN, 'link' => 'admin.php?n=page', 'new_window' => 0);
    $menu[] = array('text' => ADMIN_MENU_PLUGINS_ADMIN, 'link' => 'admin.php?n=plugin', 'new_window' => 0);
    $menu[] = array('text' => ADMIN_MENU_HELP, 'link' => 'admin.php?n=help', 'new_window' => 0);
}
?>
