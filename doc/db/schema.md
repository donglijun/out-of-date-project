# 数据库设计

## 版本

v1.0.20140317

## 基本原则

满足用户卡牌的存储管理需求，支持用户收藏卡牌，也可以对卡牌评论和打分。

## 表结构

### 卡牌主信息表(deck)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | bigint(20) | AUTO_INCREMENT | UNIQUE ID
user | bigint(20) | INDEX | Owner ID
title | varchar(255) | | 
created_on | int(11) | DEFAULT 0 | 
modified_on | int(11) | DEFAULT 0 | 
game_version | varchar(32) | | 
category | int(11) | DEFAULT 0 |
class | varchar(24) | |  
distribution | varchar(255) | | Including amount of ability, minion and weapon; JSON format
ncards | tinyint(4) | DEFAULT 0 |
lang | varchar(64) | |
checksum | varchar(64) | | A hash code for version controll
is_public | tinyint(4) | DEFAULT 0 | Others can access scheme or not
favorites | int(11) | DEFAULT 0 | 
comments | int(11) | DEFAULT 0 | 
views | int(11) | DEFAULT 0 | 
score | int(11) | DEFAULT 0 |
source | varchar(255) | | 
source_url | varchar(255) | | 
author | varchar(255) | | 

### 卡牌扩展信息表(deck_extra)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
deck | bigint(20) | UNIQUE | Mapped to a deck
cards | text | | Cards information; JSON format 
description | text | | UGC
distr_rarity | text | |
distr_type | text | |
distr_cost | text | |
distr_attack | text | |
distr_health | text | |

### 卡牌收藏记录表(user_favorite)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | bigint(20) | AUTO_INCREMENT | UNIQUE ID
user | bigint(20) | UNIQUE INDEX WITH DECK | Mapped to a user
deck | bigint(20) | UNIQUE INDEX WITH USER | Mapped to a deck
deck_owner | bigint(20) | DEFAULT 0 | Mapped to a user; redundant
added_on | int(11) | DEFAULT 0 | Time to add to favorites

### 卡牌推荐表(recommended)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | bigint(20) | AUTO_INCREMENT | UNIQUE ID
lang | varchar(64) | |
class | varchar(24) | |  
category | int(11) | DEFAULT 0 |
deck | bigint(20) | DEFAULT 0 | Mapped to a deck
ranking | int(11) | DEFAULT 0 | 
summary | varchar(255) | | 
created_on | int(11) | DEFAULT 0 | 
modified_on | int(11) | DEFAULT 0 | 

### 管理员账号表(admin_account)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
user   | bigint(20) | PRIMARY KEY | UNIQUE ID
name | varchar(255) | |
email | varchar(255) | | 
created_on | int(11) | DEFAULT 0 | 
created_by | bigint(20) | DEFAULT 0 | 
last_login_on | int(11) | DEFAULT 0 | 
last_login_ip | varchar(64) | | 
is_immovable | tinyint(4) | DEFAULT 0 | 
group | int(1) | DEFAULT 0 | 

### 管理日志表(admin_log)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | int(11) | AUTO_INCREMENT | UNIQUE ID
user | bigint(20) | |
action | varchar(255) | | 
content | text |  | JSON format
logged_on | int(11) | DEFAULT 0 | 
logged_ip | varchar(64) | | 

### 每日注册用户报表(report_registration_daily)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | int(11) |  | PRIMARY KEY
increment | int(11) | DEFAULT 0 |
total | int(11) | DEFAULT 0 | 
growth_rate | float | DEFAULT 0 |
updated_on | int(11) | DEFAULT 0 | 

### 每周注册用户报表(report_registration_weekly)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | int(11) |  | PRIMARY KEY
increment | int(11) | DEFAULT 0 |
growth_rate | float | DEFAULT 0 |
updated_on | int(11) | DEFAULT 0 | 

### 每月注册用户报表(report_registration_monthly)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | int(11) |  | PRIMARY KEY
increment | int(11) | DEFAULT 0 |
growth_rate | float | DEFAULT 0 | 
updated_on | int(11) | DEFAULT 0 | 

### 用户反馈表(feedback)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | int(11) |  | PRIMARY KEY
user | bigint(20) | INDEX | Submitter
lang | varchar(64) |  | INDEX
os | varchar(255) |  | 
description | text | | 
translation | text | | 
log_path | varchar(255) | | 
created_on | int(11) | DEFAULT 0 | INDEX
translated_on | int(11) | DEFAULT 0 | 
translated_by | bigint(20) | DEFAULT 0 | INDEX
status | tinyint(4) | DEFAULT 0 | 
contact_way | varchar(32) | | 
contact_info | varchar(255) | | 
client | varchar(64) | | 
ip | varchar(64) | | 

### 用户反馈错误信息表(feedback_error)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | int(11) |  | PRIMARY KEY
feedback | int(11) | DEFAULT 0 | INDEX
message | varchar(255) |  | INDEX
times | int(11) | DEFAULT 0 |
created_on | int(11) | DEFAULT 0 | INDEX

### 日活跃用户报表(report_active_users_daily)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | int(11) |  | PRIMARY KEY
total | int(11) | DEFAULT 0 | 
increment | int(11) | DEFAULT 0 |
growth_rate | float | DEFAULT 0 |
updated_on | int(11) | DEFAULT 0 | 

### 月活跃用户报表(report_active_users_monthly)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | int(11) |  | PRIMARY KEY
total | int(11) | DEFAULT 0 | 
increment | int(11) | DEFAULT 0 |
growth_rate | float | DEFAULT 0 |
updated_on | int(11) | DEFAULT 0 | 

### 每小时新登录用户报表(report_active_users_hourly)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | int(11) |  | PRIMARY KEY
total | int(11) | DEFAULT 0 |
increment | int(11) | DEFAULT 0 |
growth_rate | float | DEFAULT 0 |
updated_on | int(11) | DEFAULT 0 |

### 卡牌基础信息表 (card)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
id   | int(11) |  | PRIMARY KEY
card_id | varchar(64) | | UNIQUE
name | varchar(255) | |
rarity | tinyint(4) | DEFAULT 0 |
type | tinyint(4) | DEFAULT 0 |
cost | tinyint(4) | DEFAULT 0 |
attack | tinyint(4) | DEFAULT 0 |
health | tinyint(4) | DEFAULT 0 |
class | tinyint(4) | DEFAULT 0 |

### 在线用户报表(report_online_users)

Name | Type | Extra | Comment
---- | ---- | ----- | -------
date   | bigint(20) |  | UNIQUE KEY WITH `lang`
lang | varchar(64) | |
total | int(11) | DEFAULT 0 |
updated_on | int(11) | DEFAULT 0 |
