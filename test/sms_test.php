<?php
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

	echo ("[URL]".$full_url."\r\n");

	curl_setopt($ch, CURLOPT_URL, $full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	curl_close($ch);

	echo ("[RESPONSE]".$output);
	return $output;
}

function utf8_to_gbk($str) {
	return iconv("UTF-8", "GBK", $str);
}


echo "<pre>";
$sms_url = "http://221.179.180.158:9008/HttpQuickProcess/submitMessageAll";
/*
http_get($sms_url,array(
		"OperID"=>"eced3",
		"OperPass"=>"eced33",
		"DesMobile"=>"18610046239",
		"Content"=>urlencode(utf8_to_gbk("e家e味测试验证码[1234]"))
));
*/

echo "</pre>";








