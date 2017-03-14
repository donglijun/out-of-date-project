CREATE DATABASE IF NOT EXISTS `mkjogo_hearthstone` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo_hearthstone`;

-- 卡牌主信息表
CREATE TABLE IF NOT EXISTS `deck` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `modified_on` int(11) NOT NULL DEFAULT '0',
  `game_version` varchar(32) NOT NULL DEFAULT '',
  `category` tinyint(4) NOT NULL DEFAULT '0',
  `class` varchar(24) NOT NULL DEFAULT '',
  `distribution` varchar(255) NOT NULL DEFAULT '',
  `ncards` tinyint(4) NOT NULL DEFAULT '0',
  `lang` varchar(64) NOT NULL DEFAULT '',
  `checksum` varchar(64) NOT NULL DEFAULT '',
  `is_public` tinyint(4) NOT NULL DEFAULT '0',
  `favorites` int(11) NOT NULL DEFAULT '0',
  `comments` int(11) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '0',
  `source` varchar(255) NOT NULL DEFAULT '',
  `source_url` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `created_lang` (`created_on`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 卡牌扩展信息表 (2014/03/17)
CREATE TABLE IF NOT EXISTS `deck_extra` (
  `deck` bigint(20) NOT NULL DEFAULT '0',
  `cards` text NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`deck`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 卡牌收藏记录表
CREATE TABLE IF NOT EXISTS `user_favorite` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `deck` bigint(20) NOT NULL DEFAULT '0',
  `deck_owner` bigint(20) NOT NULL DEFAULT '0',
  `added_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_deck` (`user`,`deck`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 推荐榜单
CREATE TABLE IF NOT EXISTS `recommended` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(64) NOT NULL DEFAULT '',
  `class` varchar(24) NOT NULL DEFAULT '',
  `category` tinyint(4) NOT NULL DEFAULT '0',
  `deck` bigint(20) NOT NULL DEFAULT '0',
  `ranking` int(11) NOT NULL DEFAULT '0',
  `summary` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `modified_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_class_category_deck` (`lang`,`class`,`category`,`deck`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 卡牌基础信息表 (2014/03/14)
CREATE TABLE IF NOT EXISTS `card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_id` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `rarity` tinyint(4) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `cost` tinyint(4) NOT NULL DEFAULT '0',
  `attack` tinyint(4) NOT NULL DEFAULT '0',
  `health` tinyint(4) NOT NULL DEFAULT '0',
  `class` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_id` (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
