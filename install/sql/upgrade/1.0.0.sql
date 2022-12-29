INSERT INTO `prefix_config` (`name`, `value`) VALUES
('frt_page', 'index.php?n=page&page_id=1');
INSERT INTO `prefix_config` (`name`, `value`) VALUES
('add_link', '#scroll');

ALTER TABLE `prefix_links` ADD sub_link int(60) DEFAULT '0';

INSERT INTO `prefix_version` (`version_number`, `version_desc`) VALUES
('1.0.3', 'Version 1.0.3');
