# Service Reference

## 版本

v1.0.20140721

## 基本原则

提供应用程序在内部网络间调用的接口。

## 访问地址

http://api.nikksy.com

## 接口定义

### 金币

#### 支付成功

请求：

URL | 描述 
----   | ----
/service/gold/lz_pay_good | 成功支付后调用的接口

参数:

变量名 | 必填字段 | 格式 | 备注
----    | :----:        | ----      | ----
order_id | Y | string | 订单编号
user | Y | int | 用户ID
golds | Y | int | 获得金币数
cost | Y | float | 充值金额，等值土耳其里拉
cost_unit | Y | string | 充值货币单位，土耳其里拉
order_time | Y | int | 支付时间戳

返回值（JSON格式）:

键名 | 备注
----    | ----
code | 成功返回200，其他值代表失败

#### 坏账通知

请求：

URL | 描述 
----   | ----
/service/gold/lz_pay_bad | 出现坏账后调用的接口

参数:

变量名 | 必填字段 | 格式 | 备注
----    | :----:        | ----      | ----
order_id | Y | string | 订单编号

返回值（JSON格式）:

键名 | 备注
----    | ----
code | 成功返回200，其他值代表失败

### 用户

#### 检查用户是否存在

请求：

URL | 描述 
----   | ----
/service/user/exist | 检查用户的接口

参数:

变量名 | 必填字段 | 格式 | 备注
----    | :----:        | ----      | ----
user | Y | int | 用户ID

返回值（JSON格式）:

键名 | 备注
----    | ----
code | 成功返回200，其他值代表失败

#### 登录（暂时只用于用户活跃度统计）

Request：

URL | Description 
----   | ----
/service/user/login | Mark active user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
user | Y | int | 

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
