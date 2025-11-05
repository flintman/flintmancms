-- FlintmanCMS Improved Database Schema

-- Configuration table
CREATE TABLE IF NOT EXISTS `flintmancms_config` (
  `name` varchar(191) NOT NULL,
  `value` varchar(191) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `flintmancms_config` (`name`, `value`) VALUES
('site_name', 'Title Here'),
('template', 'basic'),
('maintain', '0'),
('meta_tags', ''),
('email_errors', '0'),
('email_admin', 'admin@yourdomain.com'),
('disclamer', ''),
('debug', '0'),
('allow_login', '0'),
('default_priv', '3'),
('language', 'english'),
('sendviaSTMP', '0'),
('SMTP_host' , ''),
('SMTP_hostport', ''),
('SMTP_user',''),
('SMTP_pass',''),
('SMTP_encryption', 'none'),
('maintain_message', 'This Site is under Construction'),
('frt_page', 'index.php?n=page&page_id=1'),
('add_link', '#scroll');

-- Groups table
CREATE TABLE IF NOT EXISTS `flintmancms_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_group_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `flintmancms_groups` (`id`, `name`, `description`) VALUES
(1, 'Admin', 'Full administrative access'),
(2, 'Anonymous', 'Unauthenticated users'),
(3, 'User', 'Authenticated regular users');

-- Group links table (with foreign key constraints)
CREATE TABLE IF NOT EXISTS `flintmancms_group_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `type_id` int(11) NOT NULL,
  `link_id` int(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_type` (`type`),
  KEY `idx_link_id` (`link_id`),
  CONSTRAINT `fk_group_links_group`
    FOREIGN KEY (`group_id`) REFERENCES `flintmancms_groups`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Links table
CREATE TABLE IF NOT EXISTS `flintmancms_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link` varchar(500) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `new_window` tinyint(1) NOT NULL DEFAULT 0,
  `link_order` int(11) NOT NULL DEFAULT 0,
  `sub_link` int(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`),
  KEY `idx_link_order` (`link_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `flintmancms_links` (`name`, `link`, `active`, `new_window`, `link_order`, `sub_link`) VALUES
('Photo Album', 'index.php?n=plugins&p=portfolio', 1, 0, 2, 0);

-- Logs table
CREATE TABLE IF NOT EXISTS `flintmancms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error` text NOT NULL,
  `level` varchar(20) DEFAULT 'info',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `timestamp` varchar(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pages table
CREATE TABLE IF NOT EXISTS `flintmancms_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `context` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `show_title` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`),
  KEY `idx_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `flintmancms_pages` (`title`, `context`, `active`, `show_title`) VALUES
('Front Page', '<p><strong>Welcome to Flintman CMS</strong></p>', 1, 0);

-- Portfolio portfolio table (main portfolio entries)
-- This must come BEFORE portfolio_photos because portfolio_photos references it
CREATE TABLE IF NOT EXISTS `flintmancms_portfolio_portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '',
  `date_taken` varchar(255) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Portfolio photos table
CREATE TABLE IF NOT EXISTS `flintmancms_portfolio_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portfolio_id` int(11) DEFAULT NULL,
  `photo_name` varchar(255) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portfolio_id` (`portfolio_id`),
  CONSTRAINT `fk_portfolio_photos_portfolio`
    FOREIGN KEY (`portfolio_id`) REFERENCES `flintmancms_portfolio_portfolio`(`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugins table
CREATE TABLE IF NOT EXISTS `flintmancms_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `version` varchar(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_plugin_name` (`name`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `flintmancms_plugins` (`name`, `active`, `version`) VALUES
('portfolio', 1, '1.0.0'),
('helloworld', 0, '1.0.0');

-- Profile table (user accounts)
CREATE TABLE IF NOT EXISTS `flintmancms_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sign_date` varchar(45) NOT NULL,
  `permissions` int(11) NOT NULL DEFAULT 3,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(255) DEFAULT NULL,
  `hash` varchar(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_active` (`active`),
  KEY `idx_permissions` (`permissions`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin user (password: admin)
INSERT INTO `flintmancms_profile` (`username`, `password`, `email`, `sign_date`, `permissions`, `active`, `hash`) VALUES
('admin', '$2y$12$Dq3SwceZ/YYMalbA3Pmxo.8Vf6EWFDEFqZwy4ZZD6sudGYN7ClX.2', 'admin@admin.com', NOW(), 1, 1, '');

-- Version table
CREATE TABLE IF NOT EXISTS `flintmancms_version` (
  `version_number` varchar(20) NOT NULL,
  `version_desc` varchar(255) NOT NULL,
  `released_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `flintmancms_version` (`version_number`, `version_desc`) VALUES
('2.0.0', 'Version 2.0.0');
