# API Reference For PASSPORT

## 版本

v1.0.20150804

## 访问地址

http://api.nikksy.com/

## 接口定义

### User

#### 登录

Request:

URL | Description 
----   | ----
/passport/user/signin | Signin user; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
name | Y | string | 
password | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 登出

Request:

URL | Description 
----   | ----
/passport/user/signout | Signout user

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
from | N | string | A url to redirect to after signout

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 注册

Request:

URL | Description 
----   | ----
/passport/user/signup | Signup user; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
name | Y | string | Unique user name
password | Y | string | 
email | Y | string | 
captcha_value | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 通过登录Facebook注册

Request:

URL | Description 
----   | ----
/passport/user/signup_with_facebook | Signup user with facebook; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
name | Y | string | Unique user name
password | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 登录Facebook后绑定已注册账号

Request:

URL | Description 
----   | ----
/passport/user/bind_with_facebook | Bind existed user with facebook; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
name | Y | string | Registered user name
password | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured


#### 修改密码

Request:

URL | Description 
----   | ----
/passport/user/update_password | User update password; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
old_password | Y | string | 
new_password | Y | string | 
new_password_confirm | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 申请重置密码

Request:

URL | Description 
----   | ----
/passport/user/reset_password | Submit to reset password; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
name | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 提交重置的新密码

Request:

URL | Description 
----   | ----
/passport/user/confirm_reset_password | Submit new password; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
code | Y | string | The code for validation
new_password | Y | string | 
new_password_confirm | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

#### 检查用户名是否存在

Request:

URL | Description 
----   | ----
/passport/user/exists | Check if user name exists; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
name | Y | string | User name
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | True for exists; False for not

#### 忘记用户名

Request:

URL | Description 
----   | ----
/passport/user/forgot_username | Send connected account name to submitted email; POST method only

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
email | Y | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
error | Including errors occured

### Captcha

#### 获取验证码

Request:

URL | Description 
----   | ----
/captcha | Return a captcha image

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
ns | N | string | Use "signin" when signin; Use "signup" when signup

Return:

Name | Memo
----    | ----
 | 

### Connection

#### 使用Facebook登录

Request:

URL | Description 
----   | ----
/passport/connection/fb_login | Login with facebook account

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
from | N | string | A url to redirect to after login with facebook

Return:

(Redirect without return)
 
#### 验证客户端登录Token

Request:

URL | Description 
----   | ----
/passport/connection/fb_verify_client_token | Verify client access token

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
access_token | Y | string | An facebook access token from app

Return:

Name | Memo
----    | ----
code | 200 for success; 302 for sign up; others for failure
error | Including errors occured
 
### Profile
 
#### 获取个人资料
 
Request:

URL | Description 
----   | ----
/passport/profile/get | Get user's profile

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure
data | Including profile

#### 修改个人资料

Request:

URL | Description 
----   | ----
/passport/profile/update | Update user's profile

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
email | N | string | 
nickname | N | string | 
format | N | string | Support to json/jsonp/var; Default "json"
callback | N | string | Default "mk_api_result"

Return:

Name | Memo
----    | ----
code | 200 for success, others for failure

#### 上传头像
 
Request:

URL | Description 
----   | ----
/passport/profile/upload_avatar | Upload avatar

Parameters:

Name | Required | Format | Memo
----    | :----:        | ----      | ----
avatar_file | Y | file | File element
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

