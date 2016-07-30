<?php

//database config
//$db_host = '10.66.160.158';
//$db_port = 3306;
//$db_name = 'ejew';
//$db_user = 'root';
//$db_pass = 'qaPLS9rw6NY2m9';

$db_host = '127.0.0.1';
$db_port = 3306;
$db_name = 'ejew';
$db_user = 'root';
$db_pass = '';

date_default_timezone_set('PRC');
$app_log_path = DIRECTORY_SEPARATOR=='\\'?"D:\\ejew\\logs\\":"/data/logs/";
$host_name = "www.chio2o.com";
$image_url = "upload/";
$image_full_url = DIRECTORY_SEPARATOR=='\\'?"http://localhost/ejew/upload/":"http://".$host_name."/upload/";
$vcode_timeout = 600;
$session_timeout = 3600*24*30;
$image_path = realpath(ABSPATH."../upload");
$pay_timeout = 180;


$listOfValue = array();

$listOfValue['order_status'] = array(0=>'待支付', 1=>'已支付', 2=>'已接单', 3=>'正在配送', 4=>'已完成', 
		5=>'待退款', 6=>'待退款', 7=>'拒绝接单',9=>'已评价',10=>'拒单关闭',11=>"订单关闭",
		12=>'客服退款关闭',13=>'订单关闭',14=>"小e拒单关闭",20=>'小e配送',21=>'小e接单',22=>'已取餐',25=>"小e拒单",
		26=>"小e拒单待退款");

$listOfValue['order_status_user'] = array(0=>'待支付', 1=>'等待商家确认', 2=>'美食制作中', 3=>'正在配送', 4=>'已完成',
		5=>'待退款', 6=>'待退款', 7=>'拒绝接单',9=>'已评价',10=>'拒单关闭',11=>"订单关闭",
		12=>'客服退款关闭',13=>'订单关闭',14=>"小e拒单关闭",20=>'小e配送',21=>'小e接单',22=>'已取餐',25=>"小e拒单",
		26=>"小e拒单待退款");

$listOfValue['order_status_admin'] = array(1=>'已支付', 2=>'美食制作中', 3=>'正在配送', 4=>'已完成',
		5=>'客服待退款', 6=>'订单取消待退款', 7=>'拒绝接单',9=>'已评价',10=>'拒单关闭',11=>"订单关闭",
		12=>'客服退款关闭',13=>'用户关闭',14=>"小e拒单关闭",20=>'小e配送',21=>'小e接单',22=>'小e已取餐',25=>"小e拒单",
		26=>"小e拒单待退款");

$listOfValue['pay_status'] = array(0=>'未支付', 1=>'已支付', 2=>'取消支付', 3=>'回调出错');
$listOfValue['gender'] = array (0=>'未知',1=>'男',2=>'女');
$listOfValue['approval_status'] = array (0=>'未提交审核', 1=>'审核通过', 2=>'审核不通过', 3=>'待审核');
$listOfValue['operation_status'] = array (0=>'暂停营业', 1=>'营业中', 2=>'关闭');
$listOfValue['complain_status'] = array(0=>'无投诉', 1=>'投诉处理中', 2=>'投诉关闭');
$listOfValue['delivery_method'] = array(1=>'送餐', 2=>'自取');
$listOfValue['is_super'] = array(0=>'否',1=>'是');
$listOfValue['admin_status'] = array(0=>'禁止',1=>'启用');
$listOfValue['boolean_value'] = array(0=>'否',1=>'是');

$errMsg['1001']="会话超时或无效，重定向到手机登录页面";
$errMsg['1002']="手机号和注册码有误，请重新输入";
$errMsg['1010']="尚未登录";

$time_array = array();
$time_duration_array = array();
for($i=0;$i<24;$i++) {
	$time_array[] = sprintf("%02d:00",$i);
	$time_array[] = sprintf("%02d:30",$i);
	
	$time_duration_array[] = sprintf("%02d:00-%02d:30",$i,$i);
	$time_duration_array[] = sprintf("%02d:30-%02d:00",$i,$i+1);
}

$time_map = array();
$time_count = count($time_array);
for($i=0;$i<$time_count;$i++) {
	$time_map[$time_array[$i]] = $i; 
}


$wx_appid = "#############";
$wx_appsecret = "########################";
$wx_pay_key = "########################";
$wx_mch_id = "########################";

$sms_user = "#############";
$sms_pass = "#############";
$sms_url = "#############";

$push_accessid = "#############";
$push_secretkey = "#############";


$xiaoe_secretkey = "#############";

$first_order_discount = 15;    //首单优惠金额
$pre_order_discount = 5; //提前一天预订优惠金额










