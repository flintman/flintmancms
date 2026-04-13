-- ============================================================================
-- FlintmanCMS AtlasCMMS Plugin - Database Schema
-- ============================================================================
--
-- PURPOSE:
-- This file creates the database tables for the AtlasCMMS plugin.
-- It is automatically executed when the plugin is activated.
--
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: atlascmms_config
-- Purpose: Stores API configuration and credentials
-- Fields:
--   - id: Unique identifier
--   - api_key: API authentication key
--   - api_url: Base URL for Atlas CMMS API (e.g., http://localhost:8080)
--   - minio_url: URL for MinIO file server
--   - auth_mode: Authentication type (api_key or bearer)
--   - is_active: Enable/disable this configuration
--   - last_tested: When the connection was last verified
--   - created_date: Creation timestamp
--   - updated_date: Last modification timestamp
-- ----------------------------------------------------------------------------
CREATE TABLE `flintmancms_atlascmms_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(500) DEFAULT '',
  `api_url` varchar(500) NOT NULL DEFAULT 'http://localhost:8080',
  `minio_url` varchar(500) DEFAULT '',
  `auth_mode` varchar(20) NOT NULL DEFAULT 'api_key',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_tested` datetime DEFAULT NULL,
  `test_status` varchar(20) DEFAULT 'untested',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AtlasCMMS API Configuration';

-- ----------------------------------------------------------------------------
-- Table: atlascmms_cache
-- Purpose: Cache API responses to reduce server load
-- Fields:
--   - id: Unique identifier
--   - cache_key: Unique key for this cached data
--   - cache_data: Serialized JSON data
--   - cache_type: Type of data (assets, workorders, etc)
--   - expires_at: When this cache entry expires
--   - created_date: Creation timestamp
-- ----------------------------------------------------------------------------
CREATE TABLE `flintmancms_atlascmms_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_data` longtext,
  `cache_type` varchar(50) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cache_key` (`cache_key`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_type` (`cache_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AtlasCMMS Cache Storage';

-- Default empty config - user will fill in their API details in admin
INSERT INTO `flintmancms_atlascmms_config`
  (`api_url`, `minio_url`, `auth_mode`, `is_active`)
VALUES
  ('http://localhost:8080', '', 'api_key', 1);
