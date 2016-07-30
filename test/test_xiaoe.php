<?php
$xiaoe_url = "http://open08.edaixi.cn:81/ex_order/v3/chifan/notifyNew";
$secret_key = '123456';

echo $xiaoe_url;

/* $param = array(
		'dsp_id' => 1,
		'args' => "{\"order_id\":11758185,\"receiver_name\":\"Admin\",\"receiver_phone\":13000090909,\"receiver_address\":\"北京市天安门\",\"receiver_x\":39.123132,\"receiver_y\":39.123132,\"status\":3,\"payment_status\":1,\"sp_no\":123,\"shop_name\":\"yaodian\",\"shop_address\":\"yaodian\",\"shop_phone\":\"132123213123\",\"shop_x\":32.3213213,\"shop_y\":32.3213213}"
);
 */

$order_id = "21758088";

$param = array(
	'dsp_id' => 1,
	'args' => "{\"order_id\":$order_id,\"receiver_name\":\"Admin\",\"receiver_phone\":13000090909,\"receiver_address\":\"北京市天安门\",\"receiver_x\":39.123132,\"receiver_y\":39.123132,\"status\":3,\"payment_status\":1,\"sp_no\":123,\"shop_name\":\"yaodian\",\"shop_address\":\"yaodian\",\"shop_phone\":\"132123213123\",\"shop_x\":32.3213213,\"shop_y\":32.3213213}"
);


$md5_sign = do_md5_sign($secret_key, $param);

$param['sign'] = $md5_sign;

$response = http_post_formdata($xiaoe_url,$param);
echo $response;
echo "\n";

function do_md5_sign($service_key, $origin_params) {
	//对参数按照key升序排序
	ksort($origin_params);
	//echo $param;
	
	//将参数拼接成字符串
	$str = '';
	foreach($origin_params as $key => $val){
		$str .= $key.'='.$val;
	}
	//echo $str;
	//echo "\n";
	
	//连接秘钥
	$str .= $service_key;
	//计算签名
	$sign = md5($str);
	return $sign;
}

function http_post_formdata($url,$params) {
	$ch = curl_init();

	//app_log("[URL]".$url);

	if($params && count($params)>0) {
		foreach ($params as $key=>$value) {
			//app_log($key."=".urlencode($value));
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

	//app_log("[RESPONSE]".$output);
	return $output;
}
