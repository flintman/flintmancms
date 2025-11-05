<?php
/**
 * Database Configuration
 *
 * This file loads database credentials from environment variables.
 * NEVER commit this file with actual credentials!
 *
 * Environment variables should be set in:
 * - .env file (for Docker)
 * - System environment (for production)
 * - docker-compose.yml (loads from .env)
 */

// Load database configuration from environment variables
Define("DB_NAME", getenv('MYSQL_DATABASE') ?: 'flintmancms');
Define("DB_USER", getenv('MYSQL_USER') ?: 'flintmancms');
Define("DB_PASS", getenv('MYSQL_PASSWORD'));
Define("DB_HOST", getenv('MYSQL_HOST') ?: 'db');

// Security check: Ensure password is set
if (empty(DB_PASS)) {
    error_log("CRITICAL: Database password not configured in environment variables");
    die("Database configuration error. Please check your environment variables.");
}

// Security check: Warn if using default/weak passwords
if (DB_PASS === 'CHANGE_ME_TO_STRONG_PASSWORD' || strlen(DB_PASS) < 16) {
    error_log("WARNING: Weak or default database password detected");
}
