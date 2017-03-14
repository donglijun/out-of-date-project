# API Reference For WALLET

## 版本

v1.0.20151020

## 访问地址

http://api.nikksy.com/

## 接口定义

### Point

#### 获取积分账号余额

Request:

URL | Description 
----   | ----
/wallet/point/balance | Get point balance of user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Point balance

#### 用积分兑换点卡

Request:

URL | Description 
----   | ----
/wallet/point/exchange | Exchange time card with points

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
card_type | Y | int | Time card type
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Message if error occurs

#### 点卡兑换记录

Request:

URL | Description 
----   | ----
/wallet/point/exchange_history | Exchange history

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

#### 兑换记录详情

Request:

URL | Description 
----   | ----
/wallet/point/exchange_info | Exchange info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
exchange_id | Y | int | Exchange request id
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Exchange info

### Red

#### 在个人频道发红包

Request:

URL | Description 
----   | ----
/wallet/red/publish | Publish red to channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
points | Y | int | Points to send
number | Y | int | How many users can get red
memo | N | string | Text message to users
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 打开红包

Request:

URL | Description 
----   | ----
/wallet/red/open | Open a red

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Red ID
sender | Y | int | Sender ID
hash | Y | string | Hash
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Message if error occurs

#### 发送的红包记录

Request:

URL | Description 
----   | ----
/wallet/red/mysend | Reds sent by current user

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

#### 收到红包的记录

Request:

URL | Description 
----   | ----
/wallet/red/myget | Reds gotten by current user

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

#### 红包详情，包括领取记录

Request:

URL | Description 
----   | ----
/wallet/red/detail | Red detail

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
id | Y | int | Red ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Red detail

#### 获取频道发放的最后一个红包的信息

Request:

URL | Description 
----   | ----
/wallet/red/last | Last red detail

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Red detail
opened | Whether current user opened the red

#### 获取系统发放的最后一个红包的信息

Request:

URL | Description 
----   | ----
/wallet/red/last_system | Last system red detail

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
client | Y | int | Client ID
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Red detail
opened | Whether current user opened the red

#### 获取红包面额列表

Request:

URL | Description 
----   | ----
/wallet/red/types | Get red types

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Red types

### Card

#### 点卡类型列表

Request:

URL | Description 
----   | ----
/wallet/card/types | Get card types

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Card types

### Goods

#### 个人收到礼物汇总

Request:

URL | Description 
----   | ----
/wallet/goods/account | Goods account info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Goods account info

#### 30天内收到礼物按天汇总

Request:

URL | Description 
----   | ----
/wallet/goods/history | Goods summary in 30 days

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Goods summary by day

#### 礼品列表

Request:

URL | Description 
----   | ----
/wallet/goods/list | Get goods list

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Goods list

#### 送礼

Request:

URL | Description 
----   | ----
/wallet/goods/send | Send goods to broadcaster

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
goods | Y | int | Goods ID
number | Y | int | Goods amount
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Message if error occurs

#### 礼物组发列表

Request:

URL | Description 
----   | ----
/wallet/goods/groups | Get goods groups

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Goods groups

#### 当天全站送礼排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_site_user_today | Top site users to send most valuable goods today

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
data | Users info

#### 本周全站送礼排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_site_user_weekly | Top site users to send most valuable goods weekly

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
data | Users info

#### 本月全站送礼排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_site_user_monthly | Top site users to send most valuable goods monthly

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
data | Users info

#### 当天本频道送礼排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_channel_today | Top users to send most valuable goods in this channel today

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Users info

#### 本周本频道送礼排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_channel_weekly | Top users to send most valuable goods in this channel weekly

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Users info

#### 本月本频道送礼排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_channel_monthly | Top users to send most valuable goods in this channel monthly

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Users info

#### 本频道最近赠送礼物排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/latest_channel_user | Latest send in this channel

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
channel | Y | int | Channel ID
limit | N | int | Default 10
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Users info

#### 当天获得礼物频道排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_site_channel_today | Top site channels to get most valuable goods today

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
data | Channels info

#### 本周获得礼物频道排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_site_channel_weekly | Top site channels to get most valuable goods weekly

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
data | Channels info

#### 本月获得礼物频道排行榜

Request:

URL | Description 
----   | ----
/wallet/goods/top_site_channel_monthly | Top site channels to get most valuable goods monthly

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
data | Channels info

### Gold

#### 查询金币余额

Request:

URL | Description 
----   | ----
/wallet/gold/balance | Get gold account info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Account balance

#### 金币礼包列表

Request:

URL | Description 
----   | ----
/wallet/gold/packages | Get gold packages

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
client | N | int | Client ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Account balance

#### Android客户端下单

Request:

URL | Description 
----   | ----
/wallet/gold/android_prepare_order | Prepare a new order

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
package | Y | int | Gold package ID
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Order ID

#### Android客户端验证支付结果

Request:

URL | Description 
----   | ----
/wallet/gold/android_validate_order | Validate purchase result of an order from Android

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
order_id | Y | int | Order ID
tokens | Y | string | Android purchase tokens
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
message | Error message

### Money

#### 查询当前未结算收入

Request:

URL | Description 
----   | ----
/wallet/money/balance | Get money account info

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Account balance

#### 结算历史记录

Request:

URL | Description 
----   | ----
/wallet/money/withdraw_history | Withdraw history

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
data | Withdraw orders
total_found | Total number of items
page_count | Total pages of items

#### 最近日收入（30天内）

Request:

URL | Description 
----   | ----
/wallet/money/daily_bill | Get daily bill

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Bill info

