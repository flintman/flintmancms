# FlintmanCMS Plugin System

## Overview

FlintmanCMS features an automatic plugin discovery system that makes it easy to add, update, and manage plugins through Git, FTP, or direct file system access.

## Quick Start

### Installing a Plugin

1. Copy the plugin folder to `html/plugins/`
2. Refresh the admin plugin page
3. The plugin automatically appears in the "Inactive" list
4. Click "Install" to activate it

**That's it!** No zip uploads, no manual database entries required.

### Plugin Structure

```
html/plugins/yourplugin/
├── variable.php          # Required: Plugin metadata
├── yourplugin.php        # Required: Frontend logic
├── index.html            # Security file
├── admin/
│   └── admin.php         # Admin interface
├── sql/
│   └── install.sql       # Database schema
├── template/
│   └── page.htm          # Admin template
├── css/                  # Optional styles
└── js/                   # Optional scripts
```

## How It Works

### Auto-Discovery

Every time you visit the admin plugin page, the system:

1. **Scans** the `html/plugins/` folder for plugin directories
2. **Checks** each folder for a `variable.php` file
3. **Registers** new plugins as "Inactive" in the database
4. **Removes** database entries for deleted plugin folders

This means:
- ✅ Add plugins via Git pull → They appear automatically
- ✅ Copy plugins via FTP → They appear automatically
- ✅ Delete plugin folders → Database cleans up automatically
- ✅ No manual database management required

### Activation Process

When you click "Install" on an inactive plugin:

1. Reads `variable.php` for configuration
2. Checks if database tables exist
3. Runs `sql/install.sql` if tables need creation
4. Adds menu link to navigation
5. Marks plugin as active

### Deactivation Process

When you click "Uninstall" on an active plugin:

1. Optionally drops database tables (you choose)
2. Removes menu link from navigation
3. Marks plugin as inactive

## Git Workflow

The auto-discovery system is designed for Git-based development:

```bash
# Developer creates new plugin
git add html/plugins/myplugin/
git commit -m "Add myplugin"
git push

# Production server
git pull
# Visit admin plugins page - plugin appears automatically!
# Click "Install" to activate
```

## Database Naming

### In `variable.php`

List table names **WITHOUT** the prefix:

```php
$plugin_db_tables = array(
    "myplugin_data",      // Will become flintmancms_myplugin_data
    "myplugin_settings"   // Will become flintmancms_myplugin_settings
);
```

### In `sql/install.sql`

Use **FULL** table names WITH prefix:

```sql
CREATE TABLE IF NOT EXISTS `flintmancms_myplugin_data` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    -- ...
) ENGINE=InnoDB;
```


## Plugin Upgrades (Versioned SQL)

FlintmanCMS supports automatic plugin upgrades using versioned SQL scripts and a one-click update in the admin UI.

### How It Works

1. **Plugin Version Tracking**
    - Each plugin declares its current version in its `variable.php` file, e.g.:
      ```php
      $plugin_version = "1.2.0";
      ```
    - The installed version is tracked in the `flintmancms_plugins` table in the database (column: `version`).

2. **Upgrade SQL Scripts**
    - Place upgrade SQL scripts in the plugin's `sql/updates/` directory.
    - Name each file after the version it upgrades to, e.g.:
      - `sql/updates/1.1.0.sql`
      - `sql/updates/1.2.0.sql`
    - Each file should contain the SQL statements needed to upgrade from the previous version to the named version. Separate multiple statements with semicolons (`;`).

3. **Admin UI**
    - In the admin plugin list, if the code version (from `variable.php`) is newer than the installed version (in the DB), an **Update** button will appear for that plugin.
    - Clicking **Update** will:
      - Run all SQL scripts in `sql/updates/` with a version higher than the installed version, in order.
      - Update the installed version in the database.
      - Show a green success message at the top of the plugin list.

4. **Version Comparison**
    - The system uses PHP's `version_compare()` to determine which updates to run and in what order.
    - Only scripts with a version greater than the installed version and less than or equal to the code version are run.

#### Example Upgrade Workflow

1. You release plugin version 1.2.0. Your `variable.php` contains:
    ```php
    $plugin_version = "1.2.0";
    ```
2. You add a new SQL file: `sql/updates/1.2.0.sql` with the required schema/data changes.
3. The admin visits the plugin page. If their installed version is less than 1.2.0, they see an **Update** button.
4. Clicking **Update** runs all needed SQL scripts and updates the version in the DB.

#### Notes
- Make sure your `flintmancms_plugins` table has a `version` column (`VARCHAR(20)`).
- Always test your SQL scripts before release.
- The update system is designed to be idempotent: each script should be safe to run only once and in order.
- If no update is needed, the button will not appear.

#### Troubleshooting
- If you see errors about missing `version` column, add it:
  ```sql
  ALTER TABLE flintmancms_plugins ADD COLUMN version VARCHAR(20) DEFAULT NULL;
  ```
- If an update fails, check your SQL syntax and ensure all previous updates have been applied.

---

## Security Best Practices

1. **Directory Listing**: Include `index.html` in all directories
2. **Direct Access**: Check `IN_CMS` constant in all PHP files
3. **Input Sanitization**: Use `scrub_input()` for all user input
4. **SQL Injection**: Use `quote_smart()` for all SQL parameters
5. **XSS Protection**: Use `htmlspecialchars()` for all output
6. **CSRF Protection**: Include `csrf_token` in all forms and verify in POST handlers

## Example Plugin

The `helloworld` plugin in `html/plugins/helloworld/` demonstrates:
- Complete CRUD operations
- Admin interface
- Frontend display
- Database integration
- Security best practices

Use it as a template for your own plugins!

## Troubleshooting

### Plugin doesn't appear after copying folder

- Ensure `variable.php` exists and is valid
- Check file permissions (should be readable by web server)
- Refresh the admin plugin page

### "Column count mismatch" errors

- Database schema has changed
- Old plugins may need updates for timestamp columns
- Use explicit column names in INSERT statements

### Plugin appears twice (active and inactive)

- This was a bug, now fixed
- Clear browser cache and refresh

## Migration from Old System

If upgrading from the zip-upload system:

1. Old plugins will continue to work
2. New auto-discovery system is backward compatible
3. Existing plugin database entries remain valid
4. No migration required - just start using the new workflow!
