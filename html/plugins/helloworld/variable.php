<?php
/* * *************************************************************************
 *  FlintmanCMS Hello World Plugin - Variable Definitions
 *
 *  PURPOSE:
 *  This file defines the plugin's metadata and database requirements.
 *  It is read during plugin activation to set up the plugin properly.
 *
 *  WHEN IS THIS FILE LOADED?
 *  - During plugin activation (to check/create tables)
 *  - During plugin deactivation (to optionally drop tables)
 *  - When displaying plugin information in the admin panel
 *
 *  IMPORTANT VARIABLES:
 *  - $plugin_db_tables : Array of table names (without prefix)
 *  - $plugin_name      : Display name shown in admin
 *  - $plugin_description : Brief description of plugin functionality
 *  - $plugin_version   : Version number (for updates/compatibility)
 *  - $plugin_folder    : Must match the plugin directory name
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access to this file
// If accessed directly (not through CMS), die with error message
if (!defined('IN_CMS')) {
    die("ERROR - Direct access not permitted");
}

/* ========================================================================
 * DATABASE TABLES
 * ========================================================================
 * List all database tables that this plugin creates/uses.
 *
 * NAMING CONVENTION:
 * - Table names should be: pluginfolder_tablename
 * - The CMS will automatically add the DB_PREFIX
 * - Example: "helloworld_messages" becomes "flintmancms_helloworld_messages"
 *
 * IMPORTANT:
 * - All tables listed here MUST be created in sql/install.sql
 * - During activation, the system checks if these tables exist
 * - During deactivation with "delete tables", these are dropped
 * ======================================================================== */
$plugin_db_tables = array(
    "helloworld_messages",    // Stores hello world messages
    "helloworld_settings"     // Stores plugin settings
);

/* ========================================================================
 * PLUGIN METADATA
 * ======================================================================== */

// Plugin name displayed in admin panel and menus
$plugin_name = "Hello World";

// Short description explaining what the plugin does
// This appears in the plugin management interface
$plugin_description = "A comprehensive example plugin demonstrating CRUD operations, "
                    . "database integration, admin interface, and frontend display. "
                    . "Use this as a template for building your own plugins.";

// Version number - used for tracking updates and compatibility
// Format: major.minor.patch (e.g., 1.0.0, 1.2.3, 2.0.0)
$plugin_version = "1.0.0";

// Plugin folder name - MUST match the actual directory name
// This is used for routing: index.php?n=plugins&p=helloworld
$plugin_folder = "helloworld";

/* ========================================================================
 * OPTIONAL: CUSTOM PLUGIN SETTINGS
 * ========================================================================
 * You can define additional configuration variables here that are
 * specific to your plugin. These can be accessed in your plugin files.
 * ======================================================================== */

// Example: Default settings for the plugin
$plugin_default_greeting = "Hello, World!";
$plugin_max_messages = 100;
$plugin_allow_anonymous = true;

/* ========================================================================
 * NOTES FOR DEVELOPERS
 * ========================================================================
 *
 * 1. CHANGING TABLE NAMES:
 *    If you add or rename tables, update both:
 *    - This $plugin_db_tables array
 *    - The sql/install.sql file
 *
 * 2. VERSIONING:
 *    Update $plugin_version when you make changes:
 *    - Patch (x.x.1): Bug fixes, minor changes
 *    - Minor (x.1.x): New features, backwards compatible
 *    - Major (1.x.x): Breaking changes, major rewrites
 *
 * 3. PLUGIN FOLDER:
 *    The $plugin_folder value determines:
 *    - URL parameter: ?p=helloworld
 *    - File routing: plugins/helloworld/helloworld.php
 *    - Admin routing: admin/admin.php
 *
 * ======================================================================== */
?>
