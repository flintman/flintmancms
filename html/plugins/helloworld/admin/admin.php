<?php
/* * *************************************************************************
 *  FlintmanCMS Hello World Plugin - Admin Interface
 *
 *  PURPOSE:
 *  This file handles the administrative interface for the plugin.
 *  Admins can perform CRUD operations (Create, Read, Update, Delete)
 *  on plugin data. Accessed via: admin.php?n=plugins&p=helloworld
 *
 *  EXECUTION FLOW:
 *  1. Security check (IN_CMS constant)
 *  2. Authentication (admin permissions)
 *  3. Determine action (add, edit, delete, settings, list)
 *  4. Process form submissions or display forms
 *  5. Assign variables to Smarty template
 *  6. Template (template/page.htm) renders the interface
 *
 *  TEMPLATE INTEGRATION:
 *  This file assigns $form_action and $content to Smarty.
 *  The template/page.htm file wraps these in proper HTML form structure.
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access to this file
if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

/* ========================================================================
 * AUTHENTICATION - ADMIN ACCESS ONLY
 * ========================================================================
 * This ensures only administrators can access the plugin admin interface.
 * The 'Admin' group is added to $page_lvl, then authentication.php checks it.
 * ======================================================================== */
array_push($page_lvl, 'Admin');
include(INCLUDES_PATH . 'authentication.php');

/* ========================================================================
 * INITIALIZE VARIABLES
 * ======================================================================== */
$form_action = '';  // Where the form submits to
$content = '';      // HTML content for the admin interface
// Note: $errorMsg is only set when there's an actual error

/* ========================================================================
 * ACTION: ADD NEW MESSAGE
 * ========================================================================
 * Two-step process:
 * 1. Display the add form
 * 2. Process the form submission and save to database
 * ======================================================================== */
if (isset($_GET['action']) && $_GET['action'] == 'add') {

    // STEP 1: Display the form
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=helloworld&action=add";

        $content .= '<table width="100%" class="form-table">';
        $content .= '<tr>';
        $content .= '<td width="200"><strong>Message:</strong></td>';
        $content .= '<td><input name="message" type="text" class="form-control" maxlength="255" required></td>';
        $content .= '</tr>';
        $content .= '<tr>';
        $content .= '<td><strong>Author:</strong></td>';
        $content .= '<td><input name="author" type="text" class="form-control" maxlength="100" value="Admin"></td>';
        $content .= '</tr>';
        $content .= '<tr>';
        $content .= '<td><strong>Active:</strong></td>';
        $content .= '<td><input type="checkbox" name="active" value="1" checked> Yes</td>';
        $content .= '</tr>';
        $content .= '</table>';
        $content .= '<br>';
        $content .= '<input type="submit" value="Save" name="submit" class="btn btn-primary">';
        $content .= ' <a href="admin.php?n=plugins&p=helloworld" class="button">Cancel</a>';
    }
    // STEP 2: Process the form submission
    elseif (isset($_POST['submit']) && $_POST['submit'] == 'Save') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $message = scrub_input($_POST['message'], ['max_length' => 500]);
        $author = scrub_input($_POST['author'], ['type' => 'alphanum', 'max_length' => 100]);
        $active = isset($_POST['active']) ? 1 : 0;

        // Validation
        if (empty($message)) {
            $errorMsg = "Message cannot be empty";
        } else {
            // Insert into database
            $sql = sprintf("INSERT INTO flintmancms_helloworld_messages " .
                          "(message, author, created_date, active) VALUES (%s, %s, NOW(), %s)",
                          quote_smart($message),
                          quote_smart($author),
                          quote_smart($active));
            $result = $db->sql_query($sql);

            if ($result) {
                // Success - redirect back to main admin page
                header("Location: admin.php?n=plugins&p=helloworld");
                exit;
            } else {
                $errorMsg = "Database error: " . $db->sql_error();
            }
        }
    }
}

/* ========================================================================
 * ACTION: EDIT EXISTING MESSAGE
 * ========================================================================
 * Similar to ADD, but loads existing data first
 * ======================================================================== */
elseif (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $id = scrub_input($_GET['id'], ['type' => 'int']);

    // STEP 1: Display the form with existing data
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=helloworld&action=edit&id=" . $id;

        // Load existing data
        $sql = sprintf("SELECT * FROM flintmancms_helloworld_messages WHERE id=%s",
                      quote_smart($id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result);

        if ($data) {
            $content .= '<table width="100%" class="form-table">';
            $content .= '<tr>';
            $content .= '<td width="200"><strong>Message:</strong></td>';
            $content .= '<td><input name="message" type="text" class="form-control" maxlength="255" value="' . htmlspecialchars($data['message']) . '" required></td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td><strong>Author:</strong></td>';
            $content .= '<td><input name="author" type="text" class="form-control" maxlength="100" value="' . htmlspecialchars($data['author']) . '"></td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td><strong>Active:</strong></td>';
            $content .= '<td><input type="checkbox" name="active" value="1"' . ($data['active'] ? ' checked' : '') . '> Yes</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td><strong>Created:</strong></td>';
            $content .= '<td>' . date('F j, Y g:i A', strtotime($data['created_date'])) . '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '<br>';
            $content .= '<input type="submit" value="Update" name="submit" class="btn btn-primary">';
            $content .= ' <a href="admin.php?n=plugins&p=helloworld" class="button">Cancel</a>';
        } else {
            $errorMsg = "Message not found";
        }
    }
    // STEP 2: Process the update
    elseif (isset($_POST['submit']) && $_POST['submit'] == 'Update') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $message = scrub_input($_POST['message'], ['max_length' => 500]);
        $author = scrub_input($_POST['author'], ['type' => 'alphanum', 'max_length' => 100]);
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($message)) {
            $errorMsg = "Message cannot be empty";
        } else {
            $sql = sprintf("UPDATE flintmancms_helloworld_messages " .
                          "SET message=%s, author=%s, active=%s WHERE id=%s",
                          quote_smart($message),
                          quote_smart($author),
                          quote_smart($active),
                          quote_smart($id));
            $result = $db->sql_query($sql);

            if ($result) {
                header("Location: admin.php?n=plugins&p=helloworld");
                exit;
            } else {
                $errorMsg = "Database error: " . $db->sql_error();
            }
        }
    }
}

/* ========================================================================
 * ACTION: DELETE MESSAGE
 * ========================================================================
 * Requires confirmation before deleting
 * ======================================================================== */
elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = scrub_input($_GET['id'], ['type' => 'int']);

    // STEP 1: Show confirmation
    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=helloworld&action=delete&id=" . $id;

        // Load message to show what will be deleted
        $sql = sprintf("SELECT * FROM flintmancms_helloworld_messages WHERE id=%s",
                      quote_smart($id));
        $result = $db->sql_query($sql);
        $data = $db->sql_fetchrow($result);

        if ($data) {
            $content .= '<div class="warning-box">';
            $content .= '<h3>Confirm Deletion</h3>';
            $content .= '<p>Are you sure you want to delete this message?</p>';
            $content .= '<blockquote>"' . htmlspecialchars($data['message']) . '"<br>';
            $content .= '<em>by ' . htmlspecialchars($data['author']) . '</em></blockquote>';
            $content .= '<p><strong>This action cannot be undone!</strong></p>';
            $content .= '</div>';
            $content .= '<br>';
            $content .= '<input type="submit" value="Delete" name="submit" class="btn btn-danger">';
            $content .= ' <a href="admin.php?n=plugins&p=helloworld" class="button">Cancel</a>';
        } else {
            $errorMsg = "Message not found";
        }
    }
    // STEP 2: Confirmed - perform deletion
    elseif (isset($_POST['submit']) && $_POST['submit'] == 'Delete') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("CSRF token validation failed");
        }
        $sql = sprintf("DELETE FROM flintmancms_helloworld_messages WHERE id=%s",
                      quote_smart($id));
        $result = $db->sql_query($sql);

        if ($result) {
            header("Location: admin.php?n=plugins&p=helloworld");
            exit;
        } else {
            $errorMsg = "Database error: " . $db->sql_error();
        }
    }
}

/* ========================================================================
 * ACTION: PLUGIN SETTINGS
 * ========================================================================
 * Manage plugin-wide settings
 * ======================================================================== */
elseif (isset($_GET['action']) && $_GET['action'] == 'settings') {

    if (!isset($_POST['submit']) || !$_POST['submit']) {
        $form_action = "admin.php?n=plugins&p=helloworld&action=settings";

        // Load current settings
        $sql = "SELECT * FROM flintmancms_helloworld_settings";
        $result = $db->sql_query($sql);
        $settings = array();
        while ($row = $db->sql_fetchrow($result)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }

        $content .= '<h3>Plugin Settings</h3>';
        $content .= '<table width="100%" class="form-table">';
        $content .= '<tr>';
        $content .= '<td width="200"><strong>Default Greeting:</strong></td>';
        $content .= '<td><input name="default_greeting" type="text" class="form-control" value="' .
                    htmlspecialchars($settings['default_greeting'] ?? 'Hello, World!') . '"></td>';
        $content .= '</tr>';
        $content .= '<tr>';
        $content .= '<td><strong>Max Messages:</strong></td>';
        $content .= '<td><input name="max_messages" type="number" class="form-control" value="' .
                    htmlspecialchars($settings['max_messages'] ?? '100') . '"></td>';
        $content .= '</tr>';
        $content .= '<tr>';
        $content .= '<td><strong>Allow Anonymous:</strong></td>';
        $content .= '<td><input type="checkbox" name="allow_anonymous" value="1"' .
                    (($settings['allow_anonymous'] ?? '1') == '1' ? ' checked' : '') . '> Yes</td>';
        $content .= '</tr>';
        $content .= '</table>';
        $content .= '<br>';
        $content .= '<input type="submit" value="Save Settings" name="submit" class="btn btn-primary">';
        $content .= ' <a href="admin.php?n=plugins&p=helloworld" class="button">Cancel</a>';
    } else {
        // Update settings
        $settings_to_update = array(
            'default_greeting' => scrub_input($_POST['default_greeting'], ['max_length' => 200]),
            'max_messages' => scrub_input($_POST['max_messages'], ['type' => 'int']),
            'allow_anonymous' => isset($_POST['allow_anonymous']) ? '1' : '0'
        );

        foreach ($settings_to_update as $name => $value) {
            $sql = sprintf("UPDATE flintmancms_helloworld_settings SET setting_value=%s WHERE setting_name=%s",
                          quote_smart($value),
                          quote_smart($name));
            $db->sql_query($sql);
        }

        header("Location: admin.php?n=plugins&p=helloworld");
        exit;
    }
}

/* ========================================================================
 * DEFAULT ACTION: LIST ALL MESSAGES
 * ========================================================================
 * Display a table of all messages with action links
 * Uses the Report class for consistent table formatting
 * ======================================================================== */
else {
    // Query all messages
    $sql = "SELECT * FROM flintmancms_helloworld_messages ORDER BY created_date DESC";
    $result = $db->sql_query($sql);

    // Build array for table
    $messages = array();
    while ($data = $db->sql_fetchrow($result)) {
        array_push($messages, array(
            'id' => $data['id'],
            'message' => substr($data['message'], 0, 50) . (strlen($data['message']) > 50 ? '...' : ''),
            'author' => $data['author'],
            'date' => date('Y-m-d H:i', strtotime($data['created_date'])),
            'active' => $data['active'] ? 'Yes' : 'No',
            'edit' => '<a href="admin.php?n=plugins&p=helloworld&action=edit&id=' . $data['id'] . '" class="button">Edit</a>',
            'delete' => '<a href="admin.php?n=plugins&p=helloworld&action=delete&id=' . $data['id'] . '" class="button">Delete</a>'
        ));
    }

    // Build HTML table for List.js
    $content = '<div style="margin-bottom: 1rem;">' .
               '<a href="admin.php?n=plugins&p=helloworld&action=add" class="btn btn-primary">Add New Message</a> ' .
               '<a href="admin.php?n=plugins&p=helloworld&action=settings" class="button">Settings</a> ' .
               '<a href="index.php?n=plugins&p=helloworld" class="button" target="_blank">View Frontend</a>' .
               '</div>';
    $content .= '<div id="helloworld-list">
        <input class="search user-search-bar form-control" placeholder="Search messages..." />
        <table id="helloworld-table" class="listjs-table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="sort" data-sort="id">ID</th>
                    <th class="sort" data-sort="message">Message</th>
                    <th class="sort" data-sort="author">Author</th>
                    <th class="sort" data-sort="date">Date</th>
                    <th class="sort" data-sort="active">Active</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody class="list">
';
    foreach ($messages as $row) {
        $content .= '<tr>' .
            '<td class="id">' . $row['id'] . '</td>' .
            '<td class="message">' . htmlspecialchars($row['message']) . '</td>' .
            '<td class="author">' . htmlspecialchars($row['author']) . '</td>' .
            '<td class="date">' . $row['date'] . '</td>' .
            '<td class="active">' . $row['active'] . '</td>' .
            '<td>' . $row['edit'] . '</td>' .
            '<td>' . $row['delete'] . '</td>' .
            '</tr>';
    }
    $content .= '</tbody></table>';
    $content .= '<div id="helloworld-pagination"><ul class="pagination"></ul></div></div>';
    $content .= '<script>
    window.addEventListener("DOMContentLoaded", function() {
        var hwList = document.getElementById("helloworld-list");
        if (hwList) {
            var options = {
                valueNames: ["id", "message", "author", "date", "active"],
                page: 10,
                searchClass: "search",
                listClass: "list",
                pagination: [{
                    innerWindow: 2,
                    left: 1,
                    right: 1,
                    paginationClass: "pagination",
                    item: "<li><a class=\"page\" href=\"#\"></a></li>"
                }]
            };
            var listObj = new List("helloworld-list", options);
            var pagDiv = document.getElementById("helloworld-pagination");
            var pagList = hwList.getElementsByClassName("pagination")[0];
            if (pagDiv && pagList) {
                pagDiv.appendChild(pagList);
            }
        }
    });
    </script>';
}

/* ========================================================================
 * NOTE: Error messages are handled by header.php
 * If you set $errorMsg anywhere in this file, header.php will display it
 * ======================================================================== */

/* ========================================================================
 * ASSIGN TO SMARTY TEMPLATE
 * ========================================================================
 * These variables are used in template/page.htm
 * ======================================================================== */
$smarty->assign('form_action', $form_action);
$smarty->assign('content', $content);

/* ========================================================================
 * DISPLAY THE PAGE
 * ======================================================================== */
include(BASE_PATH . 'header.php');
$smarty->display(PLUGINS_PATH . '/helloworld/template/page.htm');

/* ========================================================================
 * NOTES FOR DEVELOPERS
 * ========================================================================
 *
 * ADMIN INTERFACE PATTERNS:
 *
 * 1. CRUD OPERATIONS:
 *    - List: Query all, display in table with action links
 *    - Add: Show form, process submission, redirect
 *    - Edit: Load data, show form, process update, redirect
 *    - Delete: Show confirmation, process deletion, redirect
 *
 * 2. FORM PROCESSING:
 *    Always use two-step pattern:
 *    - Step 1: if (!isset($_POST['submit'])) - show form
 *    - Step 2: else - process form and redirect
 *
 * 3. REDIRECTS:
 *    - After successful operations, always redirect
 *    - Prevents duplicate submissions on page refresh
 *    - Use header("Location: ...") followed by exit;
 *
 * 4. THE REPORT CLASS:
 *    - Provides consistent table formatting
 *    - Methods: setMainAttributes, addOutputColumn, display, showReport
 *    - Each column needs: field name, display name, alignment, width
 *
 * 5. TEMPLATE VARIABLES:
 *    - $form_action: Where the form submits
 *    - $content: The HTML content to display
 *    - template/page.htm wraps these in proper structure
 *
 * 6. SECURITY:
 *    - Check admin authentication at top of file
 *    - Sanitize all inputs with scrub_input()
 *    - Use quote_smart() for SQL parameters
 *    - Use htmlspecialchars() when displaying user data
 *
 * ======================================================================== */
?>
