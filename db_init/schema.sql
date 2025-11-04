CREATE TABLE IF NOT EXISTS `flintmancms_config` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `flintmancms_config` (`name`, `value`) VALUES
('site_name', 'Title Here'),
('template', 'basic'),
('maintain', '0'),
('meta_tags', ''),
('email_errors', '0'),
('email_admin', 'admin@admin.com'),
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
('maintain_message', 'This Site is under Construction'),
('frt_page', 'index.php?n=page&page_id=1'),
('add_link', '#scroll');

CREATE TABLE IF NOT EXISTS `flintmancms_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `flintmancms_groups` (`id`, `name`) VALUES
(NULL, 'Admin'),
(NULL, 'Anonymous'),
(NULL, 'User');

CREATE TABLE IF NOT EXISTS `flintmancms_group_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `type_id` int(11) NOT NULL,
  `link_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `flintmancms_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `active` int(11) NOT NULL,
  `new_window` int(11) NOT NULL,
  `link_order` int(11) NOT NULL,
  `sub_link`  int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `flintmancms_links` (`id`, `name`, `link`, `active`, `new_window`, `link_order`, `sub_link`) VALUES
(NULL, 'Photo Album', 'index.php?n=plugins&p=portfolio', 1, 0, 2, 0);

CREATE TABLE IF NOT EXISTS `flintmancms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error` text NOT NULL,
  `timestamp` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `flintmancms_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `context` text NOT NULL,
  `active` int(11) NOT NULL,
  `show_title` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `flintmancms_pages` (`id`, `title`, `context`, `active`, `show_title`) VALUES
(NULL, 'Front Page', '<p><strong>Welcome to Flintman CMS</strong></p>', 1, 0);

CREATE TABLE IF NOT EXISTS `flintmancms_portfolio_photos` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `portfolio_id` int(2) DEFAULT NULL,
  `photo_name` varchar(255) DEFAULT '',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `flintmancms_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `active` int(11) NOT NULL,
  `version` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `flintmancms_plugins` (`id`, `name`, `active`, `version`) VALUES
(NULL, 'portfolio', 1, '1.0.0'),
(NULL, 'helloworld', 0, '1.0.0');

CREATE TABLE IF NOT EXISTS `flintmancms_portfolio_portfolio` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '',
  `date_taken` varchar(255) DEFAULT '',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `flintmancms_profile` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(45) NOT NULL,
  `sign_date` varchar(45) NOT NULL,
  `permissions` int(11) NOT NULL,
  `active` int(11) NOT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `hash` varchar(75) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `flintmancms_profile` (`id`, `username`, `password`, `email`, `sign_date`, `permissions`, `active`, `hash`) VALUES
(NULL, 'admin', '$2y$12$Dq3SwceZ/YYMalbA3Pmxo.8Vf6EWFDEFqZwy4ZZD6sudGYN7ClX.2', 'admin@admin.com', NOW(), 1, 1, '');

CREATE TABLE IF NOT EXISTS `flintmancms_version` (
  `version_number` varchar(20) CHARACTER SET latin1 NOT NULL,
  `version_desc` varchar(255) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`version_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `flintmancms_version` (`version_number`, `version_desc`) VALUES
('1.0.3', 'Version 1.0.3');
