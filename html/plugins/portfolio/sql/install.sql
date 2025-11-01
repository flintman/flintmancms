CREATE TABLE `flintmancms_portfolio_photos` (
  `id` int(2) NOT NULL auto_increment,
  `portfolio_id` int(2) default NULL,
  `photo_name` varchar(255) default '',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

CREATE TABLE `flintmancms_portfolio_portfolio` (
  `id` int(20) NOT NULL auto_increment,
  `name` varchar(255) default '',
  `date_taken` varchar(255) default '',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;