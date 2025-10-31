
<?php
// Basic menu array structure for Smarty foreach in footer.htm
$menu = array();

$menu[] = array(
    'text' => HOME_TEXT,
    'link' => 'index.php',
    'new_window' => 0
);

if ($_SESSION['user_logged_in']) {
    $menu[] = array(
        'text' => PROFILE_TEXT,
        'link' => 'index.php?n=profile' . $config['add_link'],
        'new_window' => 0
    );
}

$sql = "SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link='0' ORDER BY `link_order` ASC";
$result = $db->sql_query($sql);
while ($data = $db->sql_fetchrow($result)) {
    $submenu = array();
    $sql3 = sprintf("SELECT * FROM " . DB_PREFIX . "_links WHERE sub_link=%s",
        quote_smart($data['id']));
    $result3 = $db->sql_query($sql3);
    while ($data3 = $db->sql_fetchrow($result3)) {
        $submenu[] = array(
            'text' => ucwords($data3['name']),
            'link' => $data3['link'] . $config['add_link'],
            'new_window' => isset($data3['new_window']) ? $data3['new_window'] : 0
        );
    }
    $add = false;
    if ($data['active'] && check_menu($_SESSION['priv'], $data['id'])) {
        $add = true;
    } elseif ($_SESSION['priv'] == "1") {
        $add = true;
    }
    if ($add) {
        $item = array(
            'text' => $data['name'],
            'link' => $data['link'] . $config['add_link'],
            'new_window' => isset($data['new_window']) ? $data['new_window'] : 0
        );
        if (count($submenu) > 0) {
            $item['submenu'] = $submenu;
        }
        $menu[] = $item;
    }
}
if ($_SESSION['priv'] == "1") {
    $menu[] = array(
        'text' => ADMIN_TEXT,
        'link' => 'admin.php',
        'new_window' => 0
    );
}
?>
