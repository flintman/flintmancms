-- ============================================================================
-- FlintmanCMS Hello World Plugin - Database Schema
-- ============================================================================
--
-- PURPOSE:
-- This file creates the database tables needed for the Hello World plugin.
-- It is automatically executed when the plugin is activated for the first time.
--
-- IMPORTANT NOTES:
-- 1. Use `flintmancms_` for table names - this is the actual DB_PREFIX
-- 2. Table names must match those in variable.php $plugin_db_tables array
-- 3. Use proper SQL syntax with semicolons at the end of each statement
-- 4. Comments starting with -- are ignored during execution
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: helloworld_messages
-- Purpose: Stores user-submitted hello world messages
-- ----------------------------------------------------------------------------
CREATE TABLE `flintmancms_helloworld_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(100) NOT NULL DEFAULT 'Anonymous',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`),
  KEY `idx_created` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores hello world messages from users';

-- ----------------------------------------------------------------------------
-- Table: helloworld_settings
-- Purpose: Stores plugin configuration settings
-- ----------------------------------------------------------------------------
CREATE TABLE `flintmancms_helloworld_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Plugin configuration settings';

-- ----------------------------------------------------------------------------
-- Insert Default Settings
-- ----------------------------------------------------------------------------
INSERT INTO `flintmancms_helloworld_settings` (`setting_name`, `setting_value`)
VALUES
  ('default_greeting', 'Hello, World!'),
  ('max_messages', '100'),
  ('allow_anonymous', '1');

-- ----------------------------------------------------------------------------
-- Insert Sample Data (Optional - for demonstration)
-- ----------------------------------------------------------------------------
INSERT INTO `flintmancms_helloworld_messages` (`message`, `author`, `created_date`, `active`)
VALUES
  ('Welcome to the Hello World plugin!', 'System', NOW(), 1),
  ('This is an example message', 'Admin', NOW(), 1),
  ('You can add your own messages through the admin panel', 'Developer', NOW(), 1);

-- ============================================================================
-- NOTES FOR DEVELOPERS
-- ============================================================================
--
-- TABLE DESIGN BEST PRACTICES:
--
-- 1. Primary Keys:
--    - Always use AUTO_INCREMENT integer primary key named 'id'
--    - Simplifies relationships and ensures uniqueness
--
-- 2. Indexes:
--    - Add indexes on columns used in WHERE, ORDER BY, JOIN clauses
--    - Use KEY for single columns, add names like idx_columnname
--
-- 3. Character Sets:
--    - Use utf8mb4 for full Unicode support (including emojis)
--    - CHARSET=utf8mb4 ensures compatibility with international characters
--
-- 4. Timestamps:
--    - Use datetime for dates/times
--    - DEFAULT CURRENT_TIMESTAMP for automatic creation time
--    - ON UPDATE CURRENT_TIMESTAMP for automatic modification tracking
--
-- 5. Field Types:
--    - varchar(n) for strings with known max length
--    - text for longer content without specific length limit
--    - tinyint(1) for boolean values (0 = false, 1 = true)
--    - int(11) for integer values
--
-- 6. Default Values:
--    - Always provide sensible defaults where possible
--    - Use NOT NULL with DEFAULT to avoid NULL handling issues
--
-- 7. Table Names:
--    - Must match entries in $plugin_db_tables array in variable.php
--    - Use flintmancms_ which is the actual DB_PREFIX
--    - Follow naming: pluginname_purpose (e.g., helloworld_messages)
--
-- TESTING YOUR SCHEMA:
--
-- 1. Test the SQL locally before deploying
-- 2. Verify all table names match variable.php
-- 3. Check that indexes improve query performance
-- 4. Ensure sample data is appropriate for production use
-- 5. Document any special setup requirements
--
-- ============================================================================
