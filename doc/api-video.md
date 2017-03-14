# API Reference For VIDEO

## 版本

v1.0.20141015

## 访问地址

http://api.video.mkjogo.com/

## 接口定义

### Link

#### 分享链接

Request:

URL | Description 
----   | ----
/video/link/submit | Share a new link; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
url | Y | string | 
title | Y | string | 
thumbnail_url | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include link id and created time

#### 获取链接详情

Request:

URL | Description 
----   | ----
/video/link/view | Get link summary

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
link | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including link summary

#### 投票（赞和踩）

Request:

URL | Description 
----   | ----
/video/link/vote | Vote for link; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
link | Y | int | Link id
dir | Y | int | Means direction, 1 for good, -1 for bad
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure


#### 个人分享的列表

Request:

URL | Description 
----   | ----
/video/link/my | Links shared by user self

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
page | N | int | 1-base
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of links
total_found | Total number of links
page_count | Total pages of links

#### 按照时间获取链接列表

Request:

URL | Description 
----   | ----
/video/link/listnew | Links order by created time DESC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
offset | N | int | Last displayed link id
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of links
total_found | Total number of links
page_count | Total pages of links

#### 按照热度获取链接列表

Request:

URL | Description 
----   | ----
/video/link/listhot | Links order by hot point DESC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
page | N | int | Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of links
total_found | Total number of links
page_count | Total pages of links

#### 按照栏目获取链接列表

Request:

URL | Description 
----   | ----
/video/link/listbycolumn | Links in column

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
column | Y | int | Column ID
page | N | int | Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of links
total_found | Total number of links
page_count | Total pages of links

#### 按照标签获取链接列表

Request:

URL | Description 
----   | ----
/video/link/listbytag | Links by tag

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
tag | Y | int | Tag ID
page | N | int | Default 1
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of links
total_found | Total number of links
page_count | Total pages of links

#### 随机获取一条最热门分享

Request:

URL | Description 
----   | ----
/video/link/randomhot | Get a random hot link summary

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including link summary

#### 随机获取一条最新分享

Request:

URL | Description 
----   | ----
/video/link/randomnew | Get a random new link summary

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including link summary

### Comment

#### 发表评论

Request:

URL | Description 
----   | ----
/video/comment/submit | Comment a link; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
link | Y | int | Link id
body | Y | string | Comment content
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include comment id and created time

#### 删除评论

Request:

URL | Description 
----   | ----
/video/comment/delete | Delete a comment; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
comment | Y | int | Comment id; Only comment author can delete it
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Deleted count

#### 获取评论列表

Request:

URL | Description 
----   | ----
/video/comment/list | Comments order by created time ASC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
link | Y | int | Link id
offset | N | int | Last displayed comment id
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of comments
total_found | Total number of comments
page_count | Total pages of comments


#### 投票评论（赞和踩）

Request:

URL | Description 
----   | ----
/video/comment/vote | Vote for comment; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
comment | Y | int | Comment id
dir | Y | int | Means direction, 1 for good, -1 for bad
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

### Bullet

#### 发表弹幕

Request:

URL | Description 
----   | ----
/video/bullet/submit | Send a bullet; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid (or mkjogo_u) | Y | int | 
session (or mkjogo_s) | Y | string | 
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
/video/bullet/list | Bullets order by created time ASC

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
link | Y | int | Link id
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
/video/bullet/toxml | Bullets as XML for player

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
link | Y | int | Link id

Return:

Name | Memo
----    | ----
- | XML content

### Favorite

#### 收藏链接

Request:

URL | Description 
----   | ----
/video/favorite/save | Save a link as favorite

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
link | Y | int | Link id
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 取消收藏

Request:

URL | Description 
----   | ----
/video/favorite/unsave | Remove a link from favorite

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
link | Y | int | Link id
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 个人收藏列表

Request:

URL | Description 
----   | ----
/video/favorite/my | My favorite links

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
page | N | int | 1-base
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of favorite
total_found | Total number of favorite
page_count | Total pages of favorite

### Tag

#### 获取所有Tag列表

Request:

URL | Description 
----   | ----
/video/tag/list | Get all tags

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including tags

#### 获取指定分类的Tag列表

Request:

URL | Description 
----   | ----
/video/tag/listbygroup | Get tags by group

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
group | Y | int | Group ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including tags

#### 获取所有的Tag分类列表

Request:

URL | Description 
----   | ----
/video/tag/grouplist | Get all tag groups

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including tag groups

### Report

#### 举报违法链接/评论/弹幕

Request:

URL | Description 
----   | ----
/video/report/submit | Report bad link/comment/bullet

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
type | Y | string | Enum('link', 'comment', 'bullet')
target | Y | int | Link/Comment/Bullet ID
reason | N | string | Why to report
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure





