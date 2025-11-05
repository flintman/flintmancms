# Database Migration System

## Overview

FlintmanCMS uses a sequential numbered migration system to track and apply database changes. This is Git-friendly, secure, and flexible.

## How It Works

1. **Numbered Files**: Migration files are named `000001.sql`, `000002.sql`, etc.
2. **Tracking Table**: `flintmancms_migrations` tracks which migrations have been applied
3. **Sequential Execution**: Only unapplied migrations run, in numerical order
4. **Transactional**: Each migration runs in a transaction (rolls back on error)
5. **Git-Friendly**: New migrations come via `git pull`, no file deletion needed

## Usage

### Command Line (Recommended)
```bash
# Run pending migrations
php migrate.php

# Check migration status
php migrate.php status
```

### Web Browser (Secure)
```
http://yoursite.com/migrate.php?key=YOUR_SECRET_KEY
```

The `MIGRATION_KEY` must be set in `.env` file for web access.

### Docker
```bash
docker-compose exec web php /var/www/html/migrate.php
```

## Creating New Migrations

### 1. Create Numbered File

```bash
# Find the last migration number
ls migrations/ | tail -1
# If last is 000003.sql, create 000004.sql

# Create new migration
nano migrations/000004.sql
```

### 2. Migration File Format

```sql
-- Migration 000004: Add email verification
-- Description: Adds email_verified field to profile table
-- Date: 2025-11-05

ALTER TABLE `flintmancms_profile`
ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0;

CREATE INDEX `idx_email_verified` ON `flintmancms_profile`(`email_verified`);

-- Multiple statements are fine, separated by semicolons
UPDATE `flintmancms_profile`
SET `email_verified` = 1
WHERE `active` = 1;
```

### 3. Test Locally

```bash
# Check what will run
php migrate.php status

# Run migrations
php migrate.php

# Verify
docker-compose exec db mysql -u root -p flintmancms -e "SELECT * FROM flintmancms_migrations"
```

### 4. Commit to Git

```bash
git add migrations/000004.sql
git commit -m "Migration 000004: Add email verification"
git push
```

## Security

### CLI Access
- No authentication required
- Assumes you have server access
- Lock prevents concurrent runs

### Web Access
- **Requires secret key** from .env
- Generate key: `openssl rand -hex 32`
- Add to `.env`: `MIGRATION_KEY=your_generated_key`
- Access: `migrate.php?key=your_generated_key`

### Lock Mechanism
- Prevents concurrent migrations
- Lock file: `templates_c/.migration_lock`
- Auto-expires after 5 minutes if process crashes

## Migration Tracking

The `flintmancms_migrations` table tracks applied migrations:

```sql
SELECT * FROM flintmancms_migrations ORDER BY applied_at DESC;
```

Output:
```
+----+-------------+---------------------+
| id | migration   | applied_at          |
+----+-------------+---------------------+
|  3 | 000003.sql  | 2025-11-05 14:30:00 |
|  2 | 000002.sql  | 2025-11-04 09:15:00 |
|  1 | 000001.sql  | 2025-11-03 12:00:00 |
+----+-------------+---------------------+
```

## Best Practices

### ✅ DO

- **Number sequentially**: 000001, 000002, 000003...
- **Include comments**: Describe what the migration does
- **Test locally first**: Before committing to Git
- **Use transactions**: Let the system handle rollback
- **Make idempotent when possible**: Use `ADD COLUMN IF NOT EXISTS` (if supported)
- **One purpose per migration**: Don't mix unrelated changes

### ❌ DON'T

- **Don't modify old migrations**: Create a new one to fix issues
- **Don't skip numbers**: Keep sequential
- **Don't delete applied migrations**: System tracks them
- **Don't mix DDL and DML excessively**: Can cause issues in transactions

## Example Workflow

### Developer adds new feature requiring DB changes:

```bash
# 1. Create migration
echo "-- Migration 000005: Add user avatars
ALTER TABLE flintmancms_profile ADD COLUMN avatar VARCHAR(255) DEFAULT NULL;" > migrations/000005.sql

# 2. Test locally
php migrate.php

# 3. Commit
git add migrations/000005.sql
git commit -m "Migration 000005: Add user avatars"
git push
```

### Production server receives update:

```bash
# 1. Pull changes
git pull origin main

# 2. Run migrations
php migrate.php
# Output: ✅ Applied migration: 000005.sql

# 3. Done! Database updated
```

## Troubleshooting

### "Migration is already running"
Wait 5 minutes or delete lock file:
```bash
rm templates_c/.migration_lock
```

### Migration Failed Mid-Way
The transaction rolled back, so database is safe. Fix the SQL and re-run.

### Need to Manually Mark Migration as Applied
```sql
INSERT INTO flintmancms_migrations (migration) VALUES ('000005.sql');
```

### Need to Skip a Broken Migration
1. Fix the issue manually in database
2. Mark as applied (above query)
3. Or rename the file temporarily

### Check What's Pending
```bash
php migrate.php status
```

Output:
```
ℹ️  === Migration Status ===
✅ Applied migrations: 3
⚠️  Pending migrations: 2

Pending:
⚠️  - 000004.sql
⚠️  - 000005.sql
```

## Comparison with Other Systems

| Feature | This System | Laravel Migrations | Flyway |
|---------|-------------|-------------------|--------|
| Language | SQL only | PHP code | SQL/Java |
| Numbering | Sequential | Timestamp | Version |
| Git-friendly | ✅ Yes | ✅ Yes | ✅ Yes |
| Complexity | Simple | Medium | High |
| Rollback | Manual | Automatic | Manual |
| Web UI | Basic | None | Commercial |

## Advanced: Conditional Migrations

For migrations that might fail if already applied:

```sql
-- Check if column exists before adding
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'flintmancms_profile'
    AND COLUMN_NAME = 'avatar'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE flintmancms_profile ADD COLUMN avatar VARCHAR(255) DEFAULT NULL',
    'SELECT "Column avatar already exists" AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

## Integration with index.php

You can add auto-detection to redirect users to migrations:

```php
// In index.php after common.php
if (file_exists('migrate.php')) {
    $pending = $db->sql_query("SELECT COUNT(*) as cnt FROM flintmancms_migrations");
    $migration_files = glob('../migrations/*.sql');

    if (count($migration_files) > $pending) {
        header("Location: migrate.php?key=" . $_ENV['MIGRATION_KEY']);
        exit;
    }
}
```

## File Structure

```
flintmancms/
├── migrations/              # Migration files directory
│   ├── README.md           # This documentation
│   ├── .gitkeep            # Keeps folder in Git
│   ├── 000001.sql          # First migration
│   ├── 000002.sql          # Second migration
│   └── 000003.sql          # Third migration
├── html/
│   └── migrate.php         # Migration runner script
└── .env                    # Contains MIGRATION_KEY
```

## Summary

✅ **Simple**: Just numbered SQL files
✅ **Secure**: Secret key for web access
✅ **Tracked**: Database table records what's applied
✅ **Git-friendly**: Commit migrations like any code
✅ **Flexible**: Pure SQL, any schema changes
✅ **Safe**: Transactional with rollback
✅ **No cleanup**: Files stay, system tracks state
