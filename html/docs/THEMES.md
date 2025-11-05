# FlintmanCMS Theme Development Guide

## Table of Contents
1. [Quick Start](#quick-start)
2. [Theme Structure](#theme-structure)
3. [Required Files](#required-files)
4. [Template Variables](#template-variables)
5. [Smarty Template Syntax](#smarty-template-syntax)
6. [Frontend Templates](#frontend-templates)
7. [Admin Templates](#admin-templates)
8. [Plugin Templates](#plugin-templates)
9. [Styling Your Theme](#styling-your-theme)
10. [Best Practices](#best-practices)
11. [Common Patterns](#common-patterns)
12. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Creating Your First Theme

1. **Create the theme directory structure:**
```bash
cd html/templates/
mkdir mytheme
cd mytheme
mkdir admin plugins style images
```

2. **Create the required files:**
```bash
# Frontend templates
touch header.htm footer.htm page.htm error.htm maintain.htm profile.htm register.htm

# Admin templates
touch admin/admin.htm admin/config.htm admin/email.htm admin/groups.htm
touch admin/help.htm admin/links.htm admin/logs.htm admin/page.htm
touch admin/plugins.htm admin/user.htm admin/user_add-edit.htm

# Plugin template
touch plugins/plugins.htm

# Stylesheets
touch style/style.css style/menu.css
```

3. **Copy content from an existing theme** (Aurora or Basic) and modify to your needs.

4. **Activate your theme** in Admin → Configuration → Template dropdown.

---

## Theme Structure

Every theme must follow this exact directory structure:

```
mytheme/
├── header.htm              # Site header and navigation
├── footer.htm              # Site footer and closing HTML
├── page.htm                # Main content wrapper
├── error.htm               # Error display page
├── maintain.htm            # Maintenance mode page
├── profile.htm             # User profile form
├── register.htm            # User registration form
├── admin/                  # Admin interface templates
│   ├── admin.htm          # Admin dashboard
│   ├── config.htm         # Site configuration
│   ├── email.htm          # Email settings
│   ├── groups.htm         # User group management
│   ├── help.htm           # Help documentation
│   ├── links.htm          # Navigation link management
│   ├── logs.htm           # System logs viewer
│   ├── page.htm           # Page management
│   ├── plugins.htm        # Plugin management
│   ├── user.htm           # User management
│   └── user_add-edit.htm  # User add/edit form
├── plugins/                # Plugin display templates
│   └── plugins.htm        # Generic plugin wrapper
├── style/                  # CSS stylesheets
│   ├── style.css          # Main theme styles
│   └── menu.css           # Navigation menu styles
└── images/                 # Theme images and assets
```

**IMPORTANT:** All files are required. Missing files will cause errors.

---

## Required Files

### Core Template Files (Must Have)

#### 1. `header.htm` - Site Header
**Purpose:** Opens HTML document, includes CSS/JS, displays site header and navigation.

**Required Elements:**
- DOCTYPE declaration
- `<head>` with meta tags
- `{$javascript}` - CMS JavaScript includes
- `{$tooltips}` - Tooltip functionality
- `<div id="dhtmltooltip"></div>` - Required for tooltips
- Navigation menu structure
- Opening `<main>` or content wrapper

**Critical Variables:**
- `{$javascript}` - System JavaScript (required first in `<head>`)
- `{$title_text}` - Page title
- `{$meta_tags}` - SEO keywords
- `{$home_page}` - Site description
- `{$tooltips}` - Tooltip scripts
- `{$menu}` - Navigation menu array
- `{$errorMsg}` - Error messages
- `{$successMsg}` - Success messages

**Example:**
```smarty
<!DOCTYPE html>
<html lang="en">
<head>
    {$javascript}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="{$meta_tags}">
    <meta name="description" content="{$home_page}">
    <title>{$title_text}</title>
    <link rel="stylesheet" type="text/css" href="templates/mytheme/style/style.css">
    <link rel="stylesheet" type="text/css" href="templates/mytheme/style/menu.css">
</head>
<body>
    <!-- DO NOT REMOVE OR YOU RISK BREAKING TOOLTIPS -->
    <div id="dhtmltooltip"></div>
    {$tooltips}
    <!-- END TOOLTIP CODE -->

    <header>
        <h1>{$title_text}</h1>
    </header>

    <nav>
        <ul>
            {foreach from=$menu item=menuitem}
            <li>
                <a href="{$menuitem.link}"{if $menuitem.new_window} target="_blank"{/if}>
                    {$menuitem.text}
                </a>
                {if isset($menuitem.submenu) && $menuitem.submenu|@count > 0}
                <ul class="submenu">
                    {foreach from=$menuitem.submenu item=subitem}
                    <li>
                        <a href="{$subitem.link}"{if $subitem.new_window} target="_blank"{/if}>
                            {$subitem.text}
                        </a>
                    </li>
                    {/foreach}
                </ul>
                {/if}
            </li>
            {/foreach}
        </ul>
    </nav>

    <main>
        {if $errorMsg}
        <div class="error">{$errorMsg}</div>
        {/if}
        {if $successMsg}
        <div class="success">{$successMsg}</div>
        {/if}
```

#### 2. `footer.htm` - Site Footer
**Purpose:** Closes HTML document, includes footer content and JavaScript.

**Required Elements:**
- Closing `</main>` or content wrapper
- Footer content
- Closing `</body>` and `</html>` tags

**Critical Variables:**
- `{$footer_links}` - Login/logout/register links
- `{$flint_version}` - CMS version number
- `{$smarty.now|date_format:"%Y"}` - Current year

**Example:**
```smarty
    </main>

    <footer>
        <p>&copy; {$smarty.now|date_format:"%Y"}. Powered by FlintmanCMS {$flint_version}</p>
        <div class="footer-links">
            {$footer_links}
        </div>
    </footer>
</body>
</html>
```

#### 3. `page.htm` - Content Page Wrapper
**Purpose:** Displays regular page content.

**Variables:**
- `{$title}` - Page title
- `{$content}` - Page content (HTML)

**Example:**
```smarty
<div class="page">
    <h1>{$title}</h1>
    <div class="content">
        {$content}
    </div>
</div>
```

#### 4. `error.htm` - Error Display
**Purpose:** Shows error messages.

**Variables:**
- `{$errorMsg}` - Error message text

**Example:**
```smarty
<div class="error-page">
    <h1>Error</h1>
    <div class="error-message">
        {$errorMsg}
    </div>
    <a href="index.php">Return Home</a>
</div>
```

#### 5. `maintain.htm` - Maintenance Mode
**Purpose:** Displayed when site is in maintenance mode.

**Variables:**
- `{$maintain_msg}` - Maintenance message

**Example:**
```smarty
<div class="maintenance">
    <h1>Site Under Maintenance</h1>
    <p>{$maintain_msg}</p>
</div>
```

#### 6. `profile.htm` - User Profile Form
**Purpose:** User profile editing form.

**Variables:**
- `{$username}` - Username (readonly)
- `{$email}` - User email
- `{$csrf_token}` - CSRF protection token

**Example:**
```smarty
<div class="profile-form">
    <h1>Edit Profile</h1>
    <form action="index.php?n=profile" method="post">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">

        <label>Username</label>
        <input type="text" name="username" value="{$username}" readonly>

        <label>Email</label>
        <input type="email" name="email" value="{$email}" required>

        <label>New Password (leave blank to keep current)</label>
        <input type="password" name="password">

        <label>Confirm Password</label>
        <input type="password" name="password_confirm">

        <button type="submit">Update Profile</button>
        <a href="index.php">Cancel</a>
    </form>
</div>
```

#### 7. `register.htm` - Registration Form
**Purpose:** New user registration form.

**Variables:**
- `{$registration_enabled}` - Boolean if registration is allowed
- `{$csrf_token}` - CSRF protection token

**Example:**
```smarty
{if $registration_enabled}
<div class="register-form">
    <h1>Register</h1>
    <form action="index.php?n=register" method="post">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">

        <label>Username (min 3 characters)</label>
        <input type="text" name="username" minlength="3" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password (min 8 characters)</label>
        <input type="password" name="password" minlength="8" required>

        <label>Confirm Password</label>
        <input type="password" name="password_confirm" required>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="admin.php">Login</a></p>
</div>
{else}
<div class="message">
    <p>Registration is currently disabled.</p>
    <a href="index.php">Return Home</a>
</div>
{/if}
```

---

## Admin Templates

All admin templates follow similar patterns. The CMS generates form content and you provide the wrapper.

### General Admin Template Pattern

Most admin templates use this structure:

```smarty
<div class="admin-section">
    <h1>{LANGUAGE_CONSTANT|default:"Fallback Title"}</h1>

    <form action="admin.php?n=section" method="post">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        {$content}

        <div class="form-actions">
            {$save_button}
            {$section_back}
        </div>
    </form>

    {* Optional: Display existing items *}
    <div class="data-table">
        {$items}
    </div>
</div>
```

### Admin Template Files

#### `admin/admin.htm` - Admin Dashboard
**Variables:**
- `{$version_check_text}` - Version check section title
- `{$version_info}` - Version information HTML
- `{$flint_stats_text}` - Statistics section title
- `{$site_info}` - Site statistics HTML
- `{$flint_log_text}` - Logs section title
- `{$log_info}` - Recent logs HTML
- `{$flint_version_text}` - Version changes section title
- `{$version_changes}` - Version history HTML

**Example:**
```smarty
<div class="dashboard">
    <h2>{$version_check_text}</h2>
    {$version_info}

    <h2>{$flint_stats_text}</h2>
    {$site_info}

    <h2>{$flint_log_text}</h2>
    {$log_info}

    <h2>{$flint_version_text}</h2>
    {$version_changes}
</div>
```

#### `admin/config.htm` - Site Configuration
**Form Action:** `admin.php?n=config`

**Variables:**
- `{ADMIN_CONFIG_HEADER}` - Page title
- `{$csrf_token}` - CSRF token
- `{$content}` - Form fields (generated by CMS)
- `{$save_button}` - Submit button
- `{$config_back}` - Back link

**Example:**
```smarty
<div class="config">
    <h1>{ADMIN_CONFIG_HEADER|default:"Site Configuration"}</h1>
    <form action="admin.php?n=config" method="post">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        {$content}
        <div class="actions">
            {$save_button}
            {$config_back}
        </div>
    </form>
</div>
```

#### `admin/email.htm` - Email Settings
**Form Action:** `admin.php?n=email`

#### `admin/groups.htm` - User Groups
**Form Action:** `admin.php?n=groups`
**Variables:** Also includes `{$groups}` for displaying existing groups

#### `admin/help.htm` - Help Documentation
**Variables:**
- `{$content}` - Help content HTML
- `{$help_back}` - Back link (no form needed)

#### `admin/links.htm` - Navigation Links
**Form Action:** `admin.php?n=links`
**Variables:** Also includes `{$links}` for displaying existing links

#### `admin/logs.htm` - System Logs
**Variables:**
- `{$logs}` - Logs table HTML
- `{$logs_back}` - Back link (no form needed)

#### `admin/page.htm` - Page Management
**Form Action:** `admin.php?n=page`
**Variables:** Also includes `{$pages}` for displaying existing pages

#### `admin/plugins.htm` - Plugin Management
**Variables:**
- `{$active_plugins}` - Active plugins table HTML
- `{$inactive_plugins}` - Inactive plugins table HTML
- `{$content}` - Additional content
- `{$plugins_back}` - Back link

**Example:**
```smarty
<div class="plugins">
    <h1>Plugin Management</h1>

    <p><strong>To add a new plugin:</strong> Copy the plugin folder to
    <code>html/plugins/</code> and refresh this page.</p>

    <h2>Active Plugins</h2>
    {$active_plugins}

    <h2>Inactive Plugins</h2>
    {$inactive_plugins}

    {$content}

    <div class="actions">
        {$plugins_back}
    </div>
</div>
```

#### `admin/user.htm` - User Management
**Form Action:** `admin.php?n=user`
**Variables:** Also includes `{$users}` for displaying user list

#### `admin/user_add-edit.htm` - User Add/Edit Form
**Form Action:** `admin.php?n=user&action=edit` or `admin.php?n=user&action=add`

**Variables:**
- `{$edit_mode}` - Boolean for edit vs add mode
- `{$content}` - Form fields
- `{$save_button}` - Submit button
- `{$user_back}` - Back link

**Example:**
```smarty
<div class="user-form">
    <h1>{if $edit_mode}Edit User{else}Add User{/if}</h1>
    <form action="admin.php?n=user" method="post">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        {$content}
        <div class="actions">
            {$save_button}
            {$user_back}
        </div>
    </form>
</div>
```

---

## Plugin Templates

#### `plugins/plugins.htm` - Plugin Display Wrapper

**Purpose:** Generic wrapper for plugin content.

**Variables:**
- `{$plugin_title}` - Plugin name
- `{$content}` - Plugin-generated content

**Example:**
```smarty
<div class="plugin-content">
    <h1>{$plugin_title|default:"Plugin"}</h1>
    <div class="plugin-body">
        {$content}
    </div>
</div>
```

---

## Template Variables

### Common Variables Available in All Templates

| Variable | Type | Description |
|----------|------|-------------|
| `{$title_text}` | string | Page/site title |
| `{$meta_tags}` | string | SEO keywords |
| `{$home_page}` | string | Site description |
| `{$javascript}` | string | CMS JavaScript includes |
| `{$tooltips}` | string | Tooltip functionality |
| `{$menu}` | array | Navigation menu items |
| `{$footer_links}` | string | Footer navigation HTML |
| `{$flint_version}` | string | CMS version number |
| `{$csrf_token}` | string | CSRF protection token |
| `{$errorMsg}` | string | Error message (if set) |
| `{$successMsg}` | string | Success message (if set) |
| `{$logged_in}` | boolean | User login status |
| `{$username}` | string | Current username (if logged in) |

### Menu Array Structure

The `{$menu}` variable is an array with this structure:

```php
$menu = [
    [
        'text' => 'Home',
        'link' => 'index.php',
        'new_window' => false,
        'submenu' => [
            ['text' => 'About', 'link' => 'index.php?n=about', 'new_window' => false],
            ['text' => 'Contact', 'link' => 'index.php?n=contact', 'new_window' => false]
        ]
    ],
    // ...
];
```

**Looping Through Menu:**
```smarty
{foreach from=$menu item=menuitem}
    <a href="{$menuitem.link}"{if $menuitem.new_window} target="_blank"{/if}>
        {$menuitem.text}
    </a>
    {if isset($menuitem.submenu) && $menuitem.submenu|@count > 0}
        {foreach from=$menuitem.submenu item=subitem}
            <a href="{$subitem.link}"{if $subitem.new_window} target="_blank"{/if}>
                {$subitem.text}
            </a>
        {/foreach}
    {/if}
{/foreach}
```

---

## Smarty Template Syntax

FlintmanCMS uses Smarty 5 for templating. Here are the most common patterns:

### Variables
```smarty
{$variable}                          {* Output variable *}
{$array.key}                         {* Array access *}
{$object->property}                  {* Object property *}
{$variable|escape}                   {* Escape HTML *}
{$variable|default:"fallback"}       {* Default value *}
```

### Conditionals
```smarty
{if $condition}
    Content
{elseif $other_condition}
    Other content
{else}
    Default content
{/if}

{if isset($variable)}
    Variable is set
{/if}
```

### Loops
```smarty
{foreach from=$array item=item}
    {$item}
{/foreach}

{foreach from=$array key=key item=value}
    {$key}: {$value}
{/foreach}
```

### Filters/Modifiers
```smarty
{$date|date_format:"%Y-%m-%d"}      {* Format date *}
{$text|escape}                       {* HTML escape *}
{$text|upper}                        {* Uppercase *}
{$text|lower}                        {* Lowercase *}
{$array|@count}                      {* Array count *}
{$number|string_format:"%.2f"}       {* Format number *}
```

### Special Variables
```smarty
{$smarty.now}                        {* Current timestamp *}
{$smarty.now|date_format:"%Y"}       {* Current year *}
{$smarty.get.param}                  {* GET parameter *}
{$smarty.post.param}                 {* POST parameter *}
{$smarty.session.variable}           {* Session variable *}
```

### Comments
```smarty
{* This is a comment *}

{*
    Multi-line
    comment
*}
```

---

## Styling Your Theme

### CSS Organization

Create two main CSS files:

1. **`style/style.css`** - Main theme styles
2. **`style/menu.css`** - Navigation menu styles

### Recommended CSS Structure

**style.css:**
```css
/* ============================================================================
 * CSS VARIABLES
 * ============================================================================ */
:root {
    --primary-color: #007bff;
    --primary-dark: #0056b3;
    --text-color: #333;
    --bg-color: #fff;
    --border-color: #ddd;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --font-size-base: 16px;
    --border-radius: 4px;
}

/* ============================================================================
 * RESET & BASE STYLES
 * ============================================================================ */
* {
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: var(--font-size-base);
    line-height: 1.6;
    color: var(--text-color);
    background-color: var(--bg-color);
    margin: 0;
    padding: 0;
}

/* ============================================================================
 * LAYOUT
 * ============================================================================ */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-md);
}

/* ============================================================================
 * COMPONENTS (buttons, forms, tables, etc.)
 * ============================================================================ */

/* ... */
```

### Mobile-First Approach

Always design for mobile first, then add desktop styles:

```css
/* Mobile styles (default) */
.nav-menu {
    display: none;
    flex-direction: column;
}

/* Tablet styles */
@media (min-width: 768px) {
    .nav-menu {
        display: flex;
        flex-direction: row;
    }
}

/* Desktop styles */
@media (min-width: 1024px) {
    .nav-menu {
        /* Enhanced desktop styles */
    }
}
```

### Common Breakpoints
- **Mobile:** 320px - 767px (default)
- **Tablet:** 768px - 1023px
- **Desktop:** 1024px+

---

## Best Practices

### 1. **Always Include Required Elements**
- `{$javascript}` must be first in `<head>`
- Tooltip div and `{$tooltips}` are required
- CSRF tokens in all forms
- Proper HTML5 semantic structure

### 2. **Use Language Constants with Fallbacks**
```smarty
{LANGUAGE_CONSTANT|default:"English Fallback"}
```

### 3. **Validate User Input**
```smarty
<input type="email" name="email" value="{$email|escape}" required>
```

### 4. **Mobile-First Design**
- Start with mobile styles
- Use `min-width` media queries
- Test on multiple devices

### 5. **Accessible Navigation**
- Use semantic HTML (`<nav>`, `<ul>`, `<li>`)
- Include `aria-label` attributes
- Support keyboard navigation
- Ensure focus states are visible

### 6. **Performance**
- Minimize CSS/JS file sizes
- Use CSS variables for theming
- Optimize images
- Avoid inline styles

### 7. **Consistent Naming**
- Use clear, descriptive class names
- Follow a naming convention (BEM, SMACSS, etc.)
- Prefix theme-specific classes

### 8. **Form Security**
```smarty
<form action="admin.php?n=section" method="post">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {* Form fields *}
</form>
```

### 9. **Error Handling**
```smarty
{if $errorMsg}
    <div class="alert alert-danger">{$errorMsg}</div>
{/if}

{if $successMsg}
    <div class="alert alert-success">{$successMsg}</div>
{/if}
```

### 10. **Cross-Browser Compatibility**
- Test in Chrome, Firefox, Safari, Edge
- Use vendor prefixes when needed
- Provide fallbacks for CSS features

---

## Common Patterns

### Pattern 1: Responsive Navigation with Submenus

**HTML (header.htm):**
```smarty
<nav>
    <button class="menu-toggle" id="menuToggle">☰</button>
    <ul class="nav-menu" id="navMenu">
        {foreach from=$menu item=menuitem}
        <li class="{if isset($menuitem.submenu) && $menuitem.submenu|@count > 0}has-submenu{/if}">
            <a href="{$menuitem.link}"{if $menuitem.new_window} target="_blank"{/if}>
                {$menuitem.text}
            </a>
            {if isset($menuitem.submenu) && $menuitem.submenu|@count > 0}
            <ul class="submenu">
                {foreach from=$menuitem.submenu item=subitem}
                <li>
                    <a href="{$subitem.link}"{if $subitem.new_window} target="_blank"{/if}>
                        {$subitem.text}
                    </a>
                </li>
                {/foreach}
            </ul>
            {/if}
        </li>
        {/foreach}
    </ul>
</nav>
```

**CSS (menu.css):**
```css
/* Mobile: Hidden by default */
.nav-menu {
    display: none;
}

.nav-menu.active {
    display: block;
}

/* Desktop: Always visible */
@media (min-width: 768px) {
    .menu-toggle {
        display: none;
    }

    .nav-menu {
        display: flex;
    }

    .submenu {
        display: none;
        position: absolute;
    }

    .has-submenu:hover .submenu {
        display: block;
    }
}
```

**JavaScript (footer.htm):**
```javascript
<script>
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('navMenu').classList.toggle('active');
});
</script>
```

### Pattern 2: Data Tables with Overflow

```smarty
<div style="overflow-x: auto;">
    {$data_table}
</div>
```

**CSS:**
```css
@media (max-width: 767px) {
    table {
        font-size: 0.875rem;
    }

    td, th {
        padding: 0.5rem;
    }
}
```

### Pattern 3: Form with Validation

```smarty
<form action="admin.php?n=section" method="post" onsubmit="return validateForm()">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">

    <div class="form-group">
        <label for="email">Email*</label>
        <input type="email" id="email" name="email" value="{$email|escape}" required>
    </div>

    <div class="form-group">
        <label for="password">Password (min 8 characters)*</label>
        <input type="password" id="password" name="password" minlength="8" required>
    </div>

    <div class="form-actions">
        <button type="submit">Save</button>
        <a href="admin.php">Cancel</a>
    </div>
</form>
```

### Pattern 4: Conditional Content Display

```smarty
{if $logged_in}
    <a href="index.php?n=profile">Profile</a>
    <a href="index.php?logout=true">Logout</a>
{else}
    <a href="admin.php">Login</a>
    {if $registration_enabled}
        <a href="index.php?n=register">Register</a>
    {/if}
{/if}
```

### Pattern 5: Cards/Content Boxes

```smarty
<div class="content-box">
    <h2>{$title}</h2>
    <div class="content-body">
        {$content}
    </div>
</div>
```

**CSS:**
```css
.content-box {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

---

## Troubleshooting

### Issue: "Trying to access array offset on value of type null"

**Cause:** Trying to use a variable that doesn't exist or is null.

**Solution:** Always check if variables exist and provide defaults:
```smarty
{if isset($variable)}
    {$variable}
{else}
    Default value
{/if}

{* Or use default filter *}
{$variable|default:"Default value"}
```

### Issue: "Array to string conversion"

**Cause:** Trying to output an array directly instead of looping through it.

**Solution:** Use `{foreach}` for arrays:
```smarty
{* WRONG *}
{$menu}

{* RIGHT *}
{foreach from=$menu item=item}
    {$item.text}
{/foreach}
```

### Issue: Submenu Always Visible

**Cause:** CSS display property not properly set or selector specificity issue.

**Solution:** Ensure submenus are hidden by default:
```css
.submenu {
    display: none !important; /* Force hide */
}

.has-submenu:hover .submenu {
    display: block !important; /* Force show on hover */
}
```

### Issue: Navigation Not Working on Mobile

**Cause:** Missing JavaScript or wrong element IDs.

**Solution:** Verify JavaScript is included and IDs match:
```javascript
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('menuToggle');
    const menu = document.getElementById('navMenu');

    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('active');
        });
    }
});
</script>
```

### Issue: Forms Not Submitting

**Cause:** Missing CSRF token or wrong form action.

**Solution:**
1. Always include CSRF token: `<input type="hidden" name="csrf_token" value="{$csrf_token}">`
2. Use correct form action: `action="admin.php?n=section"`
3. Method should be POST: `method="post"`

### Issue: Styles Not Loading

**Cause:** Wrong path to CSS files.

**Solution:** Use correct relative path:
```html
<link rel="stylesheet" type="text/css" href="templates/mytheme/style/style.css">
```

**Note:** Path is relative to `html/` directory.

### Issue: Tooltips Not Working

**Cause:** Missing required tooltip code in header.

**Solution:** Always include in header.htm:
```smarty
<!-- DO NOT REMOVE OR YOU RISK BREAKING TOOLTIPS -->
<div id="dhtmltooltip"></div>
{$tooltips}
<!-- END TOOLTIP CODE -->
```

### Issue: Admin Pages Show Blank Content

**Cause:** Missing `{$content}` variable in template.

**Solution:** Ensure admin templates include:
```smarty
<form action="admin.php?n=section" method="post">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {$content}  {* THIS IS REQUIRED *}
    <div class="actions">
        {$save_button}
        {$section_back}
    </div>
</form>
```

### Issue: Version Changes Not Displaying

**Cause:** Using wrong variable name or format.

**Solution:** In admin/admin.htm:
```smarty
<div>
    <h2>{$flint_version_text}</h2>
    {$version_changes}  {* Already formatted HTML *}
</div>
```

---

## Testing Your Theme

### Checklist

**Frontend:**
- [ ] Home page displays correctly
- [ ] Navigation menu works on mobile and desktop
- [ ] Submenus show on hover (desktop) and click (mobile)
- [ ] Page content displays properly
- [ ] Error messages display correctly
- [ ] Success messages display correctly
- [ ] Profile form works
- [ ] Registration form works (if enabled)
- [ ] Footer displays correctly
- [ ] Links work properly
- [ ] Forms submit successfully

**Admin Panel:**
- [ ] Admin dashboard displays
- [ ] Configuration page loads and saves
- [ ] Email settings work
- [ ] User management works
- [ ] Plugin management works
- [ ] Page management works
- [ ] All tables display correctly
- [ ] Forms submit properly
- [ ] Back buttons work
- [ ] CSRF tokens work

**Responsive:**
- [ ] Test on mobile (320px - 767px)
- [ ] Test on tablet (768px - 1023px)
- [ ] Test on desktop (1024px+)
- [ ] Menu works on all sizes
- [ ] Tables scroll horizontally on mobile
- [ ] Forms are usable on mobile
- [ ] Touch targets are adequate (min 44x44px)

**Cross-Browser:**
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

**Accessibility:**
- [ ] Keyboard navigation works
- [ ] Focus states visible
- [ ] Proper heading hierarchy
- [ ] ARIA labels on interactive elements
- [ ] Sufficient color contrast

---

## Advanced Topics

### Custom CSS Variables

Define theme-specific variables:

```css
:root {
    --theme-primary: #your-color;
    --theme-secondary: #your-color;
    --theme-accent: #your-color;
}

/* Use throughout theme */
.button {
    background-color: var(--theme-primary);
}
```

### Dark Mode Support

```css
@media (prefers-color-scheme: dark) {
    :root {
        --bg-color: #1a1a1a;
        --text-color: #ffffff;
        --border-color: #333;
    }
}
```

### Adding Custom Fonts

```css
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap');

body {
    font-family: 'Roboto', sans-serif;
}
```

### Print Styles

```css
@media print {
    .no-print {
        display: none;
    }

    body {
        font-size: 12pt;
    }
}
```

---

## Resources

### Reference Templates
- **Aurora** - Modern theme with dark mode toggle
- **Basic** - Simple, table-based layout

### Useful Links
- [Smarty Documentation](https://www.smarty.net/docs/en/)
- [MDN Web Docs](https://developer.mozilla.org/)
- [CSS-Tricks](https://css-tricks.com/)

### Testing Tools
- Chrome DevTools (F12)
- Firefox Developer Tools (F12)
- [Responsive Design Mode](https://developer.mozilla.org/en-US/docs/Tools/Responsive_Design_Mode)
- [WAVE Web Accessibility Tool](https://wave.webaim.org/)

---

## Quick Reference: File Purposes

| File | Purpose | Required Variables |
|------|---------|-------------------|
| `header.htm` | HTML head, site header, navigation, open main | `{$javascript}`, `{$tooltips}`, `{$title_text}`, `{$menu}` |
| `footer.htm` | Close main, footer, close HTML | `{$footer_links}`, `{$flint_version}` |
| `page.htm` | Regular page content | `{$title}`, `{$content}` |
| `error.htm` | Error display | `{$errorMsg}` |
| `maintain.htm` | Maintenance mode | `{$maintain_msg}` |
| `profile.htm` | User profile form | `{$username}`, `{$email}`, `{$csrf_token}` |
| `register.htm` | Registration form | `{$registration_enabled}`, `{$csrf_token}` |
| `admin/admin.htm` | Admin dashboard | `{$version_info}`, `{$site_info}`, `{$log_info}`, `{$version_changes}` |
| `admin/config.htm` | Site configuration | `{$content}`, `{$save_button}`, `{$config_back}` |
| `admin/plugins.htm` | Plugin management | `{$active_plugins}`, `{$inactive_plugins}` |
| `plugins/plugins.htm` | Plugin content wrapper | `{$plugin_title}`, `{$content}` |

---

## Example: Minimal Working Theme

Here's a complete minimal theme to get started:

### header.htm
```smarty
<!DOCTYPE html>
<html>
<head>
    {$javascript}
    <title>{$title_text}</title>
    <link rel="stylesheet" href="templates/minimal/style/style.css">
</head>
<body>
    <div id="dhtmltooltip"></div>
    {$tooltips}
    <header><h1>{$title_text}</h1></header>
    <nav>
        <ul>
        {foreach from=$menu item=menuitem}
            <li><a href="{$menuitem.link}">{$menuitem.text}</a></li>
        {/foreach}
        </ul>
    </nav>
    <main>
    {if $errorMsg}<div class="error">{$errorMsg}</div>{/if}
    {if $successMsg}<div class="success">{$successMsg}</div>{/if}
```

### footer.htm
```smarty
    </main>
    <footer>
        <p>&copy; {$smarty.now|date_format:"%Y"} | {$footer_links}</p>
    </footer>
</body>
</html>
```

### page.htm
```smarty
<h2>{$title}</h2>
<div>{$content}</div>
```

### Other files
Copy from Aurora or Basic theme and modify as needed.

---

## Summary

Creating a theme for FlintmanCMS requires:

1. **Correct directory structure** - All required folders and files
2. **Required template variables** - Use the correct Smarty variables
3. **Proper Smarty syntax** - Loops, conditionals, filters
4. **Responsive CSS** - Mobile-first design with media queries
5. **Accessibility** - Semantic HTML, ARIA labels, keyboard navigation
6. **Security** - CSRF tokens, escaped output
7. **Testing** - Multiple browsers, devices, and screen sizes

Start by copying an existing theme (Aurora recommended) and modify it to match your design. Test thoroughly before deploying to production.

For more examples, study the Aurora and Basic themes included with FlintmanCMS.

---

**Happy Theming! 🎨**
