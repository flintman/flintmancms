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

$file = $_SERVER["SCRIPT_NAME"];
$break = Explode('/', $file);
$pfile = $break[count($break) - 1];

if ($pfile == "index.php")
    require_once(INCLUDES_PATH . 'menu/menu.php');
else
    require_once(INCLUDES_PATH . 'menu/menu_admin.php');
//Setup the Scripts
$scripts = '';

$scripts .='<script type="text/javascript" src="includes/tiny_mce/tiny_mce.js"></script>';
$scripts .='<script type="text/javascript" src="includes/ajax/register_validate.js"></script>';
$scripts .= '<script type="text/javascript">
	tinyMCE.init({
		mode : "textareas",
		theme : "advanced",
                plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,autosave",

		// Theme options
		theme_advanced_buttons1 : "justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview",
		theme_advanced_buttons3 : "charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,search,replace,|,forecolor,backcolor",
		theme_advanced_buttons4 : "bold,italic,underline,strikethrough,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak,restoredraft",


	});
	function toggleEditor(id) {

	if (!tinyMCE.get(id))
		tinyMCE.execCommand(\'mceAddControl\', false, id);

	else
		tinyMCE.execCommand(\'mceRemoveControl\', false, id);
		}
</script>';

$scripts .= $scriptAdd;
$tooltips = '<script type="text/javascript" src="includes/scripts/tooltips.js"></script>';

$login_data = isset($login_data) ? $login_data : '';
$register_link = isset($register_link) ? $register_link : '';
$menu = isset($menu) ? $menu : [];
$smarty->assign(
        array(
            'javascript' => $scripts,
            'title_text' => $config['site_name'],
            'meta_tags' => strip_tags($config['meta_tags']),
            'tooltips' => $tooltips,
            'login' => $login_data,
            'register' => $register_link,
            'menu' => $menu
        )
);

// display any errors if they exist
if (isset($errorMsg)) {
    if ($config['email_errors'] == 1 && isset($errorMsg)) {
        email($config['email_admin'], $errorMsg);
    }
    if (isset($errorMsg)) {
        $sql = sprintf("INSERT INTO flintmancms_logs (id, error, timestamp) VALUES('0',%s,'%s')",
                        quote_smart($errorMsg), time());
        $db->sql_query($sql);
    }

    $smarty->assign('errorMsg', $errorMsg);

    $smarty->display('../templates/' . $config['template'] . '/header.htm');
    $smarty->display('../templates/' . $config['template'] . '/error.htm');
    include('footer.php');
    exit;
}

$smarty->display('../templates/' . $config['template'] . '/header.htm');
?>
