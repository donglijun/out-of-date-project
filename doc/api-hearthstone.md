# API Reference

## 版本

v1.0.20140807

## 基本原则

满足用户卡牌的存储管理需求，支持用户收藏卡牌，也可以对卡牌评论和打分。

## 访问地址

http://api.lnplay.com/

## 接口定义

### 卡牌管理

#### 创建卡牌方案

Request:

URL | Description 
----   | ----
/deck/create | Create a new deck; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
title | Y | string | 
game_version | Y | string | 
category | Y | int | 
class | Y | string | 
cards | Y | array | 
description | Y | text | 
distribution | Y | array | 
is_public | Y | int | 
lang | Y | string | 
source | N | string | 
source_url | N | string | 
author | N | string | 
checksum | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
id | The new created deck id

#### 更新卡牌方案

Request:

URL | Description 
----   | ----
/deck/update | Update a new deck; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
deckid | Y | int | 
userid | Y | int | 
session | Y | string | 
title | Y | string | 
game_version | Y | string | 
category | Y | int | 
class | Y | string | 
cards | Y | array | 
description | Y | text | 
distribution | Y | array | 
is_public | Y | int | 
lang | Y | string | 
source | N | string | 
source_url | N | string | 
author | N | string | 
checksum | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 删除卡牌方案

Request:

URL | Description 
----   | ----
/deck/delete | Delete a deck; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
deck_ids | Y | string | Comma-delimited integer
userid | Y | int | 
session | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
affected | Deck ids have been deleted

#### 获取单套卡牌详细信息

Request:

URL | Description 
----   | ----
/deck/get | Retrieve detail information of a deck; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
deckid | Y | int | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | An array including detail information of a deck

#### 当前用户创建的卡牌列表

Request:

URL | Description 
----   | ----
/deck/list | Retrieve deck list of current user; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | An array including deck list

#### 条件搜索卡牌

Request:

URL | Description 
----   | ----
/deck/search | Retrieve decks; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid |  | int | 
category | | int | 
class | | string | 
q | | string | Keywords in title
page | | int | Default 1 
count | | int | Default 20

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
total_found | Total amount found
page_count | Total pages
data | An array including deck list

#### 编辑推荐榜

Request:

URL | Description 
----   | ----
/deck/recommended | Retrieve recommended decks; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
lang | Y | string | 
class | Y | string | 
category | Y | int | 
topn | Y | int | Default 10

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | An array including deck list

### 卡牌收藏

#### 收藏一组卡牌

Request:

URL | Description 
----   | ----
/favorite/follow | Favorite a deck; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
deckid | Y | int | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
id | Insert id

#### 取消收藏

Request:

URL | Description 
----   | ----
/favorite/unfollow | Cancel a favorite; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
favorite_ids | Y | string | Comma-delimited integer 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
affected | Favorite ids have been deleted

#### 当前用户的收藏列表

Request:

URL | Description 
----   | ----
/favorite/list | Retrieve favorites of current user; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | An array including favorites

### 用户反馈

Request:

URL | Description 
----   | ----
/feedback/lol | Submit feedback and log file; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | | int | 
session | | string | 
lang | | string | 
os | | string | 
description | | text | 
contact_way | | string | 
contact_info | | string | 
errors | | array | For example: errors[0][message]=ss&errors[0][times]=n
log | | file | Client log attached with feedback

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
id | Insert id

### 用户心跳

Request:

URL | Description
----   | ----
/user/ping | Ping server for online statistics purpose; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int |
session | Y | string |
lang | | string |

Return:

Name | Memo
----    | ----
code | Always 200

### 用户Session续期

Request:

URL | Description
----   | ----
/user/pulse | Heartbeat request to renewal session; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int |
session | Y | string |

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

### 公告/通知

Request:

URL | Description 
----   | ----
/announcement/lol | Get latest announcement

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
lang | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include id, url and publish time

### 更新用户语言

Request:

URL | Description
----   | ----
/user/updatelang | Update user's language; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int |
session | Y | string |
lang | Y | string | For example: zh_CN

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Boolean or integer for status
