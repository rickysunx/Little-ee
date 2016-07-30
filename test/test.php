<?php

set_time_limit(0);

//database config
$db_host = '127.0.0.1';
$db_port = 3306;
$db_name = 'ejew';
$db_user = 'root';
$db_pass = '123456';

$_db_conn = NULL;

function db_get_conn () {
	global $_db_conn,$db_host,$db_user,$db_pass,$db_name,$db_port;

	if(!$_db_conn) {
		$_db_conn = new mysqli($db_host,$db_user,$db_pass,$db_name,$db_port);
		if (mysqli_connect_errno()) {
			printf("mysql connect failed: %s\n", mysqli_connect_error());
			exit();
		}
		$_db_conn->set_charset("utf8");
	}
	return $_db_conn;
}

function db_update($sql,$params=NULL,$returnid=false) {
	$db_conn = db_get_conn();
	$stmt = $db_conn->prepare($sql);

	if(!$stmt) {
		error_log("sql_update_prepare_error:".$sql);
		error_log($db_conn->error);
		throw new Exception($db_conn->error,$db_conn->errno);
	}

	if($params) {
		if(is_array($params)) {
			$types = "";
			foreach($params as $param) {
				if(is_string($param)) {
					$types.='s';
				} else if(is_int($param)) {
					$types.='i';
				} else if(is_double($param)) {
					$types.='d';
				} else {
					$types.='s';
				}
			}
			$call_params = array($types);
			foreach($params as &$param) {
				$call_params[] = $param;
			}
			$method = new ReflectionMethod("mysqli_stmt","bind_param");
			$method->invokeArgs($stmt, $call_params);
		}
	}

	if(!$stmt->execute()) {
		error_log("sql_update_execute_error:".$sql);
		error_log($stmt->error);
		throw new Exception($stmt->error,$stmt->errno);
	}

	if($returnid) {
		return $db_conn->insert_id;
	} else {
		return $stmt->affected_rows;
	}


}

//echo 'http://www.chio2o.com'.$_SERVER['REQUEST_URI'];

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
	
	echo $full_url;
	
	curl_setopt($ch, CURLOPT_URL, $full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

//echo http_get("http://www.baidu.com");

function gen_session_id() {
	$session_string = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$len = strlen($session_string);
	$id_len = 32;
	$session_id = "";
	for($i=0;$i<$id_len;$i++) {
		$session_id .= $session_string[mt_rand(0,$len-1)];
	}
	return $session_id;
}

//for($i=0;$i<100;$i++) {
//	echo gen_session_id()."<br/>";
//}

for($i=0;$i<10000;$i++) {
	$session_id = gen_session_id();
	db_update("insert into test_key(session_id) values (?)",[$session_id]);
}

echo "done";




