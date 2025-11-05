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

## Important Files

### `variable.php` (Required)

Defines plugin metadata:

```php
<?php
if (!defined('IN_CMS')) die("ERROR - Hacking attempt");

$plugin_db_tables = array("myplugin_data");
$plugin_name = "My Plugin";  // Display name in admin
$plugin_description = "Does cool stuff";
$plugin_version = "1.0.0";
$plugin_folder = "myplugin";  // Must match folder name
?>
```

**Critical**: `$plugin_folder` must exactly match the directory name!

### `sql/install.sql` (Required if plugin uses database)

Creates plugin tables:

```sql
CREATE TABLE IF NOT EXISTS `flintmancms_myplugin_data` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

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
