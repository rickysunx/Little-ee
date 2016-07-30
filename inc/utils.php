<?php

//return $_POST and $_GET
function p($key) {
	if(isset($_GET[$key])) {
		return $_GET[$key];
	} else if (isset($_POST[$key])) {
		return $_POST[$key];
	} else {
		return "";
	}
}

function get_request_url() {
	return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}


function getXX($n) {
	return ($n<10)?("0".$n):("".$n);
}

function output_select ($a,$b) {
	if($a==$b) echo ' selected';
}

function get_now() {
	return date("Y-m-d H:i:s");
}

function get_today_date() {
	return date("Y-m-d");
}

function get_tomorrow_date() {
	$date = new DateTime();
	$date->add(new DateInterval("P1D"));
	return $date->format("Y-m-d");
}

function get_image_url($filename) {
	global $image_url;
	return $image_url.$filename;
}

function get_image_full_url($filename) {
	global $image_full_url;
	return $image_full_url.$filename;
}

function get_interval_date($intervalTime){
	$date = new DateTime(get_now());
	$date->add(new DateInterval($intervalTime));
	return $date->format('Y-m-d')." 00:00:00";
}

function app_log($log_string) {
	global $app_log_path;
	$log_file_name = $app_log_path.'app_log_'.date('Ymd').".txt";
	$time = date('Y-m-d H:i:s');
	file_put_contents($log_file_name, $time.' '.$log_string."\r\n",FILE_APPEND);
}

function get_milliseconds() {
	$t = round(microtime(true)*1000);
	return $t;
}

function is_string_empty($string) {
	return (!isset($string)) || strlen($string)==0;
}

function is_string_not_empty($string) {
	return !is_string_empty($string);
}

function get_lov($type,$key) {
	global $listOfValue;
	if(isset($listOfValue[$type]) && isset($listOfValue[$type][$key])) {
		return $listOfValue[$type][$key];
	}
	return "";
}

function get_random_string($size) {
	$random_chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$len = strlen($random_chars);
	$random_string = "";
	for($i=0;$i<$size;$i++) {
		$random_string .= $random_chars[mt_rand(0,$len-1)];
	}
	return $random_string;
}

function http_post_formdata($url,$params) {
	$ch = curl_init();

	app_log("[URL]".$url);

	if($params && count($params)>0) {
		foreach ($params as $key=>$value) {
			app_log($key."=".urlencode($value));
		}
	}
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	$output = curl_exec($ch);
	curl_close($ch);

	app_log("[RESPONSE]".$output);
	return $output;
}

function http_post_xml($url,$xml) {
	$ch = curl_init();
	
	app_log("[URL]".$url);
	app_log("[POST]".$xml);
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POST, 1);
	if($xml) curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	$output = curl_exec($ch);
	curl_close($ch);
	
	app_log("[RESPONSE]".$output);
	return $output;
}

function http_get($url,$params=NULL) {
	$ch = curl_init();

	$full_url = $url;

	if($params && count($params)>0) {
		$full_url .= "?";
		foreach ($params as $key=>$value) {
			$full_url .= $key."=".urlencode($value)."&";
		}
		$full_url = rtrim($full_url,"&");
	}

	app_log("[URL]".$full_url);

	curl_setopt($ch, CURLOPT_URL, $full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	curl_close($ch);
	
	app_log("[RESPONSE]".$output);
	return $output;
}

function send_sms($phone,$msg) {
	global $sms_user,$sms_pass,$sms_url;
	app_log("发送短信到手机号：".$phone." 短信内容：".$msg);
	http_get($sms_url,array(
		"OperID"=>$sms_user,
		"OperPass"=>$sms_pass,
		"DesMobile"=>$phone,
		"Content"=>urlencode(utf8_to_gbk("【小e管饭】".$msg))
	));
}

function utf8_to_gbk($str) {
	return iconv("UTF-8", "GBK", $str);
}

function get_next_month_end_date() {
	$date = new DateTime();
	$date->add(new DateInterval("P1M"));
	$year = $date->format("Y");
	$month = $date->format("m");
	$days = cal_days_in_month(0, $month, $year);
	
	return $year."-".$month."-".$days;
}

function get_time_duration ($predicted_time) {
	global $time_map,$time_duration_array;
	$time = substr($predicted_time, 11,5);
	$date = substr($predicted_time, 0,10);
	$time_index = $time_map[$time];
	return $date." ".$time_duration_array[$time_index];
}

function get_client_ip() {
	return $_SERVER['REMOTE_ADDR'];
}

function array2xml($data) {
	$xml = "<xml>\n";
	foreach ($data as $key=>$value) {
		$xml .= "<".$key.">".$value."</".$key.">\n";
	}
	$xml .= "</xml>";
	return $xml;
}

function xml2array($xml) {
	$elements = simplexml_load_string($xml);
	$elements = (array)$elements;
	$result = array();
	if($elements) {
		foreach($elements as $key=>$value) {
			$result[$key] = (string)$value;
		}
	}
	return $result;
}

$_request_id = null;

function gen_request_id() {
	global $_request_id;
	if($_request_id) return $_request_id;
	
	$session_string = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$len = strlen($session_string);
	$id_len = 2;
	$session_id = "";
	for($i=0;$i<$id_len;$i++) {
		$session_id .= $session_string[mt_rand(0,$len-1)];
	}
	$now = get_milliseconds();
	$_request_id = strtoupper(base_convert($now, 10, 36))."-".$session_id;
	return $_request_id;
}


function makeSuccess($result=array()) {
	$result['success'] = true;
	return $result;
}

function makeError($errcode,$errmsg) {
	$result = array(
			"success"=>false,
			"errcode"=>$errcode,
			"errmsg"=>$errmsg
	);

	return $result;
}

function check_not_null($key,$cnkey=NULL) {
	$value = trim(p($key));
	if(is_string_empty($value)) {
		output_result(makeError(9000, (is_string_empty($cnkey)?$key:$cnkey)."不能为空"));
		exit();
	}
	return p($key);
}

function check_values($val,$values) {
	foreach($values as $value) {
		if($value==$val) return true;
	}
	return false;
}

function is_mobile_phone($str) {
	$phone_len = strlen($str);
	if($phone_len==11 && is_number_string($str)) {
		return true;
	}
	return false;
}

function is_number_string($str) {
	$str_len = strlen($str);
	for($i=0;$i<$str_len;$i++) {
		if(!($str[$i]>='0' && $str[$i]<='9')) {
			return false;
		}
	}
	return true;
}

function is_valid_id_number($str) {
	if(strlen($str)!=15 && strlen($str)!=18) {
		return false;
	}
	$str_len = strlen($str);
	for($i=0;$i<$str_len-1;$i++) {
		if(!($str[$i]>='0' && $str[$i]<='9')) {
			return false;
		}
	}
	$last_char = strtoupper($str[$str_len-1]);
	if(!(($last_char>='0' && $last_char<='9') || $last_char=='X')) {
		return false;
	}
	return true;
}

function log_backtrace() {
	ob_start();
	debug_print_backtrace();
	$backtrace = ob_get_contents();
	ob_end_clean();
	
	app_log($backtrace);
}


function gen_session_id() {
	$session_string = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$len = strlen($session_string);
	$id_len = 20;
	$session_id = "";
	for($i=0;$i<$id_len;$i++) {
		$session_id .= $session_string[mt_rand(0,$len-1)];
	}
	$now = get_milliseconds();
	return strtoupper(base_convert($now, 10, 36)).$session_id;
}


function maskString($str,$start,$len) {
	$str_len = strlen($str);
	$new_str = "";
	for($i=0;$i<$str_len;$i++) {
		if($i>=$start && $i<($start+$len)) {
			$new_str .= "*";
		} else {
			$new_str .= $str[$i];
		}
	}
	return $new_str;
}


function push_shop_message($shop_id,$link,$msg,$obj_id) {
	global $push_accessid,$push_secretkey;

	app_log("in push shop message");

	$content = array(
			"msg"=>$msg,
			"link"=>$link,
			"obj_id"=>$obj_id
	);

	$link_title = ["",
			"您收到一个新订单",
			"您收到一个新评论",
			"您收到一笔入账",
			"您收到一条系统通知"
	];

	$msg_type = $link;
	if($link==2) $msg_type=4;
	if($link==4) $msg_type=2;

	$mymsg = array(
			"msg_type"=>$msg_type,
			"user_type"=>2,
			"user_id"=>$shop_id,
			"order_id"=>$obj_id,
			"msg_status"=>0,
			"msg_time"=>get_now(),
			"msg_content"=>$msg
	);

	db_save("ejew_msg", $mymsg);

	$token = db_query_value("select push_token from ejew_shop where shop_id = ?",[$shop_id]);
	if(is_string_empty($token)) {
		app_log("============推送token:未拿到==========");
		return;
	}

	$msg_item = new Message();
	$msg_item->setType(Message::TYPE_MESSAGE);
	$msg_item->setTitle($link_title[$link]);
	$msg_item->setContent(json_encode($content),JSON_UNESCAPED_UNICODE);
	$msg_item->setExpireTime(86400);

	$push = new XingeApp($push_accessid, $push_secretkey);

	app_log("====开始推送：token:".$token." msg:".var_export($msg_item,true));
	$ret = $push->PushSingleDevice($token, $msg_item);
	app_log("====推送结果：".json_encode($ret));

}

function do_md5_sign($service_key, $origin_params) {
	//对参数按照key升序排序
	ksort($origin_params);

	//将参数拼接成字符串
	$str = '';
	foreach($origin_params as $key => $val){
		$str .= $key.'='.$val;
	}

	//连接秘钥
	$str .= $service_key;
	//计算签名
	$sign = md5($str);
	return $sign;
}

$province_list = array();
$province_list[] = "北京市";
$province_list[] = "天津市";
$province_list[] = "河北省";
$province_list[] = "山西省";
$province_list[] = "内蒙古";
$province_list[] = "辽宁省";
$province_list[] = "吉林省";
$province_list[] = "黑龙江省";
$province_list[] = "上海市";
$province_list[] = "江苏省";
$province_list[] = "浙江省";
$province_list[] = "安徽省";
$province_list[] = "福建省";
$province_list[] = "江西省";
$province_list[] = "山东省";
$province_list[] = "河南省";
$province_list[] = "湖北省";
$province_list[] = "湖南省";
$province_list[] = "广东省";
$province_list[] = "广西自治区";
$province_list[] = "海南省";
$province_list[] = "重庆市";
$province_list[] = "四川省";
$province_list[] = "贵州省";
$province_list[] = "云南省";
$province_list[] = "西藏自治区";
$province_list[] = "陕西省";
$province_list[] = "甘肃省";
$province_list[] = "青海省";
$province_list[] = "宁夏回族自治区";
$province_list[] = "新疆维吾尔自治区";
$province_list[] = "香港特别行政区";
$province_list[] = "澳门特别行政区";
$province_list[] = "台湾";

$city_list = array();
$city_list[] = "北京市";
//$city_list[] = "上海市";
//$city_list[] = "广州市";
//$city_list[] = "深圳市";



