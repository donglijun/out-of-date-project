# API Reference For LOL

## 版本

v1.0.20140528

## 访问地址

http://api.lol.mkjogo.com/

## 接口定义

### Match

#### 检查战绩是否已上传

Request:

URL | Description 
----   | ----
/lol/match/check | Check whether a match result has been uploaded; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
platform | Y | string | 
gameid | Y | int | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
exists | Boolean

#### 上传战绩

Request:

URL | Description 
----   | ----
/lol/match/collect | Upload match result; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
platform | Y | string | 
gameid | Y | int | 
data | Y | string | JSON

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取单场比赛信息

Request:

URL | Description 
----   | ----
/lol/match/get | Get match result by gameid

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
game | Y | int | Game ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of match

### Summoner

#### 更新召唤师信息

Request:

URL | Description 
----   | ----
/lol/summoner/update | Update summoner profile; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
platform | Y | string | 
data | Y | string | JSON

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 保存最近使用的召唤师

Request:

URL | Description 
----   | ----
/lol/summoner/play | Save last played summoner; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
platform | Y | string | 
summonerID | Y | int | 
summonerName | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 获取最近使用的召唤师

Request:

URL | Description 
----   | ----
/lol/summoner/playhistory | Get last played summoners; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | summoner lists ordered by play time DESC

#### 获取召唤师最近比赛信息

Request:

URL | Description 
----   | ----
/lol/summoner/recentmatches | Get recent matches result

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
summoner | Y | int | Summoner ID
page | N | int | 1-base
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of matches
total_found | Total number of matches
page_count | Total pages of matches

Example:

```
$data = array(
    'game'   => 1000001,  # 游戏ID
    'len'    => 999,      # 游戏耗时
    'start'  => 999,      # 游戏开始时间（毫秒）
    'mode'   => 'NORMAL', # 比赛模式
    'ranked' => 0,        # 是否排名赛
    'map'    => 1,        # 地图ID
    'win_t'  => array(    # 胜者组
        0 => array(
            'id'       => 999,                     # 召唤师ID
            'name'     => 'Magic',                 # 召唤师名
            'champion' => 'Ashe',                  # 英雄名
            'bot'      => 0,                       # 是否Bot
            'k'        => 9,                       # 击杀
            'd'        => 9,                       # 死亡
            'a'        => 9,                       # 助攻
            'mddp'     => 99,                      # 对玩家的魔法伤害
            'pddp'     => 99,                      # 对玩家的物理伤害
            'tdt'      => 99,                      # 承受伤害
            'lmk'      => 9,                       # 最高连杀
            'mk'       => 99,                      # 补兵数
            'nmk'      => 99,                      # 杀野怪数
            'gold'     => 999,                     # 获得金币数
            'items'    => array(1, 9, 8, 5, 4, 2), # 出装列表
            'spells'   => array(2, 14),            # 两个法术技能
        ),
    ),
    'lose_t' => array(),  # 败者组
);
```

#### 搜索召唤师

Request:

URL | Description 
----   | ----
/lol/summoner/search | Search summoner

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
q | Y | string | Key word
page | N | int | 1-base
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of summoners
total_found | Total number of summoners
page_count | Total pages of summoners

#### 精确查找召唤师

Request:

URL | Description 
----   | ----
/lol/summoner/exact | Search summoner by name exactly

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
q | Y | string | Key word
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Profile of summoner

#### 获取召唤师信息

Request:

URL | Description 
----   | ----
/lol/summoner/getprofiles | Get summoners' profile

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
ids | Y | string | Summoner ids; Comma delimited integer
columns | N | string | Fields will be returned; Comma delimited string
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Details of profiles; Supports up to 20 records returned

Example:

```
$data = array(
    'id'           => 1000001,     # 召唤师ID
    'name'         => 'Magic',     # 召唤师名
    'level'        => 30,          # 等级
    'icon_id'      => 622,         # 头像ID
    'tunfwb'       => 1400241584,  # 下次首胜时间
    'metadata'     => array(       # 游戏模式相关数据
        'ARAM_UNRANKED_5x5' => array(
            'leaves' => 6,         # 逃跑
            'losses' => 2,         # 负
            'wins'   => 121,       # 胜
        ),
    ),
    'last_mk_user' => 2222,        # 最后使用该召唤师的MK用户
    'updated_on'   => 1400241584,  # 最后更新时间
);
```

#### 获取玩某一英雄的统计数据

Request:

URL | Description 
----   | ----
/lol/summoner/summarybychampion | Get summoner summary by champion

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
summoner | Y | int | 
champion | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Summoner summary

#### 获取玩所有英雄的统计数据

Request:

URL | Description 
----   | ----
/lol/summoner/summarybymultichampions | Get summoner summary by multi champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
summoner | Y | int | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Summary order by win rate and total matches

#### 获取玩某一英雄的最近比赛信息

Request:

URL | Description 
----   | ----
/lol/summoner/recentmatchesbychampion | Get recent matches by champion

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
summoner | Y | int | 
champion | Y | int | 
page | N | int | 1-base
limit | N | int | Default 20
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of matches
total_found | Total number of matches
page_count | Total pages of matches

#### 获取玩家某一个英雄的常用出装

Request:

URL | Description 
----   | ----
/lol/summoner/popularitemsbychampion | Get popular items by champion

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
summoner | Y | int | 
champion | Y | int | 
limit | N | int | Default 12
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include items and item's count

#### 获取反作弊状态

Request:

URL | Description 
----   | ----
/lol/summoner/ac | Get anti-cheating status; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | True for open; False for close

### Champion

#### 英雄概况

Request:

URL | Description 
----   | ----
/lol/champion/summary | Get champion summary

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
champion | Y | int | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail by game mode

#### 单英雄玩家排行榜

Request:

URL | Description 
----   | ----
/lol/champion/topsummoners | Get top summoners by champion

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
champion | Y | int | 
page | N | int | 1-base
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of summoners
total_found | Total number of summoners
page_count | Total pages of summoners

#### 玩家在单英雄排行榜中的页

Request:

URL | Description 
----   | ----
/lol/champion/summonerinrank | Get summoner page in rank

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
champion | Y | int | 
summoner | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of summoners
total_found | Total number of summoners
page_count | Total pages of summoners
page | Page number

### Chart

#### 最热门（最受欢迎）英雄榜

Request:

URL | Description 
----   | ----
/lol/chart/mostpopular | Get most popular champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
mode | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of champions

#### 最冷门英雄榜

Request:

URL | Description 
----   | ----
/lol/chart/leastpopular | Get least popular champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
mode | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of champions

#### 胜率最高英雄榜

Request:

URL | Description 
----   | ----
/lol/chart/highestwinrate | Get highest win rate champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
mode | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of champions

#### 胜率最低英雄榜

Request:

URL | Description 
----   | ----
/lol/chart/lowestwinrate | Get lowest win rate champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
mode | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of champions

#### 排名赛最多选择英雄榜

Request:

URL | Description 
----   | ----
/lol/chart/mostpicked | Get most picked champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
mode | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of champions

#### 排名赛最常被禁英雄榜

Request:

URL | Description 
----   | ----
/lol/chart/mostbanned | Get most banned champions

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
platform | Y | string | 
mode | Y | int | 
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Detail of champions

### Voice

#### 获取开黑房间ID

Request:

URL | Description 
----   | ----
/lol/voice/temporary | Get temporary voice room ID; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
userid | Y | int | 
session | Y | string | 
platform | Y | string | 
roomName | Y | string | 
team | Y | int | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Include room ID and lifetime in seconds

