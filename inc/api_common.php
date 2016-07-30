<?php
function action_error_handler($errcode , $errmsg, $errfile, $errline) {
	ob_clean();
	handle_error($errcode, $errmsg);
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
	ob_clean();
	handle_error($exception->getCode(), $exception->getMessage());
	error_log("error:".$exception->getFile()."[".$exception->getLine()."] ".$exception->getMessage());
	app_log("error:".$exception->getFile()."[".$exception->getLine()."] ".$exception->getMessage());
	app_log($exception->getTraceAsString());
	exit();
}

set_error_handler("action_error_handler");
set_exception_handler("action_exception_handler");

require 'api_common_funcs.php';



