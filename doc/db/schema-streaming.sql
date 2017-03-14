CREATE DATABASE IF NOT EXISTS `mkjogo_streaming` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo_streaming`;

-- 频道信息表 (2016/03/31)
CREATE TABLE IF NOT EXISTS `channel` (
  `id` bigint(20) NOT NULL,
  `title` text NOT NULL,
  `hash` varchar(255) NOT NULL DEFAULT '',
  `is_online` tinyint(4) NOT NULL DEFAULT '0',
  `is_banned` tinyint(4) NOT NULL DEFAULT '0',
  `is_signed` tinyint(4) NOT NULL DEFAULT '0',
  `is_exclusive` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `owner_name` varchar(255) NOT NULL DEFAULT '',
  `playing_game` int(11) NOT NULL DEFAULT '0',
  `alias` varchar(255) DEFAULT NULL,
  `special` varchar(255) NOT NULL DEFAULT '',
  `followers` int(11) NOT NULL DEFAULT '0',
  `upstream_ip` varchar(255) NOT NULL DEFAULT '',
  `upstream_on` int(11) NOT NULL DEFAULT '0',
  `resolutions` varchar(255) NOT NULL DEFAULT '',
  `paypal` varchar(255) NOT NULL DEFAULT '',
  `facebook` varchar(255) NOT NULL DEFAULT '',
  `class` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `small_show_image` varchar(255) NOT NULL DEFAULT '',
  `large_show_image` varchar(255) NOT NULL DEFAULT '',
  `offline_image` varchar(100) NOT NULL DEFAULT '',
  `background_image` varchar(100) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `memo` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`),
  KEY `owner_name` (`owner_name`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 媒体服务器信息表 (2014/10/15)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 频道助理表 (2014/10/24)
CREATE TABLE IF NOT EXISTS `editor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_user` (`channel`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 黑名单表 (2014/10/25)
CREATE TABLE IF NOT EXISTS `blacklist` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_user` (`channel`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 推送安排表 (2014/12/30)
CREATE TABLE IF NOT EXISTS `push_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` int(11) NOT NULL DEFAULT '0',
  `push_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `push_on` (`push_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 直播时长日志表 (2015/06/05)
CREATE TABLE IF NOT EXISTS `live_length_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `upstream_ip` varchar(255) NOT NULL DEFAULT '',
  `session` varchar(255) NOT NULL DEFAULT '',
  `from` int(11) NOT NULL DEFAULT '0',
  `to` int(11) NOT NULL DEFAULT '0',
  `length` int(11) NOT NULL DEFAULT '0',
  `hourly_pay` DECIMAL( 10, 4 ) UNSIGNED NOT NULL DEFAULT '0',
  `exclusive_bonus` DECIMAL( 10, 4 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_ip_session` (`channel`,`upstream_ip`,`session`),
  KEY `from` (`from`),
  KEY `length` (`length`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 关注关系表 (2015/01/14)
CREATE TABLE IF NOT EXISTS `following` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_channel` (`user`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 点卡表 (2015/01/22) -obsolete
CREATE TABLE IF NOT EXISTS `time_card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `consumed_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 点卡消费记录表 (2015/01/22) -obsolete
CREATE TABLE IF NOT EXISTS `time_card_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(255) NOT NULL DEFAULT '',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `mark` varchar(255) NOT NULL DEFAULT '',
  `operated_on` int(11) NOT NULL DEFAULT '0',
  `operated_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  UNIQUE KEY `mark_user` (`mark`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 活动报名表 (2015/02/27)
CREATE TABLE IF NOT EXISTS `campaign_member` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `game_account` varchar(255) NOT NULL DEFAULT '',
  `facebook` varchar(255) NOT NULL DEFAULT '',
  `skype` varchar(255) NOT NULL DEFAULT '',
  `signed_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 活动申诉表 (2015/01/16)
CREATE TABLE IF NOT EXISTS `campaign_complain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `reason` text NOT NULL,
  `contact` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 礼物账户表 (2015/02/06)
CREATE TABLE IF NOT EXISTS `gift_account` (
  `id` bigint(20) NOT NULL,
  `collecting` int(11) NOT NULL DEFAULT '0',
  `giving` int(11) NOT NULL DEFAULT '0',
  `remaining` int(11) NOT NULL DEFAULT '0',
  `num4` int(11) NOT NULL DEFAULT '0',
  `num5` int(11) NOT NULL DEFAULT '0',
  `num6` int(11) NOT NULL DEFAULT '0',
  `num7` int(11) NOT NULL DEFAULT '0',
  `num8` int(11) NOT NULL DEFAULT '0',
  `num9` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 频道礼物日志表 (2015/02/06)
CREATE TABLE IF NOT EXISTS `gift_channel_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `dealt_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `user` (`user`),
  KEY `dealt_on` (`dealt_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 个人礼物日志表 (2015/12/10)
CREATE TABLE IF NOT EXISTS `gift_user_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `highlight` bigint(20) NOT NULL DEFAULT '0',
  `task` int(10) unsigned NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `dealt_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `channel` (`channel`),
  KEY `dealt_on` (`dealt_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 栏目定义表 (2015/02/11)
CREATE TABLE IF NOT EXISTS `column` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 栏目链接表 (2015/11/12)
CREATE TABLE IF NOT EXISTS `column_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `column` int(11) NOT NULL DEFAULT '0',
  `media_type` varchar(32) NOT NULL DEFAULT '',
  `source` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `small_image` varchar(255) NOT NULL DEFAULT '',
  `large_image` varchar(255) DEFAULT NULL,
  `live_schedule_time` time NOT NULL DEFAULT '00:00:00',
  `display_order` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `column_display_order` (`column`,`display_order`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 广播表 (2015/06/10)
CREATE TABLE IF NOT EXISTS `broadcast` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `upstream_ip` varchar(255) NOT NULL DEFAULT '',
  `session` varchar(255) NOT NULL DEFAULT '',
  `recording_ip` varchar(255) NOT NULL DEFAULT '',
  `recording_on` int(11) NOT NULL DEFAULT '0',
  `length` int(11) NOT NULL DEFAULT '0',
  `size` int(11) NOT NULL DEFAULT '0',
  `w` int(11) NOT NULL DEFAULT '0',
  `h` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `memo` text NOT NULL,
  `uploaded_on` int(11) NOT NULL DEFAULT '0',
  `remote_path` varchar(255) NOT NULL DEFAULT '',
  `preview_path` varchar(255) NOT NULL DEFAULT '',
  `total_views` int(11) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_ip_session` (`channel`,`upstream_ip`,`session`),
  KEY `recording_on` (`recording_on`),
  KEY `uploaded_on` (`uploaded_on`),
  KEY `length` (`length`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=100000;

-- 精编广播表 (2015/12/03)
CREATE TABLE IF NOT EXISTS `broadcast_highlight` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `broadcast` bigint(20) NOT NULL DEFAULT '0',
  `start` int(11) NOT NULL DEFAULT '0',
  `stop` int(11) NOT NULL DEFAULT '0',
  `length` int(11) NOT NULL DEFAULT '0',
  `size` int(11) NOT NULL DEFAULT '0',
  `w` int(11) NOT NULL DEFAULT '0',
  `h` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `memo` text NOT NULL,
  `submitted_on` int(11) DEFAULT '0',
  `uploaded_on` int(11) NOT NULL DEFAULT '0',
  `remote_path` varchar(255) NOT NULL DEFAULT '',
  `preview_path` varchar(255) NOT NULL DEFAULT '',
  `total_views` int(11) NOT NULL DEFAULT '0',
  `total_bullets` int(11) NOT NULL DEFAULT '0',
  `is_hidden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `broadcast` (`broadcast`),
  KEY `uploaded_on` (`uploaded_on`),
  KEY `total_views` (`total_views`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=100000;

-- 每天礼物获取送出统计表 (2015/04/23)
CREATE TABLE IF NOT EXISTS `gift_report_total_daily` (
  `date` int(11) NOT NULL,
  `collecting` bigint(11) NOT NULL DEFAULT '0',
  `giving` bigint(11) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 主播每天获得礼物统计表 (2015/04/23)
CREATE TABLE IF NOT EXISTS `gift_report_channel_daily` (
  `date` int(11) NOT NULL,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `receiving` bigint(20) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `date_channel` (`date`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 主播每月获得礼物统计表 (2015/05/11)
CREATE TABLE IF NOT EXISTS `gift_report_channel_monthly` (
  `date` int(11) NOT NULL,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `receiving` bigint(20) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `date_channel` (`date`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 直播页面板信息表 (2015/05/15)
CREATE TABLE IF NOT EXISTS `panel` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 礼物冲榜竞赛表 (2015/05/27)
CREATE TABLE IF NOT EXISTS `gift_race` (
  `id` int(11) NOT NULL,
  `from` int(11) NOT NULL DEFAULT '0',
  `to` int(11) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 游戏信息表 (2015/06/02)
CREATE TABLE IF NOT EXISTS `game` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 系统广播记录表 (2015/06/16)
CREATE TABLE IF NOT EXISTS `system_broadcast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `body` varchar(255) NOT NULL DEFAULT '',
  `target_channel` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 精编视频弹幕表 (2015/06/24)
CREATE TABLE IF NOT EXISTS `bullet` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `highlight` bigint(20) NOT NULL DEFAULT '0',
  `body` varchar(255) NOT NULL DEFAULT '',
  `author` bigint(20) NOT NULL DEFAULT '0',
  `author_name` varchar(255) NOT NULL DEFAULT '',
  `style` varchar(255) NOT NULL DEFAULT '',
  `track` double NOT NULL DEFAULT '0',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `highlight_created` (`highlight`,`created_on`),
  KEY `author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 频道超管表 (2015/06/25)
CREATE TABLE IF NOT EXISTS `supervisor` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 主播分级定义表 (2015/10/29)
CREATE TABLE IF NOT EXISTS `channel_class` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `title` varchar(32) NOT NULL DEFAULT '',
  `hourly_pay` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `exclusive_bonus` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `withdraw_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 签约申请表 (2015/12/16)
CREATE TABLE IF NOT EXISTS `application` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `id_photo_front` varchar(255) NOT NULL DEFAULT '',
  `id_photo_back` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `skype` varchar(255) NOT NULL DEFAULT '',
  `twitch` varchar(255) NOT NULL DEFAULT '',
  `facebook` varchar(255) NOT NULL DEFAULT '',
  `app_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `app_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  `processed_on` int(10) unsigned NOT NULL DEFAULT '0',
  `processed_by` bigint(20) unsigned NOT NULL DEFAULT '0',
  `memo` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 上传定制图申请表 (2015/11/05)
CREATE TABLE IF NOT EXISTS `show_image_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) unsigned NOT NULL DEFAULT '0',
  `small_show_image` varchar(255) NOT NULL DEFAULT '',
  `large_show_image` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  `req_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `processed_on` int(10) unsigned NOT NULL DEFAULT '0',
  `processed_by` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 赛季定义表 (2015/11/24)
CREATE TABLE IF NOT EXISTS `league_season` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 赛季报名表 (2015/12/16)
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
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `season` (`season`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 观看直播任务定义 (2015/12/16)
CREATE TABLE IF NOT EXISTS `watching_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `gifts` int(10) unsigned NOT NULL DEFAULT '0',
  `points` int(10) unsigned NOT NULL DEFAULT '0',
  `timer` int(10) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 观看直播日志表 (2016/04/07)
CREATE TABLE IF NOT EXISTS `watching_length_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` bigint(20) NOT NULL DEFAULT '0',
  `upstream_ip` varchar(255) NOT NULL DEFAULT '',
  `session` varchar(255) NOT NULL DEFAULT '',
  `from` int(11) NOT NULL DEFAULT '0',
  `to` int(11) NOT NULL DEFAULT '0',
  `length` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `channel_ip_session` (`channel`,`upstream_ip`,`session`),
  KEY `from` (`from`),
  KEY `length` (`length`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 观看时长统计表 (2015/12/24)
CREATE TABLE IF NOT EXISTS `watching_length_summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dt` int(10) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `length` bigint(20) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dt` (`dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 送礼物成长分级表 (2015/12/29)
CREATE TABLE IF NOT EXISTS `gift_growth_scheme` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  `points` int(10) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;