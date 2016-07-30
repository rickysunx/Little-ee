<?php
require "inc/inc.php";
require "inc/api_common.php";

$secret_key = '123456';

$dsp_id = p("dsp_id");
$sign = p("sign");
$args = p("args");

//$dsp_id = 1;
//$sign = "cf889e99147ff7ac1163f49a61c81817";
////$args = '{"order_id":"18116","deliver_name":"messi","deliver_phone":"13699146624"}';
//$args = "{\"order_id\":\"18116\",\"deliver_name\":\"messi\",\"deliver_phone\":\"13699146624\"}";

//echo $dsp_id;
//echo $args;
//echo $sign;

//echo json_encode(array('a'=>'bbbb','c'=>'ddddd'));

$args_array = json_decode($args, true);

$args_array_to_sign = array(
		'dsp_id' => $dsp_id,
		'args' => $args
);

$result = checkDispatchInfo($args_array_to_sign, $secret_key, $sign);
output_result($result);

function checkDispatchInfo($args,$secret_key,$sign) {

	//$md5_sign = $sign;
	$md5_sign = do_md5_sign($secret_key, $args);
	
	if($md5_sign == $sign) {
		$result = array(
				"status"=>0,
				"statusinfo"=>""
		);
	
		return $result;
	} else {
		echo $md5_sign;
		$result = array(
				"status"=>1,
				"statusinfo"=>"参数错误",
				"data"=>'{"order_id":"订单id有误"," diliver_name ":"配送员姓名不能为空"}'
		);
	
		return $result;
	}

}

function do_md5_sign($service_key, $origin_params) {
	//对参数按照key升序排序
	ksort($origin_params);
	
	//将参数拼接成字符串
	$str = '';
	foreach($origin_params as $key => $val){
		$str .= $key.'='.$val;
	}
	
	//echo $str;
	//连接秘钥
	$str .= $service_key;
	//计算签名
	$sign = md5($str);
	return $sign;
}
