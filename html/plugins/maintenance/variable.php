<?php
/* * *************************************************************************
 *  FlintmanCMS Maintenance Tracker Plugin - Variable Definitions
 *
 *  PURPOSE:
 *  Maintenance tracking system for primary and secondary equipment units
 *  with customizable questions, maintenance records, and photo uploads.
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access to this file
if (!defined('IN_CMS')) {
    die("ERROR - Direct access not permitted");
}

/* ========================================================================
 * DATABASE TABLES
 * ======================================================================== */
$plugin_db_tables = array(
    "maintenance_equipment",      // Primary and secondary equipment units
    "maintenance_questions",      // Dynamic questions for equipment
    "maintenance_answers",        // Answers to questions for each equipment
    "maintenance_records",        // Maintenance service records
    "maintenance_config"          // Plugin configuration settings
);

/* ========================================================================
 * PLUGIN METADATA
 * ======================================================================== */
$plugin_name = "Maintenance Tracker";

$plugin_description = "Comprehensive maintenance tracking system for primary and secondary equipment. "
                    . "Track maintenance records, service history, costs, and upload photos. "
                    . "Customizable equipment fields and dynamic question system.";

$plugin_version = "1.0.0";

$plugin_folder = "maintenance";

$plugin_main_file = "maintenance.php";

/* ========================================================================
 * DEFAULT CONFIGURATION
 * ======================================================================== */
$plugin_default_primary_label = "Primary Unit";
$plugin_default_secondary_label = "Secondary Unit";
$plugin_columns_to_show = 3;

?>
