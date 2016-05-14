 CREATE TABLE `debugLog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ipn` int(10) unsigned default '0',
  `userid` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8

CREATE TABLE `debugLogEntries` (
  `id` int(11) NOT NULL auto_increment,
  `debugLogId` int(11) default NULL,
  `elapsed` float default NULL,
  `message` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8

CREATE TABLE `ownedGamesDB` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `steamid` bigint(20) unsigned NOT NULL,
  `appid` int(10) unsigned NOT NULL,
  `minutesTotal` int(10) unsigned default NULL,
  `minutes2Weeks` int(10) unsigned default NULL,
  `gameStatus` tinyint(3) unsigned default '0',
  `minutesTotalStored` int(10) unsigned default '0',
  `gameSlot` tinyint(3) unsigned default NULL,
  `achievPer` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `steamid_2` (`steamid`,`appid`),
  UNIQUE KEY `steamid_3` (`steamid`,`gameSlot`),
  KEY `steamid` (`steamid`),
  KEY `appid` (`appid`),
  KEY `stats_played` (`minutesTotal`,`appid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `statsDB` (
  `category` tinyint(3) unsigned NOT NULL,
  `appid` int(10) unsigned NOT NULL,
  `value` int(10) unsigned default '0',
  `id` smallint(5) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `steamAPIUsage` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `module` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `steamGameDB` (
  `appid` int(10) unsigned NOT NULL,
  `name` varchar(255) default NULL,
  `community` tinyint(1) NOT NULL,
  `logo` char(40) NOT NULL,
  `icon` char(40) NOT NULL,
  `owned` int(10) unsigned NOT NULL default '0',
  `beaten` int(10) unsigned default '0',
  `blacklisted` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`appid`),
  KEY `logo_icon` (`logo`,`icon`),
  KEY `index_owned` (`owned`),
  KEY `index_beaten` (`beaten`),
  KEY `index_blacklisted` (`blacklisted`),
  KEY `index_ownedbeaten` (`owned`,`beaten`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

CREATE TABLE `steamUserDB` (
  `steamid` bigint(20) unsigned NOT NULL,
  `personaname` varchar(255) default NULL,
  `personastate` tinyint(3) unsigned default NULL,
  `points` int(10) unsigned default '0',
  `lastUpdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `authSalt` binary(16) NOT NULL,
  `profileurl` varchar(255) NOT NULL,
  `avatar` char(40) default NULL,
  `profilestate` tinyint(4) NOT NULL,
  `toBeatNum` tinyint(3) unsigned NOT NULL default '5',
  `considerBeaten` tinyint(3) unsigned default '0',
  `hideQuickStats` tinyint(3) unsigned default '0',
  `gameName` varchar(255) default NULL,
  `hideAccountStats` tinyint(3) unsigned default '0',
  `hideSocial` tinyint(3) unsigned default '0',
  `private` tinyint(4) default '0',
  PRIMARY KEY  (`steamid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

CREATE TABLE `userLog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ipn` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `userid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
