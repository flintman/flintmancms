<?php
/* * *************************************************************************
 *  FlintmanCMS AtlasCMMS Plugin - Plugin Metadata
 *
 *  PURPOSE:
 *  This file defines the plugin's metadata and database requirements.
 *  It is read during plugin activation to set up the plugin properly.
 *
 *  FUNCTIONALITY:
 *  - Integrates with Atlas CMMS API
 *  - Stores API configuration (API key, API URL, Minio URL)
 *  - Displays assets and work orders from Atlas CMMS
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access to this file
if (!defined('IN_CMS')) {
    die("ERROR - Direct access not permitted");
}

/* ========================================================================
 * DATABASE TABLES
 * ========================================================================
 * List of database tables this plugin creates/uses.
 * The CMS will automatically add the DB_PREFIX to these names.
 * ======================================================================== */
$plugin_db_tables = array(
    "atlascmms_config",       // Stores API configuration
    "atlascmms_cache",        // Cache API responses
);

/* ========================================================================
 * PLUGIN METADATA
 * ========================================================================
 * Information about this plugin displayed in admin panel.
 * ======================================================================== */
$plugin_name = "AtlasCMMS Integration";
$plugin_description = "Integrates with Atlas CMMS API to display and manage assets and work orders";
$plugin_version = "1.0.0";
$plugin_folder = "atlascmms";
$plugin_author = "FlintmanCMS";


?>
