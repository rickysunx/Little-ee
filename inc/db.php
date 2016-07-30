<?php 

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

/*
 * 查询单行数据
 */
function db_query_row ($sql,$params=NULL,$resulttype=MYSQLI_ASSOC) {
	$result = db_query($sql,$params,$resulttype);
	if($result && is_array($result) && count($result)>0) {
		return $result[0];
	}
	return NULL;
}

/*
 * 用于 select count(id) from .... 之类的查询
 */
function db_query_value($sql,$params=NULL) {
	$result = db_query_row($sql,$params,MYSQLI_NUM);
	return $result[0];
}

function db_query($sql,$params=NULL,$resulttype=MYSQLI_ASSOC) {
	$time_start = get_milliseconds();
	$db_conn = db_get_conn();
	$stmt = $db_conn->prepare($sql);
	
	$reqid = gen_request_id();
	
	app_log("----------[".$reqid."]db_query:".$sql);
	if($params) app_log("==========[".$reqid."]params:".json_encode($params));
	
	
	if(!$stmt) {
		app_log("+++++++[".$reqid."]sql_query_prepare_error:".$sql);
		app_log($db_conn->error);
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
		app_log("+++++++[".$reqid."]sql_update_execute_error:".$sql);
		app_log($stmt->error);
		throw new Exception($stmt->error,$stmt->errno);
	}
	
	$result = $stmt->get_result();
	$result_data = $result->fetch_all($resulttype);
	$time_end = get_milliseconds();
	app_log("==========[".$reqid."]sql_query_result[".($time_end-$time_start)." ms]=========\r\n".var_export($result_data,true)."\r\n");
	return $result_data;
}

function db_update($sql,$params=NULL,$returnid=false) {
	$time_start = get_milliseconds();
	$db_conn = db_get_conn();
	$stmt = $db_conn->prepare($sql);
	$reqid = gen_request_id();
	
	app_log("---------[".$reqid."]db_update:".$sql);
	if($params) app_log("=========[".$reqid."]params:".json_encode($params));
	
	if(!$stmt) {
		app_log("+++++++[".$reqid."]sql_update_prepare_error:".$sql);
		app_log($db_conn->error);
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
		app_log("+++++++[".$reqid."]sql_update_execute_error:".$sql);
		app_log($stmt->error);
		throw new Exception($stmt->error,$stmt->errno);
	}
	
	$result = null;
	
	if($returnid) {
		$result = $db_conn->insert_id;
	} else {
		$result = $stmt->affected_rows;
	}
	$time_end = get_milliseconds();
	app_log("=========[".$reqid."]sql_update_result[".($time_end-$time_start)." ms]===".var_export($result,true)."\r\n");
	return $result;
	
}

function db_update_item($table,$id_field,$item) {
	$sql = "update ".$table." set ";
	$params = array();
	foreach($item as $key=>$value) {
		if($key!=$id_field) {
			$sql .= ($key."=?,");
			$params[] = $value;
		}
	}
	$sql = rtrim($sql,",");
	$sql .= " where ".$id_field."=? ";
	$params[] = $item[$id_field];
	
	return db_update($sql,$params);
}

function db_save($table,$item) {
	$sql = "insert into ".$table." (";
	$qmark = "";
	$params = array();
	foreach($item as $key=>$value) {
		$sql.=$key.",";
		$qmark.="?,";
		$params[] = $value;
	}
	$sql = rtrim($sql,",");
	$qmark = rtrim($qmark,",");
	$sql.=") values (".$qmark.")";
	
	return db_update($sql,$params,true);
}

function db_autocommit($mode) {
	$db_conn = db_get_conn();
	$db_conn->autocommit($mode);
}

function db_commit() {
	$db_conn = db_get_conn();
	$db_conn->commit();
}
