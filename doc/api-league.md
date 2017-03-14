# API Reference For LEAGUE

## 版本

v1.0.20160324

## 访问地址

http://api.nikksy.com/

## 接口定义

### Application

#### 联赛报名申请

Request:

URL | Description 
----   | ----
/league/application/apply | Apply league; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
season | Y | int | Season ID
title | Y | string | Team title
leader_name | Y | string | 
leader_phone | Y | string | 
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

#### 修改报名信息

Request:

URL | Description 
----   | ----
/league/application/modify | Modify application and reset status; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
season | Y | int | Season ID
title | Y | string | Team title
leader_phone | Y | string | 
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
/league/application/check_status | Check application status

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

### Season

#### 检查赛季状态

Request:

URL | Description 
----   | ----
/league/season/check_status | Check season status

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
data | Season info

#### 获取赛程信息

Request:

URL | Description 
----   | ----
/league/season/get_schedules | Get schedules

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
data | Schedules info

#### 获取赛季队伍信息

Request:

URL | Description 
----   | ----
/league/season/get_teams | Get teams

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
data | Teams info

#### 获取比赛结果

Request:

URL | Description 
----   | ----
/league/season/get_matches | Get matches

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
data | Matches info

#### 获取一支队伍的赛季视频

Request:

URL | Description 
----   | ----
/league/season/get_team_videos | Get team videos

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
season | Y | int | Season ID
team | Y | int | Team ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Videos info

#### 获取赛季排名

Request:

URL | Description 
----   | ----
/league/season/get_ranks | Get ranks in season

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
data | Ranks info
