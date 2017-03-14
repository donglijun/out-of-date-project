CREATE DATABASE IF NOT EXISTS `mkjogo_video` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo_video`;

-- 分享链接表 (2014/09/23)
CREATE TABLE IF NOT EXISTS `link` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `source` varchar(255) NOT NULL DEFAULT '',
  `thumbnail_url` varchar(255) NOT NULL DEFAULT '',
  `custom_image` varchar(255) NOT NULL DEFAULT '',
  `author` bigint(20) NOT NULL DEFAULT '0',
  `author_name` varchar(255) NOT NULL DEFAULT '',
  `tags` varchar(255) NOT NULL DEFAULT '',
  `comments_count` int(11) NOT NULL DEFAULT '0',
  `bullets_count` int(11) NOT NULL DEFAULT '0',
  `lang` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `ups` int(11) NOT NULL DEFAULT '0',
  `downs` int(11) NOT NULL DEFAULT '0',
  `views_count` int(11) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 评论表 (2014/09/03)
CREATE TABLE IF NOT EXISTS `comment` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` text NOT NULL,
  `link` bigint(20) NOT NULL DEFAULT '0',
  `author` bigint(20) NOT NULL DEFAULT '0',
  `author_name` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `ups` int(11) NOT NULL DEFAULT '0',
  `downs` int(11) NOT NULL DEFAULT '0',
  `hot_point` float NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `link_created` (`link`,`created_on`),
  KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 弹幕表 (2014/09/15)
CREATE TABLE IF NOT EXISTS `bullet` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `link` bigint(20) NOT NULL DEFAULT '0',
  `body` varchar(255) NOT NULL DEFAULT '',
  `author` bigint(20) NOT NULL DEFAULT '0',
  `author_name` varchar(255) NOT NULL DEFAULT '',
  `style` varchar(255) NOT NULL DEFAULT '',
  `track` double NOT NULL DEFAULT '0',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `link_created` (`link`,`created_on`),
  KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 收藏表 (2014/07/24)
CREATE TABLE IF NOT EXISTS `favorite` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `link` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_link` (`user`,`link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 投票记录表 (2014/08/12)
CREATE TABLE IF NOT EXISTS `link_vote_history` (
  `link` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `score` tinyint(4) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `link_user` (`link`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 分享记录表 (2014/08/20)
CREATE TABLE IF NOT EXISTS `link_share_history` (
  `link` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `link_user` (`link`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 标签定义表 (2014/09/23)
CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `group` int(11) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `group` (`group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `tag` (`id`, `name`, `group`, `description`) VALUES
(1, 'Gaming', '0', ''),
(2, 'Entertainment', '0', ''),
(3, 'Music', '0', ''),
(4, 'Animation', '0', ''),
(5, 'Sports', '0', ''),
(6, 'Others', '0', ''),
(7, 'Movie', '0', '');

-- 标签分组定义表 (2014/09/23)
CREATE TABLE IF NOT EXISTS `tag_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 栏目定义表 (2014/09/17)
CREATE TABLE IF NOT EXISTS `column` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 链接栏目关系表 (2014/09/17)
CREATE TABLE IF NOT EXISTS `link_column` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `link` bigint(20) NOT NULL DEFAULT '0',
  `column` int(11) NOT NULL DEFAULT '0',
  `display_order` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `column_link` (`column`,`link`),
  KEY `column_display_order` (`column`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 直播房间表 (2014/10/14)
CREATE TABLE IF NOT EXISTS `room` (
  `id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `stream_key` varchar(255) NOT NULL DEFAULT '',
  `bio` text NOT NULL,
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;