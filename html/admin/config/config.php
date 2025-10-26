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
    $title = '<input name="title" type="text" value="'
            . $config['site_name'] . '" maxlength="255" >';


// Pulls the templates
    $dir = TEMPLATES_PATH;
    $dh = opendir($dir);
    while (false != ($filename = readdir($dh))) {
        $files[] = $filename;
    }

    sort($files);
    array_shift($files);
    array_shift($files);

    $template_type = '<select name="template">';

    foreach ($files as $key => $value) {
        if ($config['template'] == $value)
            $template_type .= "<option value=\"$value\" selected>$value</option>";
        else
            $template_type .= "<option value=\"$value\">$value</option>";
    }

    $template_type .= '</select>';
    $files = null;

    // Pulls the language
    $dir = LANGUAGES_PATH;
    $dh = opendir($dir);
    while (false != ($filename = readdir($dh))) {
        $files[] = $filename;
    }

    sort($files);
    array_shift($files);
    array_shift($files);

    $language_type = '<select name="language">';

    foreach ($files as $key => $value) {
        if ($config['language'] == $value)
            $language_type .= "<option value=\"$value\" selected>$value</option>";
        else
            $language_type .= "<option value=\"$value\">$value</option>";
    }

    $language_type .= '</select>';

    //Setups groups
    $groups = '<select name="groups">';
    $sql = "SELECT * FROM " . DB_PREFIX . "_groups";
    $result = $db->sql_query($sql);
    While ($data = $db->sql_fetchrow($result)) {
        $priv_id = $data['id'];
        $priv_name = $data['name'];
        if ($config['default_priv'] == $priv_id)
            $groups .= "<option value=\"$priv_id\" selected>$priv_name</option>";
        else
            $groups .= "<option value=\"$priv_id\">$priv_name</option>";
    }
    $groups .= '</select>';

    //Gets all the different pages and plugins
    $frt_page = '<select name="frt_page">';
    $sql = "SELECT * FROM " . DB_PREFIX . "_pages WHERE active='1'";
    $result = $db->sql_query($sql);
    While ($data = $db->sql_fetchrow($result)) {
        $link = 'index.php?n=page&page_id='.$data['id'];
        $name = ucfirst($data['title']);
        if ($config['frt_page'] == $link)
            $frt_page .= "<option value=\"$link\" selected>$name</option>";
        else
            $frt_page .= "<option value=\"$link\">$name</option>";
    }
    $sql = "SELECT * FROM " . DB_PREFIX . "_plugins WHERE active='1'";
    $result = $db->sql_query($sql);
    While ($data = $db->sql_fetchrow($result)) {
        $link = 'index.php?n=plugins&p='.$data['name'];
        $name = ucfirst($data['name']);
        if ($config['frt_page'] == $link)
            $frt_page .= "<option value=\"$link\" selected>$name</option>";
        else
            $frt_page .= "<option value=\"$link\">$name</option>";
    }
    $frt_page .= '</select>';


    if ($config['debug'] == '0')
        $debug_mode = '<input type="checkbox" name="debug" value="1">';
    else
        $debug_mode = '<input type="checkbox" name="debug" value="1" checked>';


    if ($config['maintain'] == '0')
        $maintain_mode = '<input type="checkbox" name="maintain" value="1">';
    else
        $maintain_mode = '<input type="checkbox" name="maintain" value="1" checked>';

    if ($config['allow_login'] == '0')
        $allow_login = '<input type="checkbox" name="allow_login" value="1">';
    else
        $allow_login = '<input type="checkbox" name="allow_login" value="1" checked>';

    $add_to_link ='<input name="add_to_link" type="text" value="'
            . $config['add_link'] . '" maxlength="255" >';;

    $disclaimer = '<textarea name="disclaimer" cols="30" rows="2" >' . $config['disclamer'] . '</textarea>';

    $meta_tags = '<textarea name="meta" cols="30" rows="2" >' . $config['meta_tags'] . '</textarea>';

    $maintain_message = '<textarea name="maintain_message" cols="30" rows="2" >' . $config['maintain_message'] . '</textarea>';

    $buttons = '<input type="submit" name="submit" value="' . SUBMIT_TEXT . '" class="button">
        <input type="reset" name="' . RESET_TEXT . '" value="Reset" class="button">';


// put the variables into the template
    $smarty->assign(
            array(
                'title' => $title,
                'template_type' => $template_type,
                'language_type' => $language_type,
                'debug_mode' => $debug_mode,
                'maintain_mode' => $maintain_mode,
                'disclaimer' => $disclaimer,
                'meta_tag' => $meta_tags,
                'buttons' => $buttons,
                'allow_login' => $allow_login,
                'groups' => $groups,
                'frt_page' => $frt_page,
                'maintain_message' => $maintain_message,
                'add_to_link' => $add_to_link
            )
    );

    // put the variables into the template
    $smarty->assign(
            array(
                'head' => ADMIN_CONFIG_HEADER,
                'admin_title_text' => ADMIN_TITLE_TEXT,
                'admin_template_text' => ADMIN_TEMPLATE_TEXT,
                'admin_language_text' => ADMIN_LANGUAGE_TEXT,
                'admin_debug_text' => ADMIN_DEBUG_TEXT,
                'admin_maintance_text' => ADMIN_MAINTANCE_TEXT,
                'admin_allow_log_text' => ADMIN_ALLOW_LOG_TEXT,
                'admin_default_text' => ADMIN_DEAULT_TEXT,
                'admin_disclaimer_text' => ADMIN_DISCLAIMER_TEXT,
                'admin_meta_text' => ADMIN_META_TEXT,
                'admin_main_message_text' => ADMIN_MAIN_MESSAGE_TEXT,
                'admin_frt_page_text' => ADMIN_FRT_PAGE_TEXT,
                'admin_add_link_text' => ADMIN_ADD_LINK_TEXT
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
    $admin_email = isset($_POST['admin_email']) ? scrub_input($_POST['admin_email']) : '';
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
    update_config($email, "email_errors");
    update_config($admin_email, "email_admin");
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