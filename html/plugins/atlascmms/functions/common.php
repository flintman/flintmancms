<?php
/* =========================================================================
 * FlintmanCMS AtlasCMMS Plugin — Common Helpers
 * ========================================================================= */

if (!defined('IN_CMS')) { die("ERROR - Hacking attempt"); }

/**
 * Top navigation bar for all AtlasCMMS plugin pages.
 */
function atlascmms_nav($current_action) {
    $base       = '?n=plugins&p=atlascmms';
    $wo_active  = in_array($current_action, ['workorders', 'workorder_detail']) ? ' nav-tab-active' : '';
    $ast_active = in_array($current_action, ['assets', 'asset_workorders', 'asset_detail']) ? ' nav-tab-active' : '';
    $logout_url = htmlspecialchars($base . '&action=logout', ENT_QUOTES, 'UTF-8');
    $back_link  = in_array($current_action, ['workorder_detail', 'asset_detail'])
        ? '<a href="javascript:history.back()" class="nav-tab nav-tab-back">&#8592; Back</a>'
        : '';

    return '<div class="atlascmms-nav">'
        . '<a href="' . $base . '&action=workorders" class="nav-tab' . $wo_active . '">Work Orders</a>'
        . '<a href="' . $base . '&action=assets"     class="nav-tab' . $ast_active . '">Assets</a>'
        . '<span class="nav-tab-spacer"></span>'
        . $back_link
        . '<a href="' . $logout_url . '" class="nav-tab nav-tab-logout">Sign Out</a>'
        . '</div>';
}

/**
 * Safe string output — handles arrays by extracting a display-friendly value.
 */
function atlascmms_str($val) {
    if (is_array($val)) {
        return htmlspecialchars(
            (string)($val['name'] ?? $val['username'] ?? $val['title'] ?? implode(', ', array_filter($val, 'is_string'))),
            ENT_QUOTES, 'UTF-8'
        );
    }
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
