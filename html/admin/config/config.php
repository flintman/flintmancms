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

if (!defined('IN_ADMIN_CMS')) {
    die("ERROR - Hacking attempt");
}

// page authentication
array_push($page_lvl, "Admin");
include(INCLUDES_PATH . 'authentication.php');

// show the form
if (!isset($_POST['submit'])) {
    // get initial values from database
    $title = $config['site_name'];

// Pulls the templates
    $dir = TEMPLATES_PATH;
    $dh = opendir($dir);
    while (false != ($filename = readdir($dh))) {
        $templates[] = $filename;
    }

    sort($templates);
    array_shift($templates);
    array_shift($templates);

    // Pulls the language
    $dir = LANGUAGES_PATH;
    $dh = opendir($dir);
    while (false != ($filename = readdir($dh))) {
        $languages[] = $filename;
    }

    sort($languages);
    array_shift($languages);
    array_shift($languages);

    // Setups groups as array for template
    $groups_options = array();
    $sql = "SELECT * FROM " . DB_PREFIX . "_groups";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {
        $groups_options[] = array('id' => $data['id'], 'name' => $data['name']);
    }

    // Gets all the different pages and plugins as array for template
    $frt_page_options = array();
    $sql = "SELECT * FROM " . DB_PREFIX . "_pages WHERE active='1'";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {
        $link = 'index.php?n=page&page_id=' . $data['id'];
        $name = ucfirst($data['title']);
        $frt_page_options[] = array('value' => $link, 'label' => $name);
    }
    $sql = "SELECT * FROM " . DB_PREFIX . "_plugins WHERE active='1'";
    $result = $db->sql_query($sql);
    while ($data = $db->sql_fetchrow($result)) {
        $link = 'index.php?n=plugins&p=' . $data['name'];
        $name = ucfirst($data['name']);
        $frt_page_options[] = array('value' => $link, 'label' => $name);
    }

    // Assign raw values for checkboxes and fields, let Smarty template render HTML
    $debug_checked = ($config['debug'] == '1');
    $maintain_checked = ($config['maintain'] == '1');
    $allow_login_checked = ($config['allow_login'] == '1');
    $add_to_link_value = $config['add_link'];
    $disclaimer_value = $config['disclamer'];
    $meta_tags_value = $config['meta_tags'];
    $maintain_message_value = $config['maintain_message'];

    // Button labels
    $submit_text = SUBMIT_TEXT;
    $reset_text = RESET_TEXT;

    // put the variables into the template
    $smarty->assign(
        array(
            'title' => $title,
            'debug_checked' => $debug_checked,
            'maintain_checked' => $maintain_checked,
            'allow_login_checked' => $allow_login_checked,
            'add_to_link_value' => $add_to_link_value,
            'disclaimer_value' => $disclaimer_value,
            'meta_tags_value' => $meta_tags_value,
            'maintain_message_value' => $maintain_message_value,
            'submit_text' => $submit_text,
            'reset_text' => $reset_text,
            'groups_options' => $groups_options,
            'selected_group' => $config['default_priv'],
            'frt_page_options' => $frt_page_options,
            'selected_frt_page' => $config['frt_page'],
            'template_options' => $templates,
            "selected_template" => $config['template'],
            'language_options' => $languages,
            "selected_language" => $config['language']
        )
    );
}
else {
    // form submission, update the database

    if (isset($_POST['debug']))
        $debug = 1;
    else
        $debug = 0;

    if (isset($_POST['email']))
        $email = 1;
    else
        $email = 0;

    if (isset($_POST['maintain']))
        $maintain = 1;
    else
        $maintain = 0;

    if (isset($_POST['allow_login']))
        $allow_login = 1;
    else
        $allow_login = 0;

    $title = scrub_input($_POST['title']);
    $temp = scrub_input($_POST['template']);
    $lang = scrub_input($_POST['language']);
    $dis = $_POST['disclaimer'];
    $frt_page = $_POST['frt_page'];
    $meta = scrub_input($_POST['meta']);
    $groups = scrub_input($_POST['groups']);
    $maintain_message = $_POST['maintain_message'];
    $add_to_link = $_POST['add_to_link'];

    update_config($add_to_link, "add_link");
    update_config($title, "site_name");
    update_config($debug, "debug");
    update_config($temp, "template");
    update_config($lang, "language");
    update_config($dis, "disclamer");
    update_config($maintain, "maintain");
    update_config($meta, "meta_tags");
    update_config($allow_login, "allow_login");
    update_config($groups, "default_priv");
    update_config($maintain_message, "maintain_message");
    update_config($frt_page, "frt_page");
    if (!isset($errorMsg))
        header("Location: admin.php?n=config");
}

//
// Start output of page
//
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/config.htm');
?>