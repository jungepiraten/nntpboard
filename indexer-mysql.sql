CREATE TABLE IF NOT EXISTS `indexer` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `field` varchar(20) character set utf8 NOT NULL,
  `term` varchar(30) character set utf8 NOT NULL,
  `boardid` varchar(10) character set utf8 NOT NULL,
  `messageid` varchar(120) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `field` (`field`),
  KEY `term` (`term`),
  KEY `boardid` (`boardid`,`messageid`)
) ENGINE=InnoDB;
