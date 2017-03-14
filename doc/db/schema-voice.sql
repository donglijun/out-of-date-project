CREATE DATABASE IF NOT EXISTS `mkjogo_voice` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo_voice`;

-- 语音服务器信息表 (2014/07/15)
CREATE TABLE IF NOT EXISTS `server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_port` (`ip`,`port`),
  KEY `updated_on` (`updated_on`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 语音房间表 (2014/07/15)
CREATE TABLE IF NOT EXISTS `room` (
  `id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `creator` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `owner` bigint(20) NOT NULL DEFAULT '0',
  `password` varchar(255) NOT NULL DEFAULT '',
  `is_online` tinyint(4) NOT NULL DEFAULT '0',
  `max_online` int(11) NOT NULL DEFAULT '0',
  `current_online` int(11) NOT NULL DEFAULT '0',
  `options` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 房间管理员表 (2014/07/15)
CREATE TABLE IF NOT EXISTS `manager` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `room` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `granted_on` int(11) NOT NULL DEFAULT '0',
  `granted_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_user` (`room`,`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 房间黑名单表 (2014/07/15)
CREATE TABLE IF NOT EXISTS `blacklist` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `room` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_user` (`room`,`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

