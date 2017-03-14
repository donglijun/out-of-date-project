CREATE DATABASE IF NOT EXISTS `mkjogo` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo`;

-- 管理员账号
CREATE TABLE IF NOT EXISTS `admin_account` (
  `user` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `last_login_on` int(11) NOT NULL DEFAULT '0',
  `last_login_ip` varchar(64) NOT NULL DEFAULT '',
  `is_immovable` tinyint(4) NOT NULL DEFAULT '0',
  `group` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 管理员日志
CREATE TABLE IF NOT EXISTS `admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `logged_on` int(11) NOT NULL DEFAULT '0',
  `logged_ip` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `logged_on_user` (`logged_on`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 每日注册用户报表
CREATE TABLE IF NOT EXISTS `report_registration_daily` (
  `date` int(11) NOT NULL,
  `increment` int(11) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0',
  `growth_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 每周注册用户报表
CREATE TABLE IF NOT EXISTS `report_registration_weekly` (
  `date` int(11) NOT NULL,
  `increment` int(11) NOT NULL,
  `growth_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 每月注册用户报表
CREATE TABLE IF NOT EXISTS `report_registration_monthly` (
  `date` int(11) NOT NULL,
  `increment` int(11) NOT NULL,
  `growth_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 用户反馈表 (2014/02/26)
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `lang` varchar(64) NOT NULL DEFAULT '',
  `os` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `translation` text NOT NULL,
  `log_path` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `translated_on` int(11) NOT NULL DEFAULT '0',
  `translated_by` bigint(20) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `contact_way` varchar(32) NOT NULL DEFAULT '',
  `contact_info` varchar(255) NOT NULL DEFAULT '',
  `client` varchar(64) NOT NULL DEFAULT '',
  `ip` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `lang` (`lang`),
  KEY `created_on` (`created_on`),
  KEY `translated_by` (`translated_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 用户反馈错误信息表 (2014/02/26)
CREATE TABLE IF NOT EXISTS `feedback_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback` int(11) NOT NULL DEFAULT '0',
  `message` varchar(255) NOT NULL DEFAULT '',
  `times` int(11) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `feedback` (`feedback`),
  KEY `message` (`message`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 日活跃用户报表 (2014/03/05)
CREATE TABLE IF NOT EXISTS `report_active_users_daily` (
  `date` int(11) NOT NULL,
  `total` int(11) NOT NULL DEFAULT '0',
  `increment` int(11) NOT NULL DEFAULT '0',
  `growth_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 月活跃用户报表 (2014/03/05)
CREATE TABLE IF NOT EXISTS `report_active_users_monthly` (
  `date` int(11) NOT NULL,
  `total` int(11) NOT NULL DEFAULT '0',
  `increment` int(11) NOT NULL DEFAULT '0',
  `growth_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 每小时新登录用户报表 (2014/03/11)
CREATE TABLE IF NOT EXISTS `report_active_users_hourly` (
  `date` int(11) NOT NULL,
  `total` int(11) NOT NULL DEFAULT '0',
  `increment` int(11) NOT NULL DEFAULT '0',
  `growth_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 在线用户报表 (2014/03/17)
CREATE TABLE IF NOT EXISTS `report_online_users` (
  `date` bigint(20) NOT NULL,
  `lang` varchar(64) NOT NULL DEFAULT '',
  `total` int(11) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `date_lang` (`date`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- LOL每周英雄使用情况统计 (2014/04/15)
CREATE TABLE IF NOT EXISTS `report_lol_champion_weekly` (
  `date` int(11) NOT NULL DEFAULT '0',
  `champion` int(11) NOT NULL DEFAULT '0',
  `mode` int(11) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0',
  `win` int(11) NOT NULL DEFAULT '0',
  `win_rate` float NOT NULL DEFAULT '0',
  `lose` int(11) NOT NULL DEFAULT '0',
  `ranked_total` int(11) NOT NULL DEFAULT '0',
  `ranked_pick` int(11) NOT NULL DEFAULT '0',
  `ranked_pick_rate` float NOT NULL DEFAULT '0',
  `ranked_ban` int(11) NOT NULL DEFAULT '0',
  `ranked_ban_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`,`champion`,`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `report_lol_champion_weekly_br1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_eun1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_euw1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_kr` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_la1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_la2` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_na1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_oc1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_ru` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_tr1` LIKE `report_lol_champion_weekly`;
CREATE TABLE `report_lol_champion_weekly_wt1` LIKE `report_lol_champion_weekly`;

-- LOL每月英雄使用情况统计 (2014/04/15)
CREATE TABLE IF NOT EXISTS `report_lol_champion_monthly` (
  `date` int(11) NOT NULL DEFAULT '0',
  `champion` int(11) NOT NULL DEFAULT '0',
  `mode` int(11) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0',
  `win` int(11) NOT NULL DEFAULT '0',
  `win_rate` float NOT NULL DEFAULT '0',
  `lose` int(11) NOT NULL DEFAULT '0',
  `ranked_total` int(11) NOT NULL DEFAULT '0',
  `ranked_pick` int(11) NOT NULL DEFAULT '0',
  `ranked_pick_rate` float NOT NULL DEFAULT '0',
  `ranked_ban` int(11) NOT NULL DEFAULT '0',
  `ranked_ban_rate` float NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`,`champion`,`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `report_lol_champion_monthly_br1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_eun1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_euw1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_kr` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_la1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_la2` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_na1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_oc1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_ru` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_tr1` LIKE `report_lol_champion_monthly`;
CREATE TABLE `report_lol_champion_monthly_wt1` LIKE `report_lol_champion_monthly`;

-- Sphinx索引计数表 (2014/05/12)
CREATE TABLE IF NOT EXISTS `sph_counter` (
  `index_slug` varchar(255) NOT NULL DEFAULT '',
  `max_doc_id` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`index_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 语音服务器列表 (2014/06/03)
CREATE TABLE IF NOT EXISTS `voice_server` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 公告表 (2014/07/17)
CREATE TABLE IF NOT EXISTS `announcement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client` varchar(64) NOT NULL DEFAULT '',
  `lang` varchar(64) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `published_on` int(11) NOT NULL DEFAULT '0',
  `published_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `client_lang_status` (`client`,`lang`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 敏感词定义表 (2014/10/20)
CREATE TABLE IF NOT EXISTS `badword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content` (`content`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- 举报表 (2014/10/20)
CREATE TABLE IF NOT EXISTS `reported` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `target` bigint(20) NOT NULL DEFAULT '0',
  `module` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `user_name` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `reason` varchar(255) NOT NULL DEFAULT '',
  `reporter` bigint(20) NOT NULL DEFAULT '0',
  `reporter_name` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `target_module_type` (`target`,`module`,`type`),
  KEY `user` (`user`),
  KEY `reporter` (`reporter`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
