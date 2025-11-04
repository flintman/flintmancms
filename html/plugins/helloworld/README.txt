/*
 * ============================================================================
 * FLINTMANCMS PLUGIN: HELLO WORLD
 * ============================================================================
 *
 * PURPOSE:
 * This is a comprehensive example plugin demonstrating all the key components
 * of the FlintmanCMS plugin architecture. Use this as a template for creating
 * your own custom plugins.
 *
 * ============================================================================
 * PLUGIN STRUCTURE OVERVIEW
 * ============================================================================
 *
 * A FlintmanCMS plugin requires the following directory structure:
 *
 * /plugins/helloworld/
 * │
 * ├── variable.php          - Plugin metadata and database table definitions
 * ├── helloworld.php        - Main plugin frontend logic
 * ├── index.html            - Security: prevents directory listing
 * │
 * ├── /admin/
 * │   ├── admin.php         - Admin interface logic (CRUD operations)
 * │   └── index.html        - Security: prevents directory listing
 * │
 * ├── /sql/
 * │   └── install.sql       - Database schema for plugin tables
 * │
 * ├── /template/
 * │   └── page.htm          - Smarty template for admin interface
 * │
 * ├── /css/                 - Optional: Plugin-specific stylesheets
 * │   └── helloworld.css
 * │
 * └── /js/                  - Optional: Plugin-specific JavaScript
 *     └── helloworld.js
 *
 * ============================================================================
 * HOW PLUGINS WORK IN FLINTMANCMS
 * ============================================================================
 *
 * 1. ACTIVATION:
 *    - When a plugin is activated, the system:
 *      a) Reads variable.php to get table names and metadata
 *      b) Checks if required tables exist
 *      c) Executes sql/install.sql to create tables if needed
 *      d) Adds a menu link to the navigation
 *      e) Records activation in the _plugins table
 *
 * 2. FRONTEND ACCESS:
 *    - Users access the plugin via: index.php?n=plugins&p=helloworld
 *    - The main file plugins/plugins.php routes to helloworld.php
 *    - helloworld.php handles display logic and user interactions
 *
 * 3. ADMIN ACCESS:
 *    - Admins access via: admin.php?n=plugins&p=helloworld
 *    - Routes to admin/admin.php for CRUD operations
 *    - Uses template/page.htm for rendering the admin interface
 *
 * 4. AUTHENTICATION:
 *    - Plugins can check user permissions using authentication.php
 *    - The $page_lvl array controls who can access plugin features
 *
 * 5. DATABASE:
 *    - Plugin tables use DB_PREFIX convention (e.g., flintmancms_helloworld_messages)
 *    - For FlintmanCMS, DB_PREFIX is "flintmancms"
 *    - All tables must be declared in $plugin_db_tables array in variable.php
 *    - In variable.php, list table names WITHOUT prefix (e.g., "helloworld_messages")
 *    - In install.sql, use FULL table names WITH prefix (e.g., flintmancms_helloworld_messages)
 *
 * ============================================================================
 * INTEGRATION WITH CMS
 * ============================================================================
 *
 * AVAILABLE GLOBAL VARIABLES:
 * - $db          : Database connection object
 * - $smarty      : Smarty template engine
 * - $config      : Site configuration array
 * - $_SESSION    : User session data (including permissions)
 * - $errorMsg    : Error message variable (if errors occur)
 * - $report      : Report generator class for tables
 *
 * AVAILABLE FUNCTIONS (from includes):
 * - scrub_input()    : Sanitize user input
 * - quote_smart()    : Prepare SQL values safely
 * - check_menu()     : Check menu permissions
 *
 * ============================================================================
 * BEST PRACTICES
 * ============================================================================
 *
 * 1. Always sanitize user input with scrub_input()
 * 2. Use quote_smart() for SQL parameters to prevent injection
 * 3. **XSS Protection (CRITICAL): Escape ALL user data for output with:**
 *    htmlspecialchars($data, ENT_QUOTES, 'UTF-8')
 * 4. Check for IN_CMS constant to prevent direct file access
 * 5. Follow the naming convention: pluginname_tablename for database tables
 * 6. Use the $plugin_db_tables array in variable.php for ALL plugin tables
 * 7. Include index.html files in all directories for security
 * 8. Handle both GET and POST requests safely
 * 9. **CSRF Protection (CRITICAL): ALL forms must include csrf_token hidden field**
 * 10. **CSRF Verification: ALWAYS verify token in POST handlers with verify_csrf_token()**
 * 11. Provide clear error messages and validation
 * 12. Use Smarty templates for consistent UI
 * 13. Clean up all resources when plugin is deactivated
 *
 * CSRF TOKEN EXAMPLE:
 * Template: <input type="hidden" name="csrf_token" value="{$csrf_token}">
 * Handler:  if (!verify_csrf_token($_POST['csrf_token'])) die("CSRF failed");
 *
 * ============================================================================
 */
