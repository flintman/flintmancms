#!/usr/bin/env php
<?php
/**
 * FlintmanCMS Database Migration Runner
 *
 * Runs SQL migration files from the migrations/ folder in sequential order.
 * Tracks which migrations have been applied and only runs new ones.
 *
 * Usage:
 *   CLI: php migrate.php
 *   Web: http://yoursite.com/migrate.php?key=YOUR_SECRET_KEY
 *
 * Security: Set MIGRATION_KEY in .env file
 */

// Detect CLI mode
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Web access - require secret key
    $required_key = $_ENV['MIGRATION_KEY'] ?? getenv('MIGRATION_KEY') ?? null;

    if (!$required_key) {
        die("ERROR: MIGRATION_KEY not configured in .env file");
    }

    if (!isset($_GET['key']) || $_GET['key'] !== $required_key) {
        http_response_code(403);
        die("Access Denied");
    }

    // HTML header
    echo "<!DOCTYPE html><html><head><title>FlintmanCMS Migrations</title>";
    echo "<style>body{font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;}";
    echo ".success{color:#4ec9b0;}.error{color:#f48771;}.info{color:#569cd6;}.warning{color:#dcdcaa;}";
    echo ".migration{background:#252526;padding:10px;margin:10px 0;border-left:3px solid #007acc;}";
    echo "h1{color:#4ec9b0;border-bottom:2px solid #007acc;padding-bottom:10px;}";
    echo "</style></head><body>";
    echo "<h1>🔄 Database Migrations</h1>";
}

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Database configuration
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'name' => $_ENV['DB_NAME'] ?? 'flintmancms',
    'user' => $_ENV['DB_USER'] ?? 'root',
    'pass' => $_ENV['DB_PASS'] ?? ''
];

$migrations_dir = __DIR__ . '/../migrations';
$lock_file = __DIR__ . '/templates_c/.migration_lock';

/**
 * Output formatted message
 */
function output($message, $type = 'info') {
    global $is_cli;

    if ($is_cli) {
        $colors = [
            'error' => "\033[31m",
            'success' => "\033[32m",
            'warning' => "\033[33m",
            'info' => "\033[36m",
            'reset' => "\033[0m"
        ];

        $prefix = match($type) {
            'error' => '❌ ',
            'success' => '✅ ',
            'warning' => '⚠️  ',
            default => 'ℹ️  '
        };

        echo $colors[$type] . $prefix . $message . $colors['reset'] . PHP_EOL;
    } else {
        echo "<div class='{$type}'>{$message}</div>";
    }
}

/**
 * Check if migration is locked
 */
function is_locked() {
    global $lock_file;
    if (file_exists($lock_file)) {
        $lock_time = filemtime($lock_file);
        if (time() - $lock_time < 300) { // 5 minute timeout
            return true;
        }
        @unlink($lock_file);
    }
    return false;
}

/**
 * Create lock
 */
function create_lock() {
    global $lock_file;
    @mkdir(dirname($lock_file), 0755, true);
    file_put_contents($lock_file, time());
}

/**
 * Remove lock
 */
function remove_lock() {
    global $lock_file;
    @unlink($lock_file);
}

/**
 * Get database connection
 */
function get_db() {
    global $db_config;

    try {
        $conn = new mysqli(
            $db_config['host'],
            $db_config['user'],
            $db_config['pass'],
            $db_config['name']
        );

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;

    } catch (Exception $e) {
        output("Database connection failed: " . $e->getMessage(), 'error');
        exit(1);
    }
}

/**
 * Create migrations tracking table if it doesn't exist
 */
function ensure_migrations_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS `flintmancms_migrations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `migration` VARCHAR(255) NOT NULL,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$conn->query($sql)) {
        output("Failed to create migrations table: " . $conn->error, 'error');
        return false;
    }

    return true;
}

/**
 * Get list of applied migrations
 */
function get_applied_migrations($conn) {
    $applied = [];
    $result = $conn->query("SELECT migration FROM flintmancms_migrations ORDER BY migration");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $applied[] = $row['migration'];
        }
    }

    return $applied;
}

/**
 * Get list of available migration files
 */
function get_migration_files() {
    global $migrations_dir;

    if (!is_dir($migrations_dir)) {
        output("Migrations directory not found: {$migrations_dir}", 'error');
        return [];
    }

    $files = glob($migrations_dir . '/*.sql');

    // Sort by filename (numeric order)
    sort($files);

    return array_map('basename', $files);
}

/**
 * Get pending migrations
 */
function get_pending_migrations($conn) {
    $applied = get_applied_migrations($conn);
    $available = get_migration_files();

    return array_diff($available, $applied);
}

/**
 * Apply a single migration file
 */
function apply_migration($conn, $filename) {
    global $migrations_dir;

    $filepath = $migrations_dir . '/' . $filename;

    if (!file_exists($filepath)) {
        output("Migration file not found: {$filename}", 'error');
        return false;
    }

    $sql_content = file_get_contents($filepath);

    if ($sql_content === false) {
        output("Failed to read migration file: {$filename}", 'error');
        return false;
    }

    // Remove comment lines first
    $lines = explode("\n", $sql_content);
    $sql_lines = array_filter($lines, function($line) {
        $line = trim($line);
        return !empty($line) && substr($line, 0, 2) !== '--';
    });
    $clean_sql = implode("\n", $sql_lines);

    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $clean_sql)),
        function($stmt) {
            return !empty(trim($stmt));
        }
    );

    output("Found " . count($statements) . " SQL statement(s) to execute", 'info');    // Begin transaction
    $conn->begin_transaction();

    try {
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                output("Executing: " . substr($statement, 0, 100) . "...", 'info');
                if (!$conn->query($statement)) {
                    throw new Exception("SQL Error: " . $conn->error . "\nStatement: " . substr($statement, 0, 100));
                }
                output("Statement executed successfully", 'success');
            }
        }

        // Record migration as applied
        $stmt = $conn->prepare("INSERT INTO flintmancms_migrations (migration) VALUES (?)");
        $stmt->bind_param('s', $filename);

        if (!$stmt->execute()) {
            throw new Exception("Failed to record migration: " . $conn->error);
        }

        // Commit transaction
        $conn->commit();
        output("Applied migration: {$filename}", 'success');
        return true;

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        output("Failed to apply migration {$filename}: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Run all pending migrations
 */
function run_migrations() {
    output("Starting migration process...", 'info');

    if (is_locked()) {
        output("Migration is already running. Please wait or check lock file.", 'error');
        return false;
    }

    create_lock();

    try {
        // Connect to database
        $conn = get_db();
        output("Connected to database: {$GLOBALS['db_config']['name']}", 'success');

        // Ensure migrations table exists
        if (!ensure_migrations_table($conn)) {
            remove_lock();
            return false;
        }

        // Get pending migrations
        $pending = get_pending_migrations($conn);

        if (empty($pending)) {
            output("No pending migrations. Database is up to date!", 'success');
            remove_lock();
            return true;
        }

        output("Found " . count($pending) . " pending migration(s)", 'info');

        if (!$GLOBALS['is_cli']) {
            echo "<div class='migration'>";
        }

        // Apply each migration
        $applied = 0;
        $failed = 0;

        foreach ($pending as $migration) {
            if (apply_migration($conn, $migration)) {
                $applied++;
            } else {
                $failed++;
                output("Stopping migration process due to failure", 'error');
                break;
            }
        }

        if (!$GLOBALS['is_cli']) {
            echo "</div>";
        }

        // Summary
        output("\n=== Migration Summary ===", 'info');
        output("Applied: {$applied}", 'success');

        if ($failed > 0) {
            output("Failed: {$failed}", 'error');
        }

        $conn->close();
        remove_lock();

        return $failed === 0;

    } catch (Exception $e) {
        output("Fatal error: " . $e->getMessage(), 'error');
        remove_lock();
        return false;
    }
}

/**
 * Show migration status
 */
function show_status() {
    $conn = get_db();

    if (!ensure_migrations_table($conn)) {
        return;
    }

    $applied = get_applied_migrations($conn);
    $available = get_migration_files($conn);
    $pending = array_diff($available, $applied);

    output("\n=== Migration Status ===", 'info');
    output("Applied migrations: " . count($applied), 'success');
    output("Pending migrations: " . count($pending), count($pending) > 0 ? 'warning' : 'success');

    if (!empty($pending)) {
        output("\nPending:", 'warning');
        foreach ($pending as $migration) {
            output("  - {$migration}", 'warning');
        }
    }

    if (!empty($applied) && $GLOBALS['is_cli']) {
        output("\nApplied:", 'success');
        foreach ($applied as $migration) {
            output("  - {$migration}", 'info');
        }
    }

    $conn->close();
}

// Main execution
if ($is_cli && isset($argv[1]) && $argv[1] === 'status') {
    show_status();
} else {
    $success = run_migrations();

    if (!$is_cli) {
        if ($success) {
            echo "<div class='success'><strong>✅ All migrations completed successfully!</strong></div>";
            echo "<div class='info'><a href='index.php' style='color:#569cd6;'>← Back to FlintmanCMS</a></div>";
        } else {
            echo "<div class='error'><strong>❌ Migration failed. Check errors above.</strong></div>";
        }
        echo "</body></html>";
    }

    exit($success ? 0 : 1);
}
