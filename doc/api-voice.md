# API Reference For VOICE

## 版本

v1.0.20140805

## 访问地址

http://api.voice.mkjogo.com/

## 接口定义

### Room

#### 创建语音房间

Request:

URL | Description 
----   | ----
/voice/room/create | Create a new room; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
id | Y | int | 
title | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Room ID

#### 修改房间参数

Request:

URL | Description 
----   | ----
/voice/room/edit | Edit room parameters; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
title | N | string | 
options | N | array | For example: options[announcement]=hello
icon_file_%d | N | file | %d is size of icon, default is 100
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include icon url if upload occured

#### 语音房间列表

Request:

URL | Description 
----   | ----
/voice/room/list | Get room list

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Room list

#### 进入房间

Request:

URL | Description 
----   | ----
/voice/room/enter | Enter a room

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Room data including options/blacklist/managers

#### 封禁

Request:

URL | Description 
----   | ----
/voice/room/ban | Ban a user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
user | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 解封

Request:

URL | Description 
----   | ----
/voice/room/unban | Unban users

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
users | Y | string | Comma delimited integer
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
count | Number of unban users

#### 增加管理员

Request:

URL | Description 
----   | ----
/voice/room/grantmanager | Grant management privileges to user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
user | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 取消管理员资格

Request:

URL | Description 
----   | ----
/voice/room/revokemanager | Revoke management privileges from user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
user | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 修改配置参数

Request:

URL | Description 
----   | ----
/voice/room/setoptions | Set room options

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
options | Y | array | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获得配置参数

Request:

URL | Description 
----   | ----
/voice/room/getoptions | Get room options

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Options

Options example:

```
$data = array(
    'version'                   => 1,
    'max_speak_time'            => 300,
    'send_text_cooldown'        => 5,
    'max_send_text_length'      => 1000,
    'max_emoji_in_text'         => 5,
    'send_image_cooldown'       => 60,
    'valid_image_types'         => 'jpg,png,gif,bmp',
    'max_image_size'            => 1024,
    'mute_time_before_speak'    => 15,
    'announcement'              => '',
);
```

#### 最近进入房间的历史记录

Request:

URL | Description 
----   | ----
/voice/room/history | Get room history

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
data | Room information including id/title/visit time

### Chat

#### 上传聊天图片

Request:

URL | Description 
----   | ----
/voice/chat/uploadimage | Upload image in text chat

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
room | Y | int | 
file | Y | file | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Image url



