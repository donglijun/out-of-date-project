USE `mkjogo_streaming`;

-- 赛季信息表 (2016/01/18)
CREATE TABLE IF NOT EXISTS `league_season` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 战队申请参赛表 (2016/01/27)
CREATE TABLE IF NOT EXISTS `league_application` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `leader_name` varchar(255) NOT NULL DEFAULT '',
  `leader_phone` varchar(255) NOT NULL DEFAULT '',
  `leader_phone2` varchar(255) NOT NULL,
  `leader_email` varchar(255) NOT NULL,
  `teams` text NOT NULL,
  `logo` varchar(255) NOT NULL DEFAULT '',
  `video` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `app_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by` bigint(20) unsigned NOT NULL DEFAULT '0',
  `processed_on` int(10) unsigned NOT NULL DEFAULT '0',
  `processed_by` bigint(20) unsigned NOT NULL DEFAULT '0',
  `memo` text NOT NULL,
  `reason` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `season` (`season`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 赛程表 (2016/01/18)
CREATE TABLE IF NOT EXISTS `league_match_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `from` int(10) unsigned NOT NULL DEFAULT '0',
  `to` int(10) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 比赛结果表 (2016/04/14)
CREATE TABLE IF NOT EXISTS `league_match` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season` int(10) unsigned NOT NULL DEFAULT '0',
  `schedule` int(10) unsigned NOT NULL DEFAULT '0',
  `group_tag` varchar(100) NOT NULL DEFAULT '',
  `team1` int(10) unsigned NOT NULL DEFAULT '0',
  `team2` int(10) unsigned NOT NULL DEFAULT '0',
  `winner` int(11) NOT NULL DEFAULT '0',
  `datetime` int(10) unsigned NOT NULL DEFAULT '0',
  `channel` bigint(20) unsigned NOT NULL DEFAULT '0',
  `score_data` text NOT NULL,
  `video_data` text NOT NULL,
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `schedule` (`schedule`),
  KEY `season` (`season`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 赛季排名表 (2016/04/14)
CREATE TABLE IF NOT EXISTS `league_rank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season` int(10) unsigned NOT NULL DEFAULT '0',
  `schedule` int(10) unsigned NOT NULL DEFAULT '0',
  `group_tag` varchar(100) NOT NULL DEFAULT '',
  `team` int(10) unsigned NOT NULL DEFAULT '0',
  `wins` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `loses` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `k` int(10) unsigned NOT NULL DEFAULT '0',
  `d` int(10) unsigned NOT NULL DEFAULT '0',
  `a` int(10) unsigned NOT NULL DEFAULT '0',
  `points` int(10) unsigned NOT NULL DEFAULT '0',
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `season` (`season`),
  KEY `schedule` (`schedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;