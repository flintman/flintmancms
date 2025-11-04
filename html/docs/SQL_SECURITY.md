# SQL Security in FlintmanCMS

## Current Approach: mysqli + quote_smart()

FlintmanCMS uses a **secure and battle-tested** approach to prevent SQL injection:

1. **mysqli** extension (modern replacement for deprecated mysql)
2. **quote_smart()** function (wraps `mysqli_real_escape_string()`)
3. **sprintf()** for parameterized query building
4. **UTF-8 charset** to prevent encoding-based attacks

## Is This Secure?

**YES.** When used consistently, this approach provides adequate protection against SQL injection.

### Why It Works:

- `mysqli_real_escape_string()` properly escapes special characters
- `sprintf()` provides clear parameter separation
- Pattern is enforced throughout the codebase (100+ usage locations)
- UTF-8 charset prevents multi-byte encoding exploits

## Usage Pattern

### The Standard Pattern (ALWAYS use this):

```php
// Single parameter
$sql = sprintf("SELECT * FROM flintmancms_table WHERE id=%s",
    quote_smart($id));

// Multiple parameters
$sql = sprintf("INSERT INTO flintmancms_users (name, email, status) VALUES (%s, %s, %s)",
    quote_smart($name),
    quote_smart($email),
    quote_smart($status));

// UPDATE
$sql = sprintf("UPDATE flintmancms_table SET name=%s, email=%s WHERE id=%s",
    quote_smart($name),
    quote_smart($email),
    quote_smart($id));
```

### What quote_smart() Does:

```php
function quote_smart($value, $nullify = false) {
    // Handle NULL values
    if (!isset($value) || is_null($value) || $value === "") {
        return $nullify ? "NULL" : "''";
    }

    // Handle strings (escape and quote)
    if (is_string($value)) {
        global $db;
        $escaped = $db->db_connect_id->real_escape_string($value);
        return "'" . $escaped . "'";
    }

    // Handle numbers (no quotes)
    return is_numeric($value) ? $value : "'ERROR'";
}
```

## Common Mistakes to AVOID

### ❌ WRONG - Direct Concatenation:
```php
// NEVER DO THIS - SQL Injection vulnerability!
$sql = "SELECT * FROM users WHERE id=" . $_GET['id'];
$sql = "SELECT * FROM users WHERE name='" . $_POST['name'] . "'";
$sql = "DELETE FROM users WHERE id=" . $id;
```

### ✅ CORRECT - Always Use quote_smart():
```php
$sql = sprintf("SELECT * FROM flintmancms_users WHERE id=%s",
    quote_smart($id));

$sql = sprintf("SELECT * FROM flintmancms_users WHERE name=%s",
    quote_smart($name));

$sql = sprintf("DELETE FROM flintmancms_users WHERE id=%s",
    quote_smart($id));
```

## New Feature: Prepared Statements (Optional)

For **new code**, you can optionally use the prepared statement wrapper:

### Basic Usage:

```php
// Single parameter
$result = $db->sql_prepare(
    "SELECT * FROM flintmancms_users WHERE id = ?",
    [$id]
);
$data = $result->fetch_assoc();

// Multiple parameters
$result = $db->sql_prepare(
    "SELECT * FROM flintmancms_users WHERE email = ? AND status = ?",
    [$email, $status]
);

// INSERT
$result = $db->sql_prepare(
    "INSERT INTO flintmancms_users (name, email) VALUES (?, ?)",
    [$name, $email]
);
$new_id = $db->sql_nextid();

// UPDATE
$result = $db->sql_prepare(
    "UPDATE flintmancms_users SET name = ?, email = ? WHERE id = ?",
    [$name, $email, $id]
);
```

### Benefits of Prepared Statements:

✅ **Slightly more secure** - True parameter binding (no string escaping)
✅ **Better performance** - Query plan cached for repeated queries
✅ **Cleaner syntax** - No quote_smart() wrapping needed
✅ **Type safety** - Automatic type detection (integer, string, float)

### When to Use Each:

| Scenario | Recommendation |
|----------|---------------|
| Modifying existing code | Keep using `quote_smart()` - don't break working code |
| New plugin/feature | Your choice - both are secure |
| Complex queries | `sql_prepare()` may be cleaner |
| Simple queries | `quote_smart()` is fine and consistent |
| High-volume queries | `sql_prepare()` has performance advantage |

## Security Enhancements (Recently Added)

### 1. UTF-8 Charset Protection:
```php
// Set in mysql.php constructor
$this->db_connect_id->set_charset('utf8mb4');
```
Prevents encoding-based SQL injection attacks.

### 2. Error Logging (Not User Exposure):
```php
// Errors logged securely, not shown to users
error_log('SQL Query Error: ' . $this->db_connect_id->error);
```
Prevents information disclosure while maintaining debugging capability.

### 3. Connection Error Handling:
```php
// Generic error message for users, detailed log for admins
error_log('Database Connection Error: ' . $error);
die('Database connection failed. Please contact the administrator.');
```

## Security Testing

### Test for SQL Injection:

Try these payloads in form inputs:
- `' OR '1'='1`
- `1; DROP TABLE users--`
- `' UNION SELECT * FROM users--`
- `admin'--`

**Expected Result:** All should be safely escaped and treated as literal strings.

### Verification:

```php
// This input: ' OR '1'='1
// Becomes: '\' OR \'1\'=\'1'
// SQL sees: WHERE name='\' OR \'1\'=\'1'' (literal string, not injection)
```

## Migration Plan (Future Consideration)

**NOT RECOMMENDED** to migrate existing code unless:
- You're doing a major refactor anyway
- You need the performance improvement
- You want to modernize a specific module

**IF you migrate:**
1. Do it module-by-module, not all at once
2. Test thoroughly (100+ query locations)
3. Keep `quote_smart()` available for backward compatibility
4. Update plugin documentation

## Conclusion

**Current Status:** ✅ SECURE
**Action Required:** ❌ NONE (optional enhancement available)
**Priority:** 🟢 LOW (current approach is adequate)

The `quote_smart()` + `sprintf()` pattern is:
- ✅ Secure when used consistently
- ✅ Well-established throughout codebase
- ✅ Familiar to developers
- ✅ Easy to audit and review

The new `sql_prepare()` method is available as an **optional enhancement** for developers who prefer prepared statements, but **migration is NOT required** for security.

## Best Practices Summary

1. **ALWAYS** use `quote_smart()` or `sql_prepare()` - never concatenate user input
2. **NEVER** expose SQL errors to users (log them instead)
3. **ALWAYS** validate input types before queries (use `scrub_input()`)
4. **NEVER** trust user input, even from admin users
5. **ALWAYS** test with SQL injection payloads
6. **USE** prepared statements (`sql_prepare()`) for new high-security features if desired
7. **KEEP** using `quote_smart()` for consistency with existing code

---

**Last Updated:** November 2025
**Security Status:** ✅ ADEQUATE - Current approach is secure
