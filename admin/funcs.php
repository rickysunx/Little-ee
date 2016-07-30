<?php

session_start();

//common headers
header("Content-Type:text/html; charset=utf-8");

$max_page_item_count = 500;

function action_error_handler($errcode , $errmsg, $errfile, $errline) {
	error_log("error:".$errfile."[".$errline."] ".$errmsg);
	app_log("error:".$errfile."[".$errline."] ".$errmsg);
	
	ob_start();
	debug_print_backtrace();
	$backtrace = ob_get_contents();
	ob_end_clean();
	app_log($backtrace);
	
	exit();
}

function action_exception_handler($exception) {
	error_log("error:".$exception->getFile()."[".$exception->getLine()."] ".$exception->getMessage());
	app_log("error:".$exception->getFile()."[".$exception->getLine()."] ".$exception->getMessage());
	
	app_log($exception->getTraceAsString());
	exit();
}

set_error_handler("action_error_handler");
set_exception_handler("action_exception_handler");

function check_login() {
	if(!isset($_SESSION['admin_user'])) {
		$requestURL = get_request_url();
		header("Location: login.php?redirect_url=".urlencode($requestURL));
		exit();
	}
}

function check_login_api() {
	if(!isset($_SESSION['admin_user'])) {
		handle_error(9000, "未登录");
		exit();
	}
}

function set_admin_user($user) {
	$_SESSION['admin_user'] = $user;
}

function get_login_admin() {
	return $_SESSION['admin_user'];
}

function get_login_admin_id () {
	return get_login_admin()['admin_id'];
}

function get_page_count($item_count) {
	global $max_page_item_count;
	return ceil($item_count/$max_page_item_count);
}

function get_sql_limit($page,$item_count) {
	global $max_page_item_count;
	return " limit ".($page-1)*$max_page_item_count.",".$max_page_item_count." ";
}


function get_fixed_page() {
	$page = p("page");
	if(is_string_empty($page)) return 1;
	return (int)$page;
}


function show_time_options() {
	global $time_array;
	foreach ($time_array as $time) {
		echo "<option value='".$time."'>".$time."</option>\r\n";
	}
}

