CREATE DATABASE IF NOT EXISTS `mkjogo_lol` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_br1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_eun1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_euw1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_kr` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_la1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_la2` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_na1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_oc1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_ru` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_tr1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE DATABASE IF NOT EXISTS `mkjogo_lol_wt1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `mkjogo_lol`;

-- 比赛信息表 (2014/04/16)
CREATE TABLE IF NOT EXISTS `match` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `game` bigint(20) NOT NULL DEFAULT '0',
  `user` bigint(20) NOT NULL DEFAULT '0',
  `champion` int(11) NOT NULL DEFAULT '0',
  `map` int(11) NOT NULL DEFAULT '0',
  `mode` int(11) NOT NULL DEFAULT '0',
  `ranked` tinyint(4) NOT NULL DEFAULT '0',
  `start` bigint(20) NOT NULL DEFAULT '0',
  `k` int(11) NOT NULL DEFAULT '0',
  `d` int(11) NOT NULL DEFAULT '0',
  `a` int(11) NOT NULL DEFAULT '0',
  `mddp` int(11) NOT NULL DEFAULT '0',
  `pddp` int(11) NOT NULL DEFAULT '0',
  `tdt` int(11) NOT NULL DEFAULT '0',
  `lmk` int(11) NOT NULL DEFAULT '0',
  `mk` int(11) NOT NULL DEFAULT '0',
  `nmk` int(11) NOT NULL DEFAULT '0',
  `gold` int(11) NOT NULL DEFAULT '0',
  `len` int(11) NOT NULL DEFAULT '0',
  `win` tinyint(4) NOT NULL DEFAULT '0',
  `items` varchar(255) NOT NULL DEFAULT '',
  `spells` varchar(255) NOT NULL DEFAULT '',
  `aps` varchar(255) NOT NULL DEFAULT '',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_user` (`game`,`user`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `match_br1` LIKE `match`;
CREATE TABLE `match_eun1` LIKE `match`;
CREATE TABLE `match_euw1` LIKE `match`;
CREATE TABLE `match_kr` LIKE `match`;
CREATE TABLE `match_la1` LIKE `match`;
CREATE TABLE `match_la2` LIKE `match`;
CREATE TABLE `match_na1` LIKE `match`;
CREATE TABLE `match_oc1` LIKE `match`;
CREATE TABLE `match_ru` LIKE `match`;
CREATE TABLE `match_tr1` LIKE `match`;
CREATE TABLE `match_wt1` LIKE `match`;

-- 用户基础账号表 (2014/05/10)
CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT '0',
  `icon_id` int(11) NOT NULL DEFAULT '0',
  `point` int(11) NOT NULL DEFAULT '0',
  `tunfwb` int(11) NOT NULL DEFAULT '0',
  `metadata` text NOT NULL,
  `last_mk_user` bigint(20) NOT NULL DEFAULT '0',
  `updated_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `updated_on` (`updated_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user_br1` LIKE `user`;
CREATE TABLE `user_eun1` LIKE `user`;
CREATE TABLE `user_euw1` LIKE `user`;
CREATE TABLE `user_kr` LIKE `user`;
CREATE TABLE `user_la1` LIKE `user`;
CREATE TABLE `user_la2` LIKE `user`;
CREATE TABLE `user_na1` LIKE `user`;
CREATE TABLE `user_oc1` LIKE `user`;
CREATE TABLE `user_ru` LIKE `user`;
CREATE TABLE `user_tr1` LIKE `user`;
CREATE TABLE `user_wt1` LIKE `user`;

-- pick-ban英雄记录表 (2014/05/10)
CREATE TABLE IF NOT EXISTS `champion_pick_ban` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `pick` varchar(255) NOT NULL DEFAULT '',
  `ban` varchar(255) NOT NULL DEFAULT '',
  `map` tinyint(4) NOT NULL DEFAULT '0',
  `mode` int(11) NOT NULL DEFAULT '0',
  `start` bigint(20) NOT NULL DEFAULT '0',
  `created_on` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `champion_pick_ban_br1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_eun1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_euw1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_kr` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_la1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_la2` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_na1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_oc1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_ru` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_tr1` LIKE `champion_pick_ban`;
CREATE TABLE `champion_pick_ban_wt1` LIKE `champion_pick_ban`;

-- 地区记录表 (2014/04/17)
CREATE TABLE IF NOT EXISTS `region` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abbr` (`abbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `region` (`id`, `name`, `abbr`, `description`) VALUES
(1, 'North America', 'na', ''),
(2, 'Brazil', 'br', ''),
(3, 'Latin America North', 'lan', ''),
(4, 'Latin America South', 'las', ''),
(5, 'EU West', 'euw', ''),
(6, 'EU Nordic & East', 'eune', ''),
(7, 'Turkey', 'tr', ''),
(8, 'Russia', 'ru', ''),
(9, 'Oceania', 'oce', ''),
(10, 'Republic of Korea', 'kr', ''),
(11, 'People''s Republic of China', 'cn', '');

-- 平台记录表 (2014/04/17)
CREATE TABLE IF NOT EXISTS `platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abbr` (`abbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `platform` (`id`, `region`, `name`, `abbr`, `description`) VALUES
(1, 1, 'North America', 'na1', ''),
(2, 2, 'Brazil', 'br1', ''),
(3, 3, 'Latin America North', 'la1', ''),
(4, 4, 'Latin America South', 'la2', ''),
(5, 5, 'EU West', 'euw1', ''),
(6, 6, 'EU Nordic & East', 'eun1', ''),
(7, 7, 'Turkey', 'tr1', ''),
(8, 8, 'Russia', 'ru', ''),
(9, 9, 'Oceania', 'oc1', ''),
(10, 10, 'Republic of Korea', 'kr', ''),
(11, 11, 'WT1', 'wt1', '');

-- 游戏地图记录表 (2014/05/09)
drop table `map`;
CREATE TABLE IF NOT EXISTS `map` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` int(11) NOT NULL DEFAULT '0',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `map` (`id`, `name`, `alias`, `abbr`, `description`) VALUES
(1, 'Summoner''s Rift', 1, 'sr', 'Summer Variant'),
(2, 'Summoner''s Rift', 1, 'sr', 'Autumn Variant'),
(3, 'The Proving Grounds', 3, 'pg', 'Tutorial Map'),
(4, 'Twisted Treeline', 10, 'tt', 'Original Version'),
(8, 'Crystal Scar', 8, 'cs', 'Dominion Map'),
(10, 'Twisted Treeline', 10, 'tt', 'Current Version'),
(12, 'Howling Abyss', 12, 'ha', 'ARAM Map');

-- 游戏模式记录表 (2014/05/08)
CREATE TABLE IF NOT EXISTS `mode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `is_index` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `mode` (`id`, `name`, `alias`, `description`, `is_index`) VALUES
(1, 'URF_BOT', 'URFBots', '', 0),
(2, 'FIRSTBLOOD_1x1', 'FirstBlood1x1', '', 0),
(3, 'CLASSIC', '', '', 0),
(4, 'FIRSTBLOOD_2x2', 'FirstBlood2x2', '', 0),
(5, 'RANKED_TEAM_5x5', 'RankedTeam5x5', '', 1),
(6, 'FIRSTBLOOD', '', '', 0),
(7, 'RANKED_TEAM_3x3', 'RankedTeam3x3', '', 1),
(8, 'ARAM_UNRANKED_5x5', 'AramUnranked5x5', '', 0),
(9, 'ONEFORALL_5x5', 'OneForAll5x5', '', 0),
(10, 'ODIN', '', '', 0),
(11, 'BOT', 'CoopVsAI', '', 0),
(12, 'ONEFORALL', '', '', 0),
(13, 'SR_6x6', 'SummonersRift6x6', '', 0),
(14, 'NORMAL', 'Unranked', '', 1),
(15, 'ODIN_UNRANKED', 'OdinUnranked', '', 0),
(16, 'URF', 'URF', '', 0),
(17, 'BOT_3x3', 'CoopVsAI3x3', '', 0),
(18, 'NORMAL_3x3', 'Unranked3x3', '', 0),
(19, 'ARAM', '', '', 0),
(20, 'RANKED_SOLO_5x5', 'RankedSolo5x5', '', 1),
(21, 'TUTORIAL', '', '', 0),
(22, 'CAP_5x5', 'CAP5x5', '', 0);

-- 游戏段位记录表 (2014/04/17)
CREATE TABLE IF NOT EXISTS `league_tier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `abbr` (`abbr`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `league_tier` (`id`, `name`, `abbr`, `description`) VALUES
(1, 'Bronze', 'bronze', ''),
(2, 'Silver', 'silver', ''),
(3, 'Gold', 'gold', ''),
(4, 'Platinum', 'platinum', ''),
(5, 'Diamond', 'diamond', ''),
(6, 'Challenger', 'challenger', '');

-- 游戏段位等级记录表 (2014/04/17)
CREATE TABLE IF NOT EXISTS `league_rank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `abbr` (`abbr`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `league_rank` (`id`, `name`, `abbr`, `description`) VALUES
(1, 'I', 'i', ''),
(2, 'II', 'ii', ''),
(3, 'III', 'iii', ''),
(4, 'IV', 'iv', ''),
(5, 'V', 'v', '');

-- 多语言定义表 (2014/05/30)
CREATE TABLE IF NOT EXISTS `lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `abbr` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

INSERT INTO `lang` (`id`, `name`, `abbr`, `description`) VALUES
(1, 'en_US', 'enUS', ''),
(2, 'ko_KR', 'koKR', ''),
(3, 'pt_BR', 'ptBR', '');
