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

// Load Parsedown library for Markdown parsing
require_once(INCLUDES_PATH . 'Parsedown.php');

// Initialize Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true); // Prevent XSS in markdown

// Find all Markdown files in help directory
$helpContent = '';
$markdownFiles = glob("admin/help/*.md");

// Sort files naturally (01-file.md, 02-file.md, etc.)
natsort($markdownFiles);

// Process each Markdown file
foreach ($markdownFiles as $filename) {
    if (file_exists($filename)) {
        $markdown = file_get_contents($filename);
        $html = $parsedown->text($markdown);

        // Add section wrapper for styling
        $helpContent .= '<div class="help-section">' . $html . '</div>';
        $helpContent .= '<hr class="help-divider">';
    }
}

// Remove last divider
$helpContent = rtrim($helpContent, '<hr class="help-divider">');

// Fallback to old .txt files if no Markdown files found
if (empty($helpContent)) {
    $helpContent = '<div class="help-notice">⚠️ Help files are being updated. Please check back soon.</div>';

    // Try loading old txt files as fallback
    foreach (glob("admin/help/*.txt") as $filename) {
        $contents = file($filename);
        $content = implode($contents);
        $helpContent .= '<div class="help-legacy">' . nl2br(htmlspecialchars($content)) . '</div>';
    }
}

$smarty->assign(
    array(
        'head' => ADMIN_HELP_TEXT,
        'content' => $helpContent,
    )
);

//
// Start output of page
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . '/' . $config['template'] . '/admin/help.htm');

?>
