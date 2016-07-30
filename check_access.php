<?php
require "inc/inc.php";
require "inc/api_common.php";
require "test_users.php";


$code = p("code");

$url = "https://api.weixin.qq.com/sns/oauth2/access_token";
$params = array(
		"appid"=>$wx_appid,
		"secret"=>$wx_appsecret,
		"code"=>$code,
		"grant_type"=>"authorization_code"
);

$content = http_get($url,$params);
$response = json_decode($content,true);

$access_token = $response['access_token'];
$expires_time = time()+$response['expires_in'];
$refresh_token = $response['refresh_token'];
$openid = $response['openid'];

$url = "https://api.weixin.qq.com/sns/userinfo";
$params = array(
		"access_token"=>$access_token,
		"openid"=>$openid,
		"lang"=>"zh_CN"
);

$content = http_get($url,$params);
$response = json_decode($content,true);

$nickname = $response['nickname'];
$sex = $response['sex'];
$province = $response['province'];
$city = $response['city'];
$country = $response['country'];
$headimgurl = $response['headimgurl'];
$session_id = gen_session_id();

$wx_user = array(
		"openid"=>$openid,
		"access_token"=>$access_token,
		"expires_time"=>$expires_time,
		"refresh_token"=>$refresh_token,
		"nickname"=>$nickname,
		"sex"=>$sex,
		"province"=>$province,
		"city"=>$city,
		"country"=>$country,
		"headimgurl"=>$headimgurl,
		"session_id"=>$session_id
);

db_update("delete from ejew_wx_user where openid=?",[$openid]);
db_save("ejew_wx_user", $wx_user);

$session_id = gen_session_id();
setcookie("wx_sid",$session_id,0,'/');
$now = time();

$session = array(
	"session_id"=>$session_id,
	"session_type"=>3,
	"create_time"=>$now,
	"update_time"=>$now,
	"openid"=>$openid
);

db_save("ejew_session", $session);

//if(check_values($openid, $test_users)) {
if(true) {
	$g_shopid = p("shopid");
	if(is_string_empty($g_shopid)) {
		header("Location: http://www.chio2o.com/client.php?accessible=1&code=".$code);
	} else {
		header("Location: http://www.chio2o.com/client.php?accessible=1&code=$code&shopid=$g_shopid");
	}
	
} else {
?>

<!DOCTYPE html>

<html>

<head>
<title>e家e味</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device_width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=no"/>
<style>
body,img {
	padding:0;
	margin:0;
}
img {
display:block;
border:0;
}
</style>
</head>

<body>
<img src="images/coming_soon.jpg" width="100%"/>
</body>
</html>

<?php 
}

