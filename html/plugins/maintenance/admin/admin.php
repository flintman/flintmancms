<?php
/* * *************************************************************************
 *  FlintmanCMS Maintenance Tracker Plugin - Admin Interface
 *
 *  PURPOSE:
 *  Administrative interface for managing equipment questions and settings
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access
if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

/* ========================================================================
 * AUTHENTICATION
 * ======================================================================== */
$page_lvl = isset($page_lvl) ? $page_lvl : array();
$p = isset($_GET['p']) ? scrub_input($_GET['p']) : '';

$sql = sprintf("SELECT * FROM flintmancms_plugins WHERE name=%s", quote_smart($p));
$result = $db->sql_query($sql);
$ids = $db->sql_fetchrow($result);

if ($ids) {
    $sql2 = sprintf("SELECT * FROM flintmancms_group_links WHERE type='plugins' AND type_id=%s",
                    quote_smart($ids['id']));
    $result = $db->sql_query($sql2);

    while ($data = $db->sql_fetchrow($result)) {
        $sql3 = sprintf("SELECT * FROM flintmancms_groups WHERE id=%s",
                        quote_smart($data['group_id']));
        $result2 = $db->sql_query($sql3);
        $data2 = $db->sql_fetchrow($result2);
        if ($data2) {
            array_push($page_lvl, $data2['name']);
        }
    }
}

include(INCLUDES_PATH . 'authentication.php');

/* ========================================================================
 * LOAD PLUGIN STYLES
 * ======================================================================== */
$scriptAdd .= '<link rel="stylesheet" href="plugins/maintenance/css/maintenance.css?v=' . time() . '" type="text/css" />';

/* ========================================================================
 * GET CONFIGURATION
 * ======================================================================== */
$sql = "SELECT * FROM flintmancms_maintenance_config";
$result = $db->sql_query($sql);
$config_data = array();
while ($row = $db->sql_fetchrow($result)) {
    $config_data[$row['config_name']] = $row['config_value'];
}

$primary_label = $config_data['primary_unit'] ?? 'Primary Unit';
$secondary_label = $config_data['secondary_unit'] ?? 'Secondary Unit';
$columns_to_show = intval($config_data['columns_to_show'] ?? 3);

/* ========================================================================
 * HANDLE ACTIONS
 * ======================================================================== */
$action = isset($_GET['admin_action']) ? scrub_input($_GET['admin_action']) : 'config';
$message = '';

// ACTION: Update configuration
if ($action === 'config' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $new_primary = scrub_input($_POST['primary_unit']);
    $new_secondary = scrub_input($_POST['secondary_unit']);
    $new_columns = intval($_POST['columns_to_show']);

    $sql = sprintf("UPDATE flintmancms_maintenance_config SET config_value=%s WHERE config_name='primary_unit'",
                  quote_smart($new_primary));
    $db->sql_query($sql);

    $sql = sprintf("UPDATE flintmancms_maintenance_config SET config_value=%s WHERE config_name='secondary_unit'",
                  quote_smart($new_secondary));
    $db->sql_query($sql);

    $sql = sprintf("UPDATE flintmancms_maintenance_config SET config_value=%s WHERE config_name='columns_to_show'",
                  quote_smart($new_columns));
    $db->sql_query($sql);

    $message = 'Configuration updated successfully!';
    $primary_label = $new_primary;
    $secondary_label = $new_secondary;
    $columns_to_show = $new_columns;
}

// ACTION: Manage questions for primary or secondary units
elseif ($action === 'questions') {
    $unit_type = isset($_GET['type']) ? scrub_input($_GET['type']) : 'primary';
    $equipment_level = ($unit_type === 'primary') ? 1 : 2;
    $unit_label = ($unit_type === 'primary') ? $primary_label : $secondary_label;

    // Handle add question
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
        $label = scrub_input($_POST['label']);
        $type = scrub_input($_POST['type']);
        $options = ($type === 'multi_choice') ? scrub_input($_POST['options']) : null;
        $position = intval($_POST['position']);

        $sql = sprintf("INSERT INTO flintmancms_maintenance_questions (equipment_level, label, type, options, position) VALUES (%d, %s, %s, %s, %d)",
                      $equipment_level,
                      quote_smart($label),
                      quote_smart($type),
                      $options ? quote_smart($options) : 'NULL',
                      $position);
        $db->sql_query($sql);
        $message = 'Question added successfully!';
    }

    // Handle edit question
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_question'])) {
        $id = intval($_POST['id']);
        $label = scrub_input($_POST['label']);
        $type = scrub_input($_POST['type']);
        $options = ($type === 'multi_choice') ? scrub_input($_POST['options']) : null;
        $position = intval($_POST['position']);

        $sql = sprintf("UPDATE flintmancms_maintenance_questions SET label=%s, type=%s, options=%s, position=%d WHERE id=%d",
                      quote_smart($label),
                      quote_smart($type),
                      $options ? quote_smart($options) : 'NULL',
                      $position,
                      $id);
        $db->sql_query($sql);
        $message = 'Question updated successfully!';
    }

    // Handle delete question
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_question'])) {
        $id = intval($_POST['id']);
        $sql = sprintf("DELETE FROM flintmancms_maintenance_questions WHERE id=%d", $id);
        $db->sql_query($sql);
        $message = 'Question deleted successfully!';
    }

    // Handle move question up/down
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['move_up']) || isset($_POST['move_down']))) {
        $id = intval($_POST['id']);
        $direction = isset($_POST['move_up']) ? -1 : 1;

        // Get current position
        $sql = sprintf("SELECT position FROM flintmancms_maintenance_questions WHERE id=%d", $id);
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $current = $row['position'];
        $new = $current + $direction;

        // Find question at new position
        $sql = sprintf("SELECT id FROM flintmancms_maintenance_questions WHERE position=%d AND equipment_level=%d", $new, $equipment_level);
        $result = $db->sql_query($sql);
        $swap_row = $db->sql_fetchrow($result);

        if ($swap_row) {
            $swap_id = $swap_row['id'];
            // Swap positions
            $sql = sprintf("UPDATE flintmancms_maintenance_questions SET position=%d WHERE id=%d", $new, $id);
            $db->sql_query($sql);
            $sql = sprintf("UPDATE flintmancms_maintenance_questions SET position=%d WHERE id=%d", $current, $swap_id);
            $db->sql_query($sql);
            $message = 'Question position updated!';
        }
    }

    // Fetch questions
    $sql = sprintf("SELECT * FROM flintmancms_maintenance_questions WHERE equipment_level=%d ORDER BY position ASC", $equipment_level);
    $result = $db->sql_query($sql);
    $questions = array();
    while ($row = $db->sql_fetchrow($result)) {
        $questions[] = $row;
    }

    $smarty->assign('questions', $questions);
    $smarty->assign('unit_label', $unit_label);
    $smarty->assign('unit_type', $unit_type);
}

// ACTION: Manage units (primary or secondary)
elseif ($action === 'units') {
    $unit_type = isset($_GET['type']) ? scrub_input($_GET['type']) : 'primary';
    $equipment_level = ($unit_type === 'primary') ? 1 : 2;
    $unit_label = ($unit_type === 'primary') ? $primary_label : $secondary_label;

    // Handle add unit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_unit'])) {
        $unit_id = intval($_POST['unit_id']);

        // Check if unit_id already exists
        $sql = sprintf("SELECT id FROM flintmancms_maintenance_equipment WHERE unit_id=%d AND equipment_level=%d", $unit_id, $equipment_level);
        $result = $db->sql_query($sql);

        if ($db->sql_numrows($result) > 0) {
            $message = 'Error: Unit ID already exists!';
        } else {
            $sql = sprintf("INSERT INTO flintmancms_maintenance_equipment (unit_id, equipment_level, archived) VALUES (%d, %d, 0)",
                          $unit_id, $equipment_level);
            $db->sql_query($sql);
            $equipment_id = $db->sql_nextid();

            // Handle question answers if any
            $sql_questions = sprintf("SELECT id FROM flintmancms_maintenance_questions WHERE equipment_level=%d", $equipment_level);
            $result_q = $db->sql_query($sql_questions);

            while ($question = $db->sql_fetchrow($result_q)) {
                $q_id = $question['id'];
                if (isset($_POST['answer_' . $q_id])) {
                    $answer_value = scrub_input($_POST['answer_' . $q_id]);
                    $sql = sprintf("INSERT INTO flintmancms_maintenance_answers (equipment_id, question_id, value) VALUES (%d, %d, %s)",
                                  $equipment_id, $q_id, quote_smart($answer_value));
                    $db->sql_query($sql);
                }
            }

            $message = 'Unit added successfully!';
        }
    }

    // Handle edit unit
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_unit'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $unit_id = intval($_POST['unit_id']);

        // Update unit_id
        $sql = sprintf("UPDATE flintmancms_maintenance_equipment SET unit_id=%d WHERE id=%d", $unit_id, $equipment_id);
        $db->sql_query($sql);

        // Update answers
        $sql_questions = sprintf("SELECT id FROM flintmancms_maintenance_questions WHERE equipment_level=%d", $equipment_level);
        $result_q = $db->sql_query($sql_questions);

        while ($question = $db->sql_fetchrow($result_q)) {
            $q_id = $question['id'];
            if (isset($_POST['answer_' . $q_id])) {
                $answer_value = scrub_input($_POST['answer_' . $q_id]);

                // Check if answer exists
                $sql = sprintf("SELECT id FROM flintmancms_maintenance_answers WHERE equipment_id=%d AND question_id=%d",
                              $equipment_id, $q_id);
                $result_a = $db->sql_query($sql);

                if ($db->sql_numrows($result_a) > 0) {
                    // Update existing answer
                    $sql = sprintf("UPDATE flintmancms_maintenance_answers SET value=%s WHERE equipment_id=%d AND question_id=%d",
                                  quote_smart($answer_value), $equipment_id, $q_id);
                    $db->sql_query($sql);
                } else {
                    // Insert new answer
                    $sql = sprintf("INSERT INTO flintmancms_maintenance_answers (equipment_id, question_id, value) VALUES (%d, %d, %s)",
                                  $equipment_id, $q_id, quote_smart($answer_value));
                    $db->sql_query($sql);
                }
            }
        }

        $message = 'Unit updated successfully!';
    }

    // Handle archive unit
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_unit'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $sql = sprintf("UPDATE flintmancms_maintenance_equipment SET archived=1 WHERE id=%d", $equipment_id);
        $db->sql_query($sql);
        $message = 'Unit archived successfully!';
    }

    // Handle unarchive unit
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unarchive_unit'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $sql = sprintf("UPDATE flintmancms_maintenance_equipment SET archived=0 WHERE id=%d", $equipment_id);
        $db->sql_query($sql);
        $message = 'Unit unarchived successfully!';
    }

    // Fetch questions for this equipment level
    $sql = sprintf("SELECT * FROM flintmancms_maintenance_questions WHERE equipment_level=%d ORDER BY position ASC", $equipment_level);
    $result = $db->sql_query($sql);
    $questions = array();
    while ($row = $db->sql_fetchrow($result)) {
        // Process multi_choice options into an array
        if ($row['type'] == 'multi_choice' && !empty($row['options'])) {
            $row['options_array'] = array_map('trim', explode(',', $row['options']));
        }
        $questions[] = $row;
    }

    // Fetch all units (including archived)
    $sql = sprintf("SELECT * FROM flintmancms_maintenance_equipment WHERE equipment_level=%d ORDER BY archived ASC, unit_id ASC", $equipment_level);
    $result = $db->sql_query($sql);
    $units = array();
    while ($row = $db->sql_fetchrow($result)) {
        // Fetch answers for this unit
        $unit_id = $row['id'];
        $sql2 = sprintf("SELECT * FROM flintmancms_maintenance_answers WHERE equipment_id=%d", $unit_id);
        $result2 = $db->sql_query($sql2);
        $answers = array();
        while ($answer = $db->sql_fetchrow($result2)) {
            $answers[$answer['question_id']] = $answer['value'];
        }
        $row['answers'] = $answers;
        $units[] = $row;
    }

    // If this is secondary units, fetch primary units for the dropdown
    $primary_units = array();
    if ($equipment_level == 2) {
        $sql = "SELECT * FROM flintmancms_maintenance_equipment WHERE equipment_level=1 AND archived=0 ORDER BY unit_id ASC";
        $result = $db->sql_query($sql);
        while ($row = $db->sql_fetchrow($result)) {
            $primary_units[] = $row;
        }
    }

    $smarty->assign('units', $units);
    $smarty->assign('questions', $questions);
    $smarty->assign('unit_label', $unit_label);
    $smarty->assign('unit_type', $unit_type);
    $smarty->assign('primary_units', $primary_units);
}

/* ========================================================================
 * OUTPUT
 * ======================================================================== */
$smarty->assign(
    array(
        'action' => $action,
        'message' => $message,
        'primary_label' => $primary_label,
        'secondary_label' => $secondary_label,
        'columns_to_show' => $columns_to_show,
        'admin_url' => 'admin.php?n=plugins&p=maintenance'
    )
);

include(BASE_PATH . 'header.php');
$smarty->display(PLUGINS_PATH . 'maintenance/template/page.htm');
?>
