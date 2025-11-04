<?php
/* * *************************************************************************
 *  FlintmanCMS Hello World Plugin - Frontend Display
 *
 *  PURPOSE:
 *  This is the main plugin file that handles the frontend display and
 *  user interactions. It is loaded when users visit:
 *  index.php?n=plugins&p=helloworld
 *
 *  EXECUTION FLOW:
 *  1. Security check (IN_CMS constant)
 *  2. Load CSS/JS resources
 *  3. Check user permissions
 *  4. Process user actions (view, submit, etc.)
 *  5. Query database for content
 *  6. Build HTML output
 *  7. Assign variables to Smarty template
 *
 *  AVAILABLE VARIABLES:
 *  - $db          : Database connection object
 *  - $smarty      : Smarty template engine
 *  - $config      : Site configuration
 *  - $_SESSION    : User session data
 *  - $_GET/$_POST : Request parameters
 *  - $scriptAdd   : String to append CSS/JS includes
 *
 * ************************************************************************* */

// SECURITY: Prevent direct access to this file
if (!defined('IN_CMS')) {
    die("ERROR - Hacking attempt");
}

/* ========================================================================
 * STEP 1: ADD CSS AND JAVASCRIPT RESOURCES
 * ========================================================================
 * Use $scriptAdd to inject custom CSS and JavaScript into the page header.
 * This ensures your plugin's styles and scripts are loaded properly.
 * ======================================================================== */
$scriptAdd .= '<link rel="stylesheet" href="plugins/helloworld/css/helloworld.css" type="text/css" />';
$scriptAdd .= '<script src="plugins/helloworld/js/helloworld.js" type="text/javascript"></script>';

/* ========================================================================
 * STEP 2: AUTHENTICATION AND PERMISSIONS
 * ========================================================================
 * Check if the user has permission to access this plugin.
 * The $page_lvl array contains group names that can access this page.
 * ======================================================================== */

// Initialize page level array (stores allowed groups)
$page_lvl = isset($page_lvl) ? $page_lvl : array();

// Get the plugin name from URL parameter
$p = isset($_GET['p']) ? scrub_input($_GET['p'], ['type' => 'alphanum', 'max_length' => 50]) : '';

// Query to find this plugin's ID and permissions
$sql = sprintf("SELECT * FROM " . DB_PREFIX . "_plugins WHERE name=%s",
                quote_smart($p));
$result = $db->sql_query($sql);
$ids = $db->sql_fetchrow($result);

// If plugin exists, check which groups can access it
if ($ids) {
    $sql2 = sprintf("SELECT * FROM " . DB_PREFIX . "_group_links WHERE type='plugins' AND type_id=%s",
                    quote_smart($ids['id']));
    $result = $db->sql_query($sql2);

    // Build array of allowed groups
    while ($data = $db->sql_fetchrow($result)) {
        $sql3 = sprintf("SELECT * FROM " . DB_PREFIX . "_groups WHERE id=%s",
                        quote_smart($data['group_id']));
        $result2 = $db->sql_query($sql3);
        $data2 = $db->sql_fetchrow($result2);
        if ($data2) {
            array_push($page_lvl, $data2['name']);
        }
    }
}

// Include authentication check (compares user's group with $page_lvl)
include(INCLUDES_PATH . 'authentication.php');

/* ========================================================================
 * STEP 3: INITIALIZE VARIABLES
 * ======================================================================== */
$title = "Hello World Plugin";  // Page title
$body = "";                      // HTML content to display
$message = "";                   // Status/error messages
$action = isset($_GET['action']) ? scrub_input($_GET['action'], ['type' => 'alpha', 'max_length' => 20]) : 'list';

/* ========================================================================
 * STEP 4: HANDLE USER ACTIONS
 * ========================================================================
 * Process different actions based on URL parameters and form submissions.
 * Common pattern: Check GET action, then check for POST submission.
 * ======================================================================== */

// ACTION: View a single message in detail
if ($action === 'view' && isset($_GET['id'])) {
    $id = scrub_input($_GET['id'], ['type' => 'int']);

    // Query for specific message
    $sql = sprintf("SELECT * FROM " . DB_PREFIX . "_helloworld_messages WHERE id=%s AND active=1",
                   quote_smart($id));
    $result = $db->sql_query($sql);
    $data = $db->sql_fetchrow($result);

    if ($data) {
        $title = "Message from " . htmlspecialchars($data['author']);
        $body .= '<div class="hello-message-detail">';
        $body .= '<h2>' . htmlspecialchars($data['message']) . '</h2>';
        $body .= '<p class="meta">By: ' . htmlspecialchars($data['author']) . '</p>';
        $body .= '<p class="meta">Date: ' . date('F j, Y g:i A', strtotime($data['created_date'])) . '</p>';
        $body .= '<a href="index.php?n=plugins&p=helloworld" class="button">Back to List</a>';
        $body .= '</div>';
    } else {
        $message = "Message not found.";
        $body .= '<a href="index.php?n=plugins&p=helloworld" class="button">Back to List</a>';
    }
}
// ACTION: Submit a new message
elseif ($action == 'add') {
    if (!isset($_POST['submit'])) {
        // Show the form
        $title = "Add New Message";
        $body .= '<div class="hello-form">';
        $body .= '<form method="POST" action="index.php?n=plugins&p=helloworld&action=add">';
        $body .= '<div class="form-group">';
        $body .= '<label for="message">Your Message:</label>';
        $body .= '<input type="text" name="message" id="message" class="form-control" maxlength="255" required>';
        $body .= '</div>';
        $body .= '<div class="form-group">';
        $body .= '<label for="author">Your Name:</label>';
        $body .= '<input type="text" name="author" id="author" class="form-control" maxlength="100" value="';
        $body .= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Anonymous';
        $body .= '" required>';
        $body .= '</div>';
        $body .= '<button type="submit" name="submit" value="Submit" class="btn btn-primary">Submit Message</button> ';
        $body .= '<a href="index.php?n=plugins&p=helloworld" class="button">Cancel</a>';
        $body .= '</form>';
        $body .= '</div>';
    } else {
        // Process the form submission
        $new_message = scrub_input($_POST['message'], ['max_length' => 500]);
        $new_author = scrub_input($_POST['author'], ['type' => 'alphanum', 'max_length' => 100]);

        if (!empty($new_message) && !empty($new_author)) {
            $sql = sprintf("INSERT INTO " . DB_PREFIX . "_helloworld_messages (message, author, created_date, active) " .
                          "VALUES (%s, %s, NOW(), 1)",
                          quote_smart($new_message),
                          quote_smart($new_author));
            $result = $db->sql_query($sql);

            if ($result) {
                // Success - redirect to main page
                header("Location: index.php?n=plugins&p=helloworld&msg=added");
                exit;
            } else {
                $message = "Error saving message: " . $db->sql_error();
            }
        } else {
            $message = "Please fill in all fields.";
        }
    }
}
// DEFAULT ACTION: List all messages
else {
    $title = "Hello World Messages";

    // Check for status message from redirect
    if (isset($_GET['msg']) && $_GET['msg'] == 'added') {
        $message = '<div class="success-message">Your message has been added successfully!</div>';
    }

    // Query all active messages
    $sql = "SELECT * FROM " . DB_PREFIX . "_helloworld_messages WHERE active=1 ORDER BY created_date DESC";
    $result = $db->sql_query($sql);

    // Check if we have any messages
    $count = $db->sql_numrows($result);

    if ($count > 0) {
        $body .= '<div class="hello-messages">';
        $body .= '<p>Total messages: ' . $count . '</p>';
        $body .= '<a href="index.php?n=plugins&p=helloworld&action=add" class="btn btn-primary">Add New Message</a>';
        $body .= '<hr>';

        // Loop through all messages
        while ($data = $db->sql_fetchrow($result)) {
            $body .= '<div class="hello-message-card">';
            $body .= '<h3>' . htmlspecialchars($data['message']) . '</h3>';
            $body .= '<p class="author">By: ' . htmlspecialchars($data['author']) . '</p>';
            $body .= '<p class="date">' . date('F j, Y', strtotime($data['created_date'])) . '</p>';
            $body .= '<a href="index.php?n=plugins&p=helloworld&action=view&id=' . $data['id'] . '" class="button">View Details</a>';
            $body .= '</div>';
        }

        $body .= '</div>';
    } else {
        $body .= '<p>No messages yet. Be the first to add one!</p>';
        $body .= '<a href="index.php?n=plugins&p=helloworld&action=add" class="btn btn-primary">Add New Message</a>';
    }
}

/* ========================================================================
 * STEP 5: ASSIGN VARIABLES TO SMARTY TEMPLATE
 * ========================================================================
 * The CMS uses Smarty for templating. Assign variables that will be
 * available in the template files (header.htm, footer.htm, etc.)
 * ======================================================================== */
$smarty->assign('text', $title);
$smarty->assign('body', $message . $body);

/* ========================================================================
 * DISPLAY THE PAGE
 * ======================================================================== */
include(BASE_PATH . 'header.php');
$smarty->display(TEMPLATES_PATH . $config['template'] . '/plugins/plugins.htm');

/* ========================================================================
 * NOTES FOR DEVELOPERS
 * ========================================================================
 *
 * COMMON PATTERNS:
 *
 * 1. LISTING ITEMS:
 *    - Query with ORDER BY for sorting
 *    - Loop with while ($row = $db->sql_fetchrow($result))
 *    - Build HTML in $body variable
 *
 * 2. VIEWING SINGLE ITEM:
 *    - Get ID from $_GET['id']
 *    - Query with WHERE id=X
 *    - Check if result exists before displaying
 *
 * 3. FORMS (Add/Edit):
 *    - Check if form submitted: if (isset($_POST['submit']))
 *    - If not submitted: display form
 *    - If submitted: process data and redirect
 *
 * 4. SECURITY:
 *    - Always use scrub_input() for user input
 *    - Always use quote_smart() for SQL values
 *    - Use htmlspecialchars() when outputting user data in HTML
 *
 * 5. REDIRECTS:
 *    - Use header("Location: ...") after successful operations
 *    - Always call exit; after header()
 *    - Pass messages via URL parameters (e.g., ?msg=success)
 *
 * ======================================================================== */
?>
