-- Migration 000001: Update version to 2.0.0
-- Description: Mark database as version 2.0.0 after schema improvements
-- Date: 2025-11-05

UPDATE `flintmancms_version`
SET `version_number` = '2.0.0',
    `version_desc` = 'Version 2.0.0'
WHERE `version_number` = '1.0.3';
