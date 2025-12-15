CREATE TABLE IF NOT EXISTS `flintmancms_taplist_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `api_key` VARCHAR(128) DEFAULT NULL,
    `selected_folders` TEXT DEFAULT NULL,
    `title` VARCHAR(128) DEFAULT NULL,
    `refresh_interval` INT(11) DEFAULT 3600,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
