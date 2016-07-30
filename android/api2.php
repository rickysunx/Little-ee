<?php
require '../inc/inc.php';
require 'funcs.php';
switch (p ( 'method' )) {
	case "login" :
		login ();
		break;
	case "getIndexInfo" :
		getIndexInfo ();
		break;
	case "setMsgStatus" :
		setMsgStatus ();
		break;
	case "renewSid" :
		renewSid ();
		break;
}
// 验证登陆
// http://127.0.0.1/ejew/android/api.php?method=login&phone=13900000001&vcode=123
function login() {
	global $errMsg;
	// sid:会话id
	// is_new_user:0-否 1-是
	$user_id = db_query_value ( "SELECT user_id FROM ejew_vcode WHERE phone=? AND vcode_number=? and vcode_status=0", [ 
			p ( "phone" ),
			p ( "vcode" ) 
	] );
	if ($user_id == null) {
		$result ["success"] = false;
		$result ["errcode"] = "1002";
		$result ["errmsg"] = $errMsg ['1002'];
	} else {
		$session_id = db_query_value ( "SELECT session_id FROM ejew_session WHERE user_id=?", [ 
				$user_id 
		] );
		$shop_count = db_query_value ( "SELECT count(*) FROM ejew_shop WHERE shop_id=?", [ 
				$user_id 
		] );
		$result ["success"] = true;
		$result ["sid"] = $session_id;
		$result ["is_new_user"] = $shop_count == 1 ? 0 : 1;
	}
	echo json_encode ( $result );
	return json_encode ( $result );
}
// 首页信息（营业额、今日明日订单数量、要做的菜数量）
// http://127.0.0.1/ejew/android/api.php?method=getIndexInfo&shop_id=1
function getIndexInfo() {
	// product_count:要做的菜
	// order_count_today:今日订单量
	// order_count_tomorrow:明日订单量
	// income_today:今日营业额
	$today = get_interval_date ( 'P0D' );
	$tomorrow = get_interval_date ( 'P1D' );
	$afterTomorrow = get_interval_date ( 'P2D' );
	$result = array ();
	$product_count = db_query_value ( "select count(*) from ejew_product where shop_id=?", [ 
			p ( "shop_id" ) 
	] );
	$order_count = db_query_value ( "select count(*) FROM ejew_order WHERE shop_id=? and predicted_time > ?  AND predicted_time < ?", [ 
			p ( "shop_id" ),
			$today,
			$tomorrow 
	] );
	$order_count_tomorrow = db_query_value ( "select count(*) FROM ejew_order WHERE shop_id=? and predicted_time > ?  AND predicted_time < ? ", [ 
			p ( "shop_id" ),
			$tomorrow,
			$afterTomorrow 
	] );
	$income_today = db_query_value ( "SELECT SUM(total_fee) FROM ejew_order a,ejew_order_b b WHERE a.`shop_id` = ? AND a.`order_id`=b.`order_id` AND a.predicted_time >? AND predicted_time < ?", [ 
			p ( "shop_id" ),
			$today,
			$tomorrow 
	] );
	
	$result ["success"] = true;
	$result ["product_count"] = $product_count;
	$result ["order_count"] = $order_count;
	$result ["order_count_tomorrow"] = $order_count_tomorrow;
	$result ["income_today"] = $income_today;
	echo json_encode ( $result );
	return json_encode ( $result );
}
// 已阅读反馈
//127.0.0.1/ejew/android/api.php?method=setMsgStatus&msg_id=1&msg_status=1
function setMsgStatus() {
	$msg_status = p ( "msg_status" );
	$msg_id = p ( "msg_id" );
	db_update ( "UPDATE ejew_msg SET msg_status=? WHERE msg_id=?", array (
			$msg_status,
			$msg_id 
	) );
	$result ["success"] = true;
	echo json_encode ( $result );
	return json_encode ( $result );
}

// 刷新sid
//127.0.0.1/ejew/android/api.php?method=renewSid&user_id=1
function renewSid() {
	$session_id = db_query_value ( "SELECT session_id FROM ejew_session WHERE user_id=?", [ 
			p ( "user_id" ) 
	] );
	$result ["success"] = true;
	$result ["sid"] = $session_id;
	echo json_encode ( $result );
	return json_encode ( $result );
}

?>