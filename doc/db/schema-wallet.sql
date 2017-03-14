USE `mkjogo_streaming`;

-- 钱包账户表 (2015/04/16)
CREATE TABLE IF NOT EXISTS `point_account` (
  `id` bigint(20) NOT NULL,
  `number` int(11) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 钱包交易日志表 (2015/07/20)
CREATE TABLE IF NOT EXISTS `point_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `dealt_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_type` (`user`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 红包信息表 (2015/08/20)
CREATE TABLE IF NOT EXISTS `red` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `consumed_points` int(11) NOT NULL DEFAULT '0',
  `consumed_number` int(11) NOT NULL DEFAULT '0',
  `returned_points` int(11) NOT NULL DEFAULT '0',
  `memo` varchar(255) NOT NULL DEFAULT '',
  `target_channel` bigint(20) NOT NULL DEFAULT '0',
  `target_client` tinyint(4) NOT NULL DEFAULT '0',
  `hash` varchar(64) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `ending_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 红包类型表 (2015/06/10)
CREATE TABLE IF NOT EXISTS `red_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 红包领取日志表 (2015/05/14)
CREATE TABLE IF NOT EXISTS `red_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `red` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `memo` varchar(255) NOT NULL DEFAULT '',
  `dealt_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `red_user` (`red`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 红包发送日程表 (2015/08/20)
CREATE TABLE IF NOT EXISTS `red_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `points` int(11) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `memo` varchar(255) NOT NULL DEFAULT '',
  `target_channel` bigint(20) NOT NULL DEFAULT '0',
  `target_client` tinyint(4) NOT NULL DEFAULT '0',
  `publish_on` int(11) NOT NULL DEFAULT '0',
  `publish_status` tinyint(4) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `created_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `publish_on` (`publish_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 点卡信息表 (2015/04/29)
CREATE TABLE IF NOT EXISTS `card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL DEFAULT '',
  `type` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `consumed_on` int(11) NOT NULL DEFAULT '0',
  `consumed_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `type_status` (`type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 点卡类型定义表 (2015/04/29)
CREATE TABLE IF NOT EXISTS `card_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `price` int(11) NOT NULL DEFAULT '0',
  `game` int(11) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 点卡兑换记录表 (2015/04/29)
CREATE TABLE IF NOT EXISTS `card_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `price` int(11) NOT NULL DEFAULT '0',
  `code` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `processed_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 礼品信息表 (2015/09/25)
CREATE TABLE IF NOT EXISTS `goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `price` int(11) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL DEFAULT '',
  `slogan` varchar(255) NOT NULL DEFAULT '',
  `effect_trigger` int(11) NOT NULL DEFAULT '0',
  `rarity` tinyint(4) NOT NULL DEFAULT '0',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 礼品日志表 (2015/09/18)
CREATE TABLE IF NOT EXISTS `goods_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sender` bigint(20) NOT NULL DEFAULT '0',
  `receiver` bigint(20) NOT NULL DEFAULT '0',
  `goods` int(11) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `golds` int(11) NOT NULL DEFAULT '0',
  `withdraw_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sender` (`sender`),
  KEY `receiver` (`receiver`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 礼品组发表 (2015/08/12)
CREATE TABLE IF NOT EXISTS `goods_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 金币账户表 (2015/09/03)
CREATE TABLE IF NOT EXISTS `gold_account` (
  `id` bigint(20) NOT NULL,
  `recharge_num` int(11) NOT NULL DEFAULT '0',
  `locked_recharge_num` int(11) NOT NULL DEFAULT '0',
  `earn_num` int(11) NOT NULL DEFAULT '0',
  `remained_earn_num` int(11) NOT NULL DEFAULT '0',
  `remained_earn_money` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `withdraw_money` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `recharge_times` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `recharge_num` (`recharge_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 金币日志表 (2015/07/20)
CREATE TABLE IF NOT EXISTS `gold_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `number` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `dealt_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_type` (`user`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 金币礼包表 (2015/10/21)
CREATE TABLE IF NOT EXISTS `gold_package` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `money` float(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `money_unit` varchar(20) NOT NULL DEFAULT '',
  `golds` int(10) unsigned NOT NULL DEFAULT '0',
  `bonus` int(10) unsigned NOT NULL DEFAULT '0',
  `client` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 充值订单表 (2015/09/17)
CREATE TABLE IF NOT EXISTS `gold_recharge_order` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` bigint(20) NOT NULL DEFAULT '0',
  `foreign_id` varchar(255) NOT NULL DEFAULT '',
  `golds` int(11) NOT NULL DEFAULT '0',
  `cost` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `cost_unit` varchar(20) NOT NULL DEFAULT '',
  `foreign_timestamp` int(11) NOT NULL DEFAULT '0',
  `is_processed` tinyint(4) NOT NULL DEFAULT '0',
  `is_bad` tinyint(4) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  `processed_on` int(11) NOT NULL DEFAULT '0',
  `bad_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `foreign_id` (`foreign_id`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 提现订单表 (2015/09/24)
CREATE TABLE IF NOT EXISTS `withdraw_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` bigint(20) unsigned NOT NULL DEFAULT '0',
  `dt` int(10) unsigned NOT NULL DEFAULT '0',
  `live_length` int(10) unsigned NOT NULL DEFAULT '0',
  `live_salary` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `live_exclusive_bonus` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `goods_golds` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_money` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `total_money` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_money` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_on` int(10) unsigned NOT NULL DEFAULT '0',
  `processed_on` int(10) unsigned NOT NULL DEFAULT '0',
  `paypal` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_dt` (`user`,`dt`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;