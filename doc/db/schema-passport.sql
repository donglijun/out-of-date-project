CREATE DATABASE IF NOT EXISTS `mkjogo_passport` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo_passport`;

-- 账号信息表 (2014/12/22)
CREATE TABLE IF NOT EXISTS `account` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `old_password` varchar(255) NOT NULL DEFAULT '',
  `status` int(11) NOT NULL DEFAULT '0',
  `ban_until` int(11) NOT NULL DEFAULT '0',
  `freeze_until` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 个人简介表 (2014/12/22)
CREATE TABLE IF NOT EXISTS `profile` (
  `user` bigint(20) NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `nickname` varchar(255) NOT NULL DEFAULT '',
  `gender` tinyint(4) NOT NULL DEFAULT '0',
  `birthday` date NOT NULL,
  `lang` varchar(45) NOT NULL DEFAULT '',
  `country` varchar(45) NOT NULL DEFAULT '',
  `registered_ip` varchar(64) NOT NULL DEFAULT '',
  `registered_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Facebook连接表 (2014/12/12)
CREATE TABLE IF NOT EXISTS `connection_facebook` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `foreign_user` varchar(255) NOT NULL DEFAULT '',
  `access_token` varchar(255) NOT NULL DEFAULT '',
  `expires_in` int(11) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `foreign_user` (`foreign_user`),
  UNIQUE KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 重置密码日志表 (2014/12/17)
CREATE TABLE IF NOT EXISTS `reset_password_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `code` varchar(64) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `send_times` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `user` (`user`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 忘记用户名日志表 (2015/01/05)
CREATE TABLE IF NOT EXISTS `forgot_username_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `send_times` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 登录记录表 (2015/08/10)
CREATE TABLE IF NOT EXISTS `signin_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `ip` varchar(64) NOT NULL DEFAULT '0',
  `client` varchar(64) NOT NULL DEFAULT '',
  `client_version` varchar(64) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
