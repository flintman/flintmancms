-- Maintenance Tracker Plugin - Database Schema

-- Equipment table (primary and secondary units)
CREATE TABLE IF NOT EXISTS `flintmancms_maintenance_equipment` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `unit_id` INT NOT NULL,
    `equipment_level` INT DEFAULT 1,
    `archived` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_unit_id` (`unit_id`),
    INDEX `idx_equipment_level` (`equipment_level`),
    INDEX `idx_archived` (`archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dynamic questions for equipment
CREATE TABLE IF NOT EXISTS `flintmancms_maintenance_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_level` INT DEFAULT 1,
    `label` VARCHAR(100) NOT NULL,
    `type` ENUM('string','text','number','date','multi_choice') NOT NULL,
    `options` VARCHAR(255) DEFAULT NULL,
    `position` INT NOT NULL DEFAULT 0,
    INDEX `idx_equipment_level` (`equipment_level`),
    INDEX `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Answers to questions for each equipment
CREATE TABLE IF NOT EXISTS `flintmancms_maintenance_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `value` TEXT,
    FOREIGN KEY (`equipment_id`) REFERENCES `flintmancms_maintenance_equipment`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `flintmancms_maintenance_questions`(`id`) ON DELETE CASCADE,
    INDEX `idx_equipment_id` (`equipment_id`),
    INDEX `idx_question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance service records
CREATE TABLE IF NOT EXISTS `flintmancms_maintenance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `secondary_id` INT NULL,
    `pmy_id` INT NULL,
    `type_of_service` VARCHAR(100),
    `description` TEXT,
    `costs_of_parts` DECIMAL(10,2),
    `performed_at` DATE,
    `performed_by` VARCHAR(100),
    `photos` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_secondary_id` (`secondary_id`),
    INDEX `idx_pmy_id` (`pmy_id`),
    INDEX `idx_performed_at` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Plugin configuration
CREATE TABLE IF NOT EXISTS `flintmancms_maintenance_config` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `config_name` VARCHAR(32) NOT NULL UNIQUE,
    `config_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default configuration
INSERT INTO `flintmancms_maintenance_config` (`config_name`, `config_value`) VALUES
    ('primary_unit', 'Primary Unit'),
    ('secondary_unit', 'Secondary Unit'),
    ('columns_to_show', '3')
ON DUPLICATE KEY UPDATE `config_name`=`config_name`;

-- Insert default questions for primary units (level 1)
INSERT INTO `flintmancms_maintenance_questions` (`equipment_level`, `label`, `type`, `options`, `position`) VALUES
    (1, 'Location', 'string', NULL, 1),
    (1, 'Model', 'string', NULL, 2),
    (1, 'Serial Number', 'string', NULL, 3),
    (1, 'Installation Date', 'date', NULL, 4)
ON DUPLICATE KEY UPDATE `label`=`label`;

-- Insert default questions for secondary units (level 2)
INSERT INTO `flintmancms_maintenance_questions` (`equipment_level`, `label`, `type`, `options`, `position`) VALUES
    (2, 'Component Type', 'string', NULL, 1),
    (2, 'Model', 'string', NULL, 2),
    (2, 'Serial Number', 'string', NULL, 3)
ON DUPLICATE KEY UPDATE `label`=`label`;
