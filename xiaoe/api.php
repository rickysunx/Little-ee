<?php
require '../inc/inc.php';
require "../inc/api_common.php";

header("Content-type:text/html;charset=utf-8");

$method = p("method");

if($method=='update_courier') {
	update_courier();
} else if($method=='update_status') {
	update_status();
}

function update_courier() {
	$params = array();
	$result = array();
	foreach ($_GET as $key=>$value) {
		if($key!='sign') {
			$params[$key] = $value;
		}
	}
	ksort($params);
	$param_string = "";
	foreach ($params as $key=>$value) {
		$param_string .= ($key."=".$value."&");
	}
	$param_string = rtrim($param_string,"&");
	$sign = md5($param_string.$xiaoe_secretkey);
	
	$result['ret'] = true;
	output_result($result);
}

function update_status() {
	$result = array();
	
	$result['ret'] = true;
	output_result($result);
}


