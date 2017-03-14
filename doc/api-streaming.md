# API Reference For STREAMING

## 版本

v1.0.20160406

## 访问地址

http://api.nikksy.com/

## 接口定义

### Channel

#### 创建频道

Request:

URL | Description 
----   | ----
/streaming/channel/create | Create a stream channel by current user; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
title | N | string | Room title
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including channel id and stream key

#### 更新频道(标题)

Request:

URL | Description 
----   | ----
/streaming/channel/update | Update channel info; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
title | N | string | Room title
playing_game | N | string | Playing game ID
paypal | N | string | Paypal account
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 进入频道

Request:

URL | Description 
----   | ----
/streaming/channel/enter | Enter a stream channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including channel info

#### 获取Stream Key

Request:

URL | Description 
----   | ----
/streaming/channel/getkey | Get stream key of current user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Private stream key

#### 重置Stream Key

Request:

URL | Description 
----   | ----
/streaming/channel/resetkey | Reset stream key

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | New stream key

#### 关注频道

Request:

URL | Description 
----   | ----
/streaming/channel/follow | Follow a channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 取消关注

Request:

URL | Description 
----   | ----
/streaming/channel/unfollow | Unfollow a channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 关注的频道列表

Request:

URL | Description 
----   | ----
/streaming/channel/following | List of following channels

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channels info

#### 获取推送到客户端展示的频道

Request:

URL | Description 
----   | ----
/streaming/channel/getclientone | Get a channel id to show in client

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channel id

#### 获取按照在线人数排序的频道列表

Request:

URL | Description 
----   | ----
/streaming/channel/listhot | Get live channels order by online users DESC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of channels
page | Current page
total_found | Total number of links
page_count | Total pages of links

#### 按照直播开始时间排序的频道列表

Request:

URL | Description 
----   | ----
/streaming/channel/listnew | Get live channels order by open time

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of channels
page | Current page
total_found | Total number of links
page_count | Total pages of links

#### 获取多个频道的简要信息

Request:

URL | Description 
----   | ----
/streaming/channel/info | Get channels' info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channels | Y | string | Channel ids delimited by comma
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of channels

#### 获取单个频道详细信息

Request:

URL | Description 
----   | ----
/streaming/channel/detail | Get detail of a channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including channel info

#### 获取近期直播记录（30天）

Request:

URL | Description 
----   | ----
/streaming/channel/livehistory | Get live records

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Live records

#### 按照游戏获取热门频道列表

Request:

URL | Description 
----   | ----
/streaming/channel/list_hot_by_game | Get live channels playing one game order by online users DESC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
game | Y | int | Game ID
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of channels
page | Current page
total_found | Total number of links
page_count | Total pages of links

#### 主播分级列表

Request:

URL | Description 
----   | ----
/streaming/channel/get_classes | Get channel classes

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channel classes list

#### 上传定制图

Request:

URL | Description 
----   | ----
/streaming/channel/upload_show_image | Update show images; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
large_show_image_file | Y | file | Large show image
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including image path
error | Error message

#### 上传背景图

Request:

URL | Description 
----   | ----
/streaming/channel/upload_background_image | Update background images; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
background_image_file | Y | file | Background image
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including image path
error | Error message

#### 上传离线图

Request:

URL | Description 
----   | ----
/streaming/channel/upload_offline_image | Update offline images; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
offline_image_file | Y | file | Offline image
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including image path
error | Error message

### Chat

#### 发送聊天消息

Request:

URL | Description 
----   | ----
/streaming/chat/send | Send chat message

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
channel | Y | int | Which channel to broadcast message to
body | Y | string | Message content
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取最后一条系统通知

Request:

URL | Description 
----   | ----
/streaming/chat/last_system_broadcast | Get last system broadcast message

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Broadcast info

#### 禁言

Request:

URL | Description 
----   | ----
/streaming/chat/gag | Gag user in channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
user | Y | int | User ID
expire | N | int | Expire time (seconds, default 600)
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 解除禁言

Request:

URL | Description 
----   | ----
/streaming/chat/remove_gag | Remove gag

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
user | Y | int | User ID
expire | N | int | Expire time (seconds, default 600)
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取禁言列表

Request:

URL | Description 
----   | ----
/streaming/chat/list_gag | List gag

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
expire | N | int | Expire time (seconds, default 600)
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Users info

### Editor

#### 添加频道管理员

Request:

URL | Description 
----   | ----
/streaming/editor/add | Add a channel editor to user's channel; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
editor_id | Y | int | User ID of editor
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 移除频道管理员

Request:

URL | Description 
----   | ----
/streaming/editor/remove | Remove a channel editor from user's channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
editor_id | Y | int | User ID of editor
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取管理员列表

Request:

URL | Description 
----   | ----
/streaming/editor/getusersbychannel | Get editor list of current channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Editors' info

#### 获取可管理的频道列表

Request:

URL | Description 
----   | ----
/streaming/editor/getchannelsbyuser | Get channel list you have editor privilege

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channels' info

#### 检查当前用户是否管理员

Request:

URL | Description 
----   | ----
/streaming/editor/check | Check if current user is editor of channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Boolean

### Blacklist

#### 加入黑名单

Request:

URL | Description 
----   | ----
/streaming/blacklist/add | Add user into blacklist

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
channel | Y | int | Channel ID
user | Y | int | User ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 解除黑名单

Request:

URL | Description 
----   | ----
/streaming/blacklist/remove | Remove user from blacklist

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
channel | Y | int | Channel ID
user | Y | int | User ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取禁言列表

Request:

URL | Description 
----   | ----
/streaming/blacklist/getuserbychannel | Get blacklist of channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Users' info

### Campaign

#### 报名参加活动

Request:

URL | Description 
----   | ----
/streaming/campaign/signup | Sign up campaign; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
game_account | Y | string | Game account of LOL
facebook | N | string | Facebook account
skype | N | string | Skype account
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取个人报名信息

Request:

URL | Description 
----   | ----
/streaming/campaign/profile | Get personal profile in campaign; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Profile

#### 申诉

Request:

URL | Description 
----   | ----
/streaming/campaign/complain | Complain signup; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
reason | Y | string | 
contact | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

### Gift

#### 获取个人礼物数量

Request:

URL | Description 
----   | ----
/streaming/gift/remain | Get number of remained gifts

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Gift number

#### 签到领取礼物（每天每个频道只能领一次，1个）

Request:

URL | Description 
----   | ----
/streaming/gift/checkin | Collect gifts from channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
captcha_value | N | string | Captcha value
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Balance

#### 分享领取礼物（每天每个精编视频只能领一次，1个）

Request:

URL | Description 
----   | ----
/streaming/gift/share | Collect gifts by share highlight in facebook

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
highlight | Y | int | Highlight broadcast ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Balance

#### 给主播送礼物（每次固定送10个）

Request:

URL | Description 
----   | ----
/streaming/gift/give | Give gifts to channel broadcaster

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Balance

#### 检查是否签到某个频道并获得礼物

Request:

URL | Description 
----   | ----
/streaming/gift/checkcheckin | Check if user have collected gift of channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Boolean

#### 检查是否分享过某个精编视频并获得礼物

Request:

URL | Description 
----   | ----
/streaming/gift/checkshare | Check if user have collected gift by sharing highlight

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
highlight | Y | int | Highlight broadcast ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Boolean

#### 当日礼物排行榜

Request:

URL | Description 
----   | ----
/streaming/gift/top_today | Top channels to get most gifts today

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channel info

#### 当周礼物排行榜

Request:

URL | Description 
----   | ----
/streaming/gift/top_week | Top channels to get most gifts in current week

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channel info

#### 当月礼物排行榜

Request:

URL | Description 
----   | ----
/streaming/gift/top_month | Top channels to get most gifts in current month

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channel info

#### 某个小时礼物排行榜

Request:

URL | Description 
----   | ----
/streaming/gift/top_hour | Top channels to get most gifts in the hour

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
hour | Y | int | Like 2015052116
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channel info including gifts

#### 观看直播任务列表

Request:

URL | Description 
----   | ----
/streaming/gift/watching_tasks | Get watching tasks with progress

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Watching tasks with progress

#### 完成一次观看直播任务

Request:

URL | Description 
----   | ----
/streaming/gift/complete_watching_task | Complete a task

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
token | Y | string | Task token
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 领取一次观看直播任务奖励

Request:

URL | Description 
----   | ----
/streaming/gift/award_watching_task | Award a task

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
task | Y | int | Task ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Gift bill and next task token

### Server

#### 获取直播服务器地址

Request:

URL | Description 
----   | ----
/streaming/server/roll | Get an IP address of media server

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | IP address

### Column

#### 获取栏目列表

Request:

URL | Description 
----   | ----
/streaming/column/list | Get all columns

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including columns' ID and name

#### 获取某个栏目中的链接

Request:

URL | Description 
----   | ----
/streaming/column/items | Get items in a column

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
column | Y | int | Column ID
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Column items
total_found | Total number of items
page_count | Total pages of items

### Broadcast

#### 更新录像信息

Request:

URL | Description 
----   | ----
/streaming/broadcast/update | Update broadcast info; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Broadcast ID
title | N | string | 
memo | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 删除录像

Request:

URL | Description 
----   | ----
/streaming/broadcast/delete | Delete broadcast; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Broadcast ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取录像详情

Request:

URL | Description 
----   | ----
/streaming/broadcast/detail | Get broadcast info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Broadcast ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Broadcast info

#### 获取主播近30天的录像列表

Request:

URL | Description 
----   | ----
/streaming/broadcast/list | Get broadcast list by channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 精编录像

Request:

URL | Description 
----   | ----
/streaming/broadcast/highlight | Highlight a broadcast; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Broadcast ID
start | Y | int | Start position (second)
stop | Y | int | Stop position (second)
title | N | string | 
memo | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取自己近30天的录像列表

Request:

URL | Description 
----   | ----
/streaming/broadcast/my | Get broadcast list of current user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 检查录像是否上传完成

Request:

URL | Description 
----   | ----
/streaming/broadcast/check_upload | Check upload status

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
ids | Y | string | Broadcast ids delimited by comma
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Broadcast info

### Highlight

#### 更新精编录像信息

Request:

URL | Description 
----   | ----
/streaming/highlight/update | Update highlight info; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Highlight broadcast ID
title | N | string | 
memo | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 删除精编录像

Request:

URL | Description 
----   | ----
/streaming/highlight/delete | Delete highlight; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Highlight broadcast ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取精编录像详情

Request:

URL | Description 
----   | ----
/streaming/highlight/detail | Get highlight broadcast info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Highlight broadcast ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast info

#### 获取主播的精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/list | Get highlight broadcast list by channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 获取自己的精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/my | Get highlight broadcast list of currrent user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 获取自己基于某部录像的精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/my_by_broadcast | Get highlight broadcast list of currrent user based on same broadcast

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
broadcast | Y | int | Broadcast ID
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 检查精编录像是否完成

Request:

URL | Description 
----   | ----
/streaming/highlight/check_upload | Check upload status

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
ids | Y | string | Highlight broadcast ids delimited by comma
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast info

#### 获取最新精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/list_new | Get highlight broadcast list by new

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 获取点击最多精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/list_hot | Get highlight broadcast list by hot

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 当天点击最多精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/list_hot_today | Get most viewed highlight broadcast list of today

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 本周点击最多精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/list_hot_week | Get most viewed highlight broadcast list of this week

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

#### 本月点击最多精编录像列表

Request:

URL | Description 
----   | ----
/streaming/highlight/list_hot_month | Get most viewed highlight broadcast list of this month

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Page number; Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Highlight broadcast items
total_found | Total number of items
page_count | Total pages of items

### Game

#### 获取全部游戏列表

Request:

URL | Description 
----   | ----
/streaming/game/list | Get game list

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Game list

#### 根据前缀过滤游戏列表

Request:

URL | Description 
----   | ----
/streaming/game/filter | Filter game list

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
keyword | Y | string | Name prefix, more than 3 characters
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Game list

### Panel

#### 上传面板图片

Request:

URL | Description 
----   | ----
/streaming/panel/upload_image | Upload panel image; Post method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
panel_image_file | Y | string | Image file
x | Y | int | x position
y | Y | int | y position
w | Y | int | Width
h | Y | int | Height
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Image url
error | Error message

#### 创建/更新面板信息

Request:

URL | Description 
----   | ----
/streaming/panel/update | Update panel info; Post method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
panel | N | int | Panel ID
title | N | string | Title
image | N | string | Image url
link | N | string | Link to image
description | N | string | Description
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Panel ID if created

#### 移除面板

Request:

URL | Description 
----   | ----
/streaming/panel/remove | Remove a panel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
panel | Y | int | Panel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 更新面板排序

Request:

URL | Description 
----   | ----
/streaming/panel/resort | Resort panels

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
panels | Y | string | Panels ID delimited by comma
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取面板列表

Request:

URL | Description 
----   | ----
/streaming/panel/list | List panels

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | string | Channel ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Channels info

### Bullet

#### 发表弹幕

Request:

URL | Description 
----   | ----
/streaming/bullet/submit | Send a bullet; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
cid | Y | int | Link id
message | Y | string | Bullet content
stime | Y | double | Track time
mode | N | string | Bullet position mode; Default '1'
type | N | string | Default 'normal'
msg | N | sting | Default '1'
size | N | int | Font size; Default 25
color | N | int | Font color; Default 16777215
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include bullet id and created time

#### 获取弹幕列表

Request:

URL | Description 
----   | ----
/streaming/bullet/list | Bullets order by created time ASC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
highlight | Y | int | Broadcast highlight id
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of bullets

#### 获取xml格式弹幕列表

Request:

URL | Description 
----   | ----
/streaming/bullet/toxml | Bullets as XML for player

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
highlight | Y | int | Broadcast highlight id

Return:

Name | Memo
----    | ----
- | XML content

### Application

#### 签约申请

Request:

URL | Description 
----   | ----
/streaming/application/signed | Apply signed broadcaster; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id_photo_front_file | Y | file | ID card photo front
id_photo_back_file | Y | file | ID card photo back
phone | Y | string | Phone number
skype | N | string | Skype
twitch | N | string | Twitch
facebook | N | string | Facebook
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Error message

#### 独家申请

Request:

URL | Description 
----   | ----
/streaming/application/exclusive | Apply exclusive broadcaster

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
agree | Y | int | Agree user protocol
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取签约申请状态

Request:

URL | Description 
----   | ----
/streaming/application/get_signed_status | Get signed application info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Appliation info

#### 获取独家申请状态

Request:

URL | Description 
----   | ----
/streaming/application/get_exclusive_status | Get exclusive application info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Appliation info

### Request

#### 上传定制图申请

Request:

URL | Description 
----   | ----
/streaming/request/show_image | Apply to update show images; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
small_show_image_file | Y | file | Small show image
large_show_image_file | Y | file | Large show image
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Error message

#### 获取定制图审核状态

Request:

URL | Description 
----   | ----
/streaming/request/get_show_image_status | Get show image request info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Request info

### League

#### 联赛报名申请

Request:

URL | Description 
----   | ----
/streaming/league/apply | Apply league; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
season | Y | int | Season ID
title | Y | string | Team title
leader_name | Y | string | 
leader_phone | Y | string | 
leader_phone2 | Y | string | 
leader_email | Y | string |
teams | Y | string | 
logo_file | N | file | Logo file
video | N | string | Video link
description | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Error message

#### 获取报名申请状态

Request:

URL | Description 
----   | ----
/streaming/league/get_application_status | Get application info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
season | Y | int | Season ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Appliation info

### Service

#### 验证Stream Key

Request:

URL | Description 
----   | ----
/service/streaming/validatekey | Validate stream key of a channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
stream_key | Y | string | For example: `live_100005_a7b59d8d515e13916cb7d2956e3e4a62`
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure; For example: `{"code":200}`

#### 获取频道Stream Key

Request:

URL | Description 
----   | ----
/service/streaming/getkey | Retrieve stream key of a channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel_id | Y | int | For example: `100005`
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including stream key; For example: `{"code":200,"data":{"stream_key":"live_100005_a7b59d8d515e13916cb7d2956e3e4a62"}}`