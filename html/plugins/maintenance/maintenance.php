<?php
/* * *************************************************************************
 *  FlintmanCMS Maintenance Tracker Plugin - Frontend Display
 *
 *  PURPOSE:
 *  Main plugin file for maintenance tracking system
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access
if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

/* ========================================================================
 * LOAD CSS AND JAVASCRIPT
 * ======================================================================== */
$scriptAdd .= '<link rel="stylesheet" href="plugins/maintenance/css/maintenance.css?v=' . time() . '" type="text/css" />';

/* ========================================================================
 * AUTHENTICATION AND PERMISSIONS
 * ======================================================================== */
$page_lvl = isset($page_lvl) ? $page_lvl : array();
$p = isset($_GET['p']) ? scrub_input($_GET['p'], ['type' => 'alphanum', 'max_length' => 50]) : '';

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
 * INITIALIZE VARIABLES
 * ======================================================================== */
$title = "Maintenance Tracker";
$body = "";
$action = isset($_GET['action']) ? scrub_input($_GET['action'], ['type' => 'alpha', 'max_length' => 20]) : 'dashboard';

/* ========================================================================
 * HANDLE ACTIONS
 * ======================================================================== */

/* ========================================================================
 * HANDLE ACTIONS
 * ======================================================================== */

// ACTION: Dashboard - show navigation menu
if ($action === 'dashboard') {
    // Count active primary units
    $sql = "SELECT COUNT(*) as count FROM flintmancms_maintenance_equipment WHERE equipment_level=1 AND archived=0";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $primary_count = $row['count'];

    // Count active secondary units
    $sql = "SELECT COUNT(*) as count FROM flintmancms_maintenance_equipment WHERE equipment_level=2 AND archived=0";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $secondary_count = $row['count'];

    // Fetch last 5 maintenance records
    $sql = "SELECT r.*,
            CASE
                WHEN r.secondary_id IS NOT NULL THEN CONCAT('{$secondary_label} ', e2.unit_id)
                WHEN r.pmy_id IS NOT NULL THEN CONCAT('{$primary_label} ', e1.unit_id)
            END as unit_name,
            CASE
                WHEN r.secondary_id IS NOT NULL THEN 2
                ELSE 1
            END as equipment_level,
            CASE
                WHEN r.secondary_id IS NOT NULL THEN r.secondary_id
                ELSE r.pmy_id
            END as unit_id
            FROM flintmancms_maintenance_records r
            LEFT JOIN flintmancms_maintenance_equipment e1 ON r.pmy_id = e1.id
            LEFT JOIN flintmancms_maintenance_equipment e2 ON r.secondary_id = e2.id
            ORDER BY r.performed_at DESC, r.id DESC
            LIMIT 5";
    $result = $db->sql_query($sql);

    $title = "Maintenance Tracker";
    $body .= '<div class="maintenance-dashboard">';
    $body .= '<div class="dashboard-grid">';

    // Primary units block
    $body .= '<div class="dashboard-card">';
    $body .= '<h3>' . htmlspecialchars($primary_label) . 's</h3>';
    // Add theme-friendly classes so counts inherit theme styles if present
    $body .= '<p class="count stat-number">' . $primary_count . '</p>';
    // Use both common button classes so theme styles apply (button, btn, btn-primary)
    $body .= '<a href="index.php?n=plugins&p=maintenance&action=units&type=primary" class="button btn btn-primary">View All</a>';
    $body .= '</div>';

    // Secondary units block
    $body .= '<div class="dashboard-card">';
    $body .= '<h3>' . htmlspecialchars($secondary_label) . 's</h3>';
    $body .= '<p class="count stat-number">' . $secondary_count . '</p>';
    $body .= '<a href="index.php?n=plugins&p=maintenance&action=units&type=secondary" class="button btn btn-primary">View All</a>';
    $body .= '</div>';

    // Recent records block
    $body .= '<div class="dashboard-card recent-records">';
    $body .= '<h3>Recent Maintenance</h3>';

    if ($db->sql_numrows($result) > 0) {
        $body .= '<ul class="recent-list">';
        while ($row = $db->sql_fetchrow($result)) {
            $body .= '<li>';
            $body .= '<a href="index.php?n=plugins&p=maintenance&action=viewrecord&record_id=' . $row['id'] . '">';
            $body .= htmlspecialchars($row['unit_name']) . ' - ' . htmlspecialchars($row['type_of_service']);
            $body .= '<br><small>' . date('M d, Y', strtotime($row['performed_at'])) . '</small>';
            $body .= '</a>';
            $body .= '</li>';
        }
        $body .= '</ul>';
    } else {
        $body .= '<p>No maintenance records yet.</p>';
    }

    $body .= '</div>';
    $body .= '</div>'; // End dashboard-grid
    $body .= '</div>'; // End maintenance-dashboard
}

// ACTION: View units list (primary or secondary)
elseif ($action === 'units') {
    $unit_type = isset($_GET['type']) ? scrub_input($_GET['type']) : 'primary';
    $equipment_level = ($unit_type === 'primary') ? 1 : 2;
    $unit_label = ($unit_type === 'primary') ? $primary_label : $secondary_label;

    // Fetch questions for this equipment level
    $sql = sprintf("SELECT * FROM flintmancms_maintenance_questions WHERE equipment_level=%d ORDER BY position ASC", $equipment_level);
    $result = $db->sql_query($sql);
    $questions = array();
    while ($row = $db->sql_fetchrow($result)) {
        $questions[] = $row;
    }

    // Limit questions based on columns_to_show config
    $display_questions = array_slice($questions, 0, $columns_to_show);

    // Fetch units with their answers
    $sql = sprintf("SELECT * FROM flintmancms_maintenance_equipment WHERE equipment_level=%d AND archived=0 ORDER BY unit_id ASC", $equipment_level);
    $result = $db->sql_query($sql);

    $title = $unit_label . 's';
    $body .= '<div class="maintenance-units">';
    $body .= '<h1>' . htmlspecialchars($unit_label) . 's</h1>';
    $body .= '<div class="back-link"><a href="index.php?n=plugins&p=maintenance">← Back to Dashboard</a></div>';

    if ($db->sql_numrows($result) > 0) {
        // Prepare valueNames for List.js (unit_id plus each question answer key)
        $unitsValueNames = array('unit_id');
        foreach ($display_questions as $q) {
            $unitsValueNames[] = 'answer_' . $q['id'];
        }

        $body .= '<div id="units-list">';
        $body .= '<input class="search user-search-bar form-control" placeholder="Search ' . htmlspecialchars($unit_label) . 's..." />';
        $body .= '<table class="units-table listjs-table">';
        $body .= '<thead><tr>';
        $body .= '<th class="sort" data-sort="unit_id">Unit ID</th>';
        foreach ($display_questions as $q) {
            $body .= '<th>' . htmlspecialchars($q['label'] ?? '') . '</th>';
        }
        $body .= '<th>Actions</th>';
        $body .= '</tr></thead><tbody class="list">';

        while ($row = $db->sql_fetchrow($result)) {
            // Fetch answers for this unit
            $unit_id = $row['id'];
            $sql2 = sprintf("SELECT * FROM flintmancms_maintenance_answers WHERE equipment_id=%d", $unit_id);
            $result2 = $db->sql_query($sql2);
            $answers = array();
            while ($answer = $db->sql_fetchrow($result2)) {
                $answers[$answer['question_id']] = $answer['value'];
            }

            $body .= '<tr>';
            $body .= '<td class="unit_id">' . htmlspecialchars($row['unit_id']) . '</td>';

            foreach ($display_questions as $q) {
                $value = $answers[$q['id']] ?? '';
                $body .= '<td class="answer_' . $q['id'] . '">' . htmlspecialchars($value) . '</td>';
            }

            $body .= '<td>';
            $body .= '<a href="index.php?n=plugins&p=maintenance&action=viewunit&unit_id=' . $row['id'] . '&level=' . $equipment_level . '" class="btn btn-small">View Records</a>';
            $body .= '</td>';
            $body .= '</tr>';
        }

    $body .= '</tbody></table>';
    $body .= '<div class="list-pagination"><ul class="pagination"></ul></div>';
    $body .= '</div>';

    // Initialize List.js for units
    $body .= '<script>';
    $body .= 'document.addEventListener("DOMContentLoaded", function() {';
    $body .= 'if (typeof List !== "undefined" && document.getElementById("units-list")) {';
    $body .= 'var valueNames = ' . json_encode($unitsValueNames) . ';';
    $body .= 'new List("units-list", { valueNames: valueNames, pagination: true, page: 10, searchClass: "search", listClass: "list" });';
    $body .= '}';
    $body .= '});';
    $body .= '</script>';
    } else {
        $body .= '<p>No ' . htmlspecialchars($unit_label) . 's found.</p>';
    }

    $body .= '</div>';
}

// ACTION: View maintenance records for a specific unit
elseif ($action === 'view_unit' || $action === 'viewunit') {
    $unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
    $equipment_level = isset($_GET['level']) ? intval($_GET['level']) : 1;

    if (!$unit_id) {
        $body = "<p>No unit selected.</p>";
    } else {
        // Fetch unit info
        $sql = sprintf("SELECT * FROM flintmancms_maintenance_equipment WHERE id=%d", $unit_id);
        $result = $db->sql_query($sql);
        $unit = $db->sql_fetchrow($result);

        if (!$unit) {
            $body = "<p>Unit not found.</p>";
        } else {
            $unit_label = ($equipment_level == 1) ? $primary_label : $secondary_label;
            $unit_name = $unit_label . ' ' . $unit['unit_id'];

            // Handle add maintenance record
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_maintenance'])) {
                $type_of_service = scrub_input($_POST['type_of_service']);
                $description = scrub_input($_POST['description']);
                $costs_of_parts = floatval($_POST['costs_of_parts'] ?? 0);
                $performed_at = scrub_input($_POST['performed_at']);
                $performed_by = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';

                // Insert maintenance record
                $secondary_id = ($equipment_level == 2) ? $unit_id : null;
                $pmy_id = ($equipment_level == 1) ? $unit_id : null;

                $sql = sprintf("INSERT INTO flintmancms_maintenance_records
                               (secondary_id, pmy_id, type_of_service, description, costs_of_parts, performed_at, performed_by)
                               VALUES (%s, %s, %s, %s, %s, %s, %s)",
                               $secondary_id ? quote_smart($secondary_id) : 'NULL',
                               $pmy_id ? quote_smart($pmy_id) : 'NULL',
                               quote_smart($type_of_service),
                               quote_smart($description),
                               quote_smart($costs_of_parts),
                               quote_smart($performed_at),
                               quote_smart($performed_by));
                $result = $db->sql_query($sql);

                if ($result) {
                    $maintenance_id = $db->sql_nextid();

                    // Handle photo uploads
                    if (!empty($_FILES['photos']['name'][0]) && $_FILES['photos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                        // Determine upload directory based on unit ID
                        $upload_dir = 'plugins/maintenance/images/' . $unit_id . '/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $uploaded_photos = array();
                        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                                // Create temporary file array for validation
                                $temp_file = array(
                                    'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                                    'name' => $_FILES['photos']['name'][$key],
                                    'size' => $_FILES['photos']['size'][$key],
                                    'type' => $_FILES['photos']['type'][$key],
                                    'error' => $_FILES['photos']['error'][$key]
                                );

                                // Validate uploaded file (max 10MB)
                                $validation = validate_upload($temp_file, ['image/jpeg', 'image/png', 'image/gif'], 10485760);

                                if ($validation['valid']) {
                                    // Use secure random filename from validation
                                    $filename = $validation['filename'];
                                    $file_path = $upload_dir . $filename;

                                    if (move_uploaded_file($tmp_name, $file_path)) {
                                        // Set secure file permissions
                                        chmod($file_path, 0644);
                                        $uploaded_photos[] = $file_path;
                                    }
                                }
                            }
                        }

                        if (!empty($uploaded_photos)) {
                            $photos_json = json_encode($uploaded_photos);
                            $sql = sprintf("UPDATE flintmancms_maintenance_records SET photos=%s WHERE id=%d",
                                          quote_smart($photos_json), $maintenance_id);
                            $db->sql_query($sql);
                        }
                    }

                    // Redirect to show success
                    header("Location: index.php?n=plugins&p=maintenance&action=viewunit&unit_id={$unit_id}&level={$equipment_level}&msg=added");
                    exit;
                }
            }

            $title = "Maintenance for " . $unit_name;
            $body .= '<div class="maintenance-records">';
            $body .= '<h1>Maintenance for ' . htmlspecialchars($unit_name) . '</h1>';
            $body .= '<div class="back-link">';
            $body .= '<a href="index.php?n=plugins&p=maintenance&action=units&type=' . ($equipment_level == 1 ? 'primary' : 'secondary') . '">← Back to Units</a>';
            $body .= '</div>';

            // Check for success message
            if (isset($_GET['msg']) && $_GET['msg'] === 'added') {
                $body .= '<div class="alert alert-success">Maintenance record added successfully!</div>';
            }

            // Add Maintenance Form (Collapsible)
            $body .= '<div class="add-maintenance-form">';
            $body .= '<button type="button" class="button btn btn-secondary maintenance-btn maintenance-btn-secondary toggle-form" onclick="document.getElementById(\'maintenanceForm\').style.display = document.getElementById(\'maintenanceForm\').style.display === \'none\' ? \'block\' : \'none\';">+ Add Maintenance Record</button>';
            $body .= '<div id="maintenanceForm" style="display: none; margin-top: 15px;">';
            $body .= '<h2>Add Maintenance Record</h2>';
            $body .= '<form method="POST" action="index.php?n=plugins&p=maintenance&action=viewunit&unit_id=' . $unit['id'] . '&level=' . $equipment_level . '" enctype="multipart/form-data">';
            $body .= '<div class="form-group">';
            $body .= '<label>Type of Service:</label>';
            $body .= '<input type="text" name="type_of_service" class="form-control" required />';
            $body .= '</div>';
            $body .= '<div class="form-group">';
            $body .= '<label>Description:</label>';
            $body .= '<textarea name="description" rows="4" class="no-editor form-control"></textarea>';
            $body .= '</div>';
            $body .= '<div class="form-group">';
            $body .= '<label>Cost of Parts ($):</label>';
            $body .= '<input type="number" name="costs_of_parts" class="form-control" step="0.01" min="0" value="0.00" />';
            $body .= '</div>';
            $body .= '<div class="form-group">';
            $body .= '<label>Date Performed:</label>';
            $body .= '<input type="date" name="performed_at" class="form-control" required />';
            $body .= '</div>';
            $body .= '<div class="form-group">';
            $body .= '<label>Photos:</label>';
            $body .= '<input type="file" name="photos[]" class="form-control" multiple accept="image/*" />';
            $body .= '</div>';
            $body .= '<button type="submit" name="add_maintenance" value="1" class="btn btn-primary maintenance-btn maintenance-btn-primary">Add Record</button> ';
            $body .= '<button type="button" class="btn btn-secondary maintenance-btn maintenance-btn-secondary" onclick="document.getElementById(\'maintenanceForm\').style.display = \'none\';">Cancel</button>';
            $body .= '</form>';
            $body .= '</div>';
            $body .= '</div>';

            // Disable TinyMCE for this specific form
            $body .= '<script>
            if (typeof tinyMCE !== "undefined") {
                tinyMCE.settings.mode = "specific_textareas";
                tinyMCE.settings.editor_selector = "mceEditor";
            }
            </script>';            // Fetch maintenance records
            if ($equipment_level == 2) {
                $sql = sprintf("SELECT * FROM flintmancms_maintenance_records WHERE secondary_id=%d ORDER BY performed_at DESC", $unit_id);
            } else {
                $sql = sprintf("SELECT * FROM flintmancms_maintenance_records WHERE pmy_id=%d ORDER BY performed_at DESC", $unit_id);
            }
            $result = $db->sql_query($sql);

            // Maintenance Records List
            $body .= '<div class="maintenance-list">';
            $body .= '<h2>Maintenance History</h2>';

            if ($db->sql_numrows($result) > 0) {
                // Build maintenance list wrapper for List.js
                $body .= '<div id="maintenance-list">';
                $body .= '<input class="search user-search-bar form-control" placeholder="Search maintenance records..." />';
                $body .= '<table class="maintenance-table listjs-table">';
                $body .= '<thead><tr>';
                $body .= '<th class="sort" data-sort="date">Date</th>';
                $body .= '<th class="sort" data-sort="type_of_service">Type of Service</th>';
                $body .= '<th>Description</th>';
                $body .= '<th class="sort" data-sort="cost">Cost</th>';
                $body .= '<th class="sort" data-sort="performed_by">Performed By</th>';
                $body .= '<th>Actions</th>';
                $body .= '</tr></thead><tbody class="list">';

                while ($row = $db->sql_fetchrow($result)) {
                    $body .= '<tr>';
                    $body .= '<td class="date">' . date('M d, Y', strtotime($row['performed_at'])) . '</td>';
                    $body .= '<td class="type_of_service">' . htmlspecialchars($row['type_of_service']) . '</td>';
                    $body .= '<td class="description">' . htmlspecialchars(substr($row['description'], 0, 50)) . '...</td>';
                    $body .= '<td class="cost">$' . number_format($row['costs_of_parts'], 2) . '</td>';
                    $body .= '<td class="performed_by">' . htmlspecialchars($row['performed_by']) . '</td>';
                    $body .= '<td><a href="index.php?n=plugins&p=maintenance&action=viewrecord&record_id=' . $row['id'] . '" class="btn btn-small">View Details</a></td>';
                    $body .= '</tr>';
                }

                $body .= '</tbody></table>';
                $body .= '<div class="list-pagination"><ul class="pagination"></ul></div>';
                $body .= '</div>';

                // Initialize List.js for maintenance records
                $maintenanceValueNames = array('date', 'type_of_service', 'description', 'cost', 'performed_by');
                $body .= '<script>';
                $body .= 'document.addEventListener("DOMContentLoaded", function() {';
                $body .= 'if (typeof List !== "undefined" && document.getElementById("maintenance-list")) {';
                $body .= 'var mNames = ' . json_encode($maintenanceValueNames) . ';';
                $body .= 'new List("maintenance-list", { valueNames: mNames, pagination: true, page: 10, searchClass: "search", listClass: "list" });';
                $body .= '}';
                $body .= '});';
                $body .= '</script>';
            } else {
                $body .= '<p>No maintenance records yet.</p>';
            }

            $body .= '</div>';
            $body .= '</div>';
        }
    }
}

// ACTION: View individual maintenance record
elseif ($action === 'view_record' || $action === 'viewrecord') {
    $record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;

    if (!$record_id) {
        $body = "<p>No record selected.</p>";
    } else {
        $sql = sprintf("SELECT * FROM flintmancms_maintenance_records WHERE id=%d", $record_id);
        $result = $db->sql_query($sql);
        $record = $db->sql_fetchrow($result);

        if (!$record) {
            $body = "<p>Record not found.</p>";
        } else {
            // Get unit info
            if ($record['secondary_id']) {
                $sql = sprintf("SELECT unit_id FROM flintmancms_maintenance_equipment WHERE id=%d", $record['secondary_id']);
                $result = $db->sql_query($sql);
                $unit_row = $db->sql_fetchrow($result);
                $unit_name = $secondary_label . ' ' . ($unit_row['unit_id'] ?? '');
                $equipment_level = 2;
                $unit_id = $record['secondary_id'];
            } else {
                $sql = sprintf("SELECT unit_id FROM flintmancms_maintenance_equipment WHERE id=%d", $record['pmy_id']);
                $result = $db->sql_query($sql);
                $unit_row = $db->sql_fetchrow($result);
                $unit_name = $primary_label . ' ' . ($unit_row['unit_id'] ?? '');
                $equipment_level = 1;
                $unit_id = $record['pmy_id'];
            }

            // Decode photos if exists
            $photos = array();
            if (!empty($record['photos'])) {
                $photos = json_decode($record['photos'], true);
            }

            $title = "Maintenance Record Details";
            $body .= '<div class="maintenance-detail">';
            $body .= '<h1>Maintenance Record Details</h1>';
            $body .= '<div class="back-link">';
            $body .= '<a href="index.php?n=plugins&p=maintenance&action=viewunit&unit_id=' . $unit_id . '&level=' . $equipment_level . '">← Back to ' . htmlspecialchars($unit_name) . '</a>';
            $body .= '</div>';

            $body .= '<div class="record-details">';
            $body .= '<div class="detail-row"><strong>Unit:</strong> ' . htmlspecialchars($unit_name) . '</div>';
            $body .= '<div class="detail-row"><strong>Type of Service:</strong> ' . htmlspecialchars($record['type_of_service']) . '</div>';
            $body .= '<div class="detail-row"><strong>Date Performed:</strong> ' . date('F d, Y', strtotime($record['performed_at'])) . '</div>';
            $body .= '<div class="detail-row"><strong>Performed By:</strong> ' . htmlspecialchars($record['performed_by']) . '</div>';
            $body .= '<div class="detail-row"><strong>Cost of Parts:</strong> $' . number_format($record['costs_of_parts'], 2) . '</div>';
            $body .= '<div class="detail-row"><strong>Description:</strong><br>' . nl2br(htmlspecialchars($record['description'])) . '</div>';

            if (!empty($photos)) {
                $body .= '<div class="detail-row"><strong>Photos:</strong></div>';
                $body .= '<div class="photo-gallery">';
                foreach ($photos as $photo) {
                    $body .= '<div class="photo-item">';
                    $body .= '<a href="' . htmlspecialchars($photo) . '" target="_blank">';
                    $body .= '<img src="' . htmlspecialchars($photo) . '" alt="Maintenance Photo" />';
                    $body .= '</a>';
                    $body .= '</div>';
                }
                $body .= '</div>';
            }

            $body .= '</div>';
            $body .= '</div>';
        }
    }
}

/* ========================================================================
 * OUTPUT
 * ======================================================================== */
$smarty->assign('title', $title);
$smarty->assign('body', $body);

include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');
?>
