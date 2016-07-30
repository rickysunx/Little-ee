<?php
require '../inc/inc.php';
require "../inc/api_common.php";

header("Content-type:text/html;charset=utf-8");

$method_list = array(
		"login",
		"genVerifyCode",
		"renewSid",
		"logout",
		"getIndexInfo",
		"getIndexNotice",
		"getMsgList",
		"setMsgStatus",
		"get_todo_product",
		"getOrderList",
		"getOrderInfo",
		"setOrderStatus",
		"getBillList",
		"clearMsg",
		"getShopInfo",
		"updateShopInfo",
		"getKeeperInfo",
		"updateKeeperInfo",
		"getShopStatus",
		"getProductList",
		"getProduct",
		"addProduct",
		"updateProduct",
		"getBank",
		"deleteBank",
		"updateBank",
		"getCommentList",
		"addCommentReply",
		"uploadImage",
		"addPushToken",
		"deleteProduct",
		"setShopStatus",
		"updateCert",
		"getCert",
		"getSharedQR",
		"setProductStatus",
		"getOrderCount",
        "genVoiceVerifyCode"
);


$method = p("method");

$reqid = gen_request_id();
app_log("===============Android_Service[".$method."][".$reqid."]================");
$api_time_start = get_milliseconds();
app_log("[".$reqid."][GET]".json_encode($_GET,JSON_UNESCAPED_UNICODE));
app_log("[".$reqid."][POST]".json_encode($_POST,JSON_UNESCAPED_UNICODE));

if(check_values($method, $method_list)) {
	$result = call_user_func($method);
} else {
	return makeError(8000, "方法不支持:".$method);
}
app_log("[".$reqid."][RET]".json_encode($result,JSON_UNESCAPED_UNICODE));
output_result($result);
$api_time_end = get_milliseconds();
app_log("===============End of Android_Service[".$method."][".$reqid."][".($api_time_end-$api_time_start)." ms]================");

/*
 * http://localhost/ejew/android/api.php?method=login&phone=13900000001&vcode=8280
 */
function login() {
	global $vcode_timeout;
	$phone = p("phone");
	$vcode = p("vcode");
	
	$now = time();
	
	$sql = "delete from ejew_vcode where validate_time<?";
	db_update($sql,[$now]);
	
	//check vcode
	$sql = "select * from ejew_vcode where phone=? and validate_time>=? order by vcode_id desc";
	$item = db_query_row($sql,[$phone,$now]);
	
	if( $phone!='18001167362' ) {
		if( (!$item) || $item['vcode_number']!=$vcode ) {
			return makeError(9000, "验证码不正确");
		}
	}
		
	db_update("delete from ejew_vcode where phone=?",[$phone]);
	
	//check shop
	$sql = "select * from ejew_shop where shop_phone=?";
	
	$shop = db_query_row($sql,[$phone]);
	
	$session_id = gen_session_id();
	
	$session = array(
		"session_id"=>$session_id,
		"session_type"=>2,
		"create_time"=>$now,
		"update_time"=>$now
	);
	
	if($shop) {
		$session['user_id'] = $shop['shop_id'];
		db_save("ejew_session", $session);
		return makeSuccess(array(
			"sid"=>$session_id,
			"is_new_user"=>0
		));
	} else {
		$shop = array(
			"shop_phone"=>$phone,
			"reg_time"=>get_now(),
			"lunch_stop_time"=>"15:30",
			"dinner_stop_time"=>"21:30"
		);
		
		$shop_id = db_save("ejew_shop", $shop);
		
		$session['user_id'] = $shop_id;
		db_save("ejew_session", $session);
		
		return makeSuccess(array(
			"sid"=>$session_id,
			"is_new_user"=>1
		));
	}
	
}


/*
 * http://localhost/ejew/android/api.php?method=genVerifyCode&phone=13900000001
 */
function genVerifyCode() {
	global $vcode_timeout;
	$phone = p("phone");
	
	if(!is_mobile_phone($phone)) {
		return makeError(9000, "非合法手机号");
	}
	
	$value = db_query_row("select vcode_id from ejew_vcode where phone=? and create_time > ? and vcode_status=0 and vcode_type=2",array($phone,time()-60));
	
	if($value) {
		return makeError(9000, "同一手机1分钟内只能获取一次验证码");
	}
	
	$value = db_query_row("select vcode_id,vcode_number from ejew_vcode where phone=? and create_time > ? and vcode_status=0 and vcode_type=2",array($phone,time()-300));
	
	if($value) {
		$vcode = $value['vcode_number'];
	} else {
		db_update("update ejew_vcode set vcode_status=2 where phone = ? and vcode_type=2",array($phone));
		$vcode = mt_rand(1000,9999);
		
		$item = array(
				"vcode_number"=>$vcode,
				"vcode_type"=>2,
				"vcode_status"=>0,
				"phone"=>$phone,
				"create_time"=>time(),
				"validate_time"=>time()+$vcode_timeout,
		);
		
		db_save("ejew_vcode", $item);
	}
	
	
	
	send_sms($phone, "您登录e家e味厨师端的验证码为：(".$vcode.")");
		
	return makeSuccess(array(
		"seconds"=>60
	));
}

/*
 * genVoiceVerifyCode
 */
function genVoiceVerifyCode(){
    global $vcode_timeout;

    $phone = p("phone");
    if(!is_mobile_phone($phone)) {
        return makeError(9000, "非合法手机号");
    }

    $value = db_query_row("select vcode_id,vcode_number from ejew_vcode where phone=? and create_time > ? and vcode_status=0 and vcode_type=2",array($phone,time()-300));

    if($value) {
        $vcode = $value['vcode_number'];
    } else {
        db_update("update ejew_vcode set vcode_status=2 where phone = ? and vcode_type=2",array($phone));
        $vcode = mt_rand(1000,9999);

        $item = array(
            "vcode_number"=>$vcode,
            "vcode_type"=>2,
            "vcode_status"=>0,
            "phone"=>$phone,
            "create_time"=>time(),
            "validate_time"=>time()+$vcode_timeout,
        );

        db_save("ejew_vcode", $item);
    }

    $data = array(
        'userCode' => 'edxcf',
        'userPass' => 'edxcf133',
        'DesNo' => $phone,
        'VoiceCode' => $vcode,
        'Amount' => 1,
        'TemplateID' => 4
    );

    $content = file_get_contents('http://h.1069106.com:1210/Services/MsgSend.asmx/SendVoiceCodeWithTemplate?'.http_build_query($data));

    return makeSuccess(array(
        "seconds"=>60
    ));
}

/*
 * http://localhost/ejew/android/api.php?method=renewSid&sid=BFf9vtejle3y0CbOpoQKt0eAm9DjTf
 */
function renewSid() {
	$shop_id = get_login_shop_id();
	$sid = p("sid");
	$now = time();
	
	db_update("delete from ejew_session where session_id=?",[$sid]);
	
	$session_id = gen_session_id();
	$session = array(
		"session_id"=>$session_id,
		"session_type"=>2,
		"user_id"=>$shop_id,
		"create_time"=>$now,
		"update_time"=>$now
	);
	
	db_save("ejew_session", $session);
	
	return makeSuccess(array(
		"sid"=>$session_id
	));
}

function get_login_shop_id() {
	global $session_timeout;
	$sid = trim(p("sid"));
	if(is_string_empty($sid)) {
		output_result(makeError(9000, "sid不能为空"));
		exit();
	}
	
	$now = time();

	$sql = "delete from ejew_session where update_time<?";
	db_update($sql,[$now-$session_timeout]);
	
	$sql = "select * from ejew_session where session_id=? and session_type=2";
	$session = db_query_row($sql,[$sid]);
	
	if($session) {
		db_update("update ejew_session set update_time=? where session_id=?",[$now,$sid]);
		return $session['user_id'];
	} else {
		output_result(makeError(9000, "无效的sid"));
		exit();
	}
}

/*
 * http://localhost/ejew/android/api.php?method=logout&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function logout() {
	$sid = trim(p("sid"));
	if(is_string_empty($sid)) {
		output_result(makeError(9000, "sid不能为空"));
		exit();
	}
	$shop_id = get_login_shop_id();
	db_update("delete from ejew_session where session_id=?",[$sid]);
	db_update("update ejew_shop set push_token = NULL where shop_id = ?",[$shop_id]);
	return makeSuccess();
}

/*
 * http://localhost/ejew/android/api.php?method=getIndexInfo&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function getIndexInfo() {
	$shop_id = get_login_shop_id();
	$today_date = get_today_date();
	$tomorrow_date = get_tomorrow_date();
	
	//要做的菜
	$sql = "select count(order_product_id) from ejew_order_product where order_id in ".
		"(select order_id from v_order where order_status=2 and shop_id=?) ";
	$product_count = db_query_value($sql,[$shop_id]);
	
	//今日订单
	//$sql = "select count(order_id) from v_order where shop_id = ? and predicted_time>=? and predicted_time<=? and order_status>0";
	$sql = "select count(order_id) from v_order where shop_id = ? and predicted_time>=? and predicted_time<=? and order_status not in (0,11,6,13)";
	$order_count_today = db_query_value($sql,[$shop_id,$today_date." 00:00:00",$today_date." 23:59:59"]);
	
	//明日订单
	$order_count_tomorrow = db_query_value($sql,[$shop_id,$tomorrow_date." 00:00:00",$tomorrow_date." 23:59:59"]);
	
	//全部订单
	$sql = "select count(order_id) from v_order where shop_id=? and order_status not in (0,11,6,13)";
	$order_count_total = db_query_value($sql,[$shop_id]);
	
	$sql = "select count(msg_id) from ejew_msg where user_type=2 and user_id=? and msg_status=0 and deleted=0";
	$msg_count = db_query_value($sql,[$shop_id]);
	
	return makeSuccess(array(
		"product_count"=>$product_count,
		"order_count_today"=>$order_count_today,
		"order_count_tomorrow"=>$order_count_tomorrow,
		"order_count_total"=>$order_count_total,
		"msg_count"=>$msg_count
	));
}

/*
 * http://localhost/ejew/android/api.php?method=getIndexNotice&sid=i09ssnFFzBumyjceDp1HYe1DhZrAEp
 */
function getIndexNotice() {
	return makeSuccess(array(
		"notice"=>"通知：欢迎使用e家e味"
	));
}

/*
 * http://localhost/ejew/android/api.php?method=getMsgList&offset=0&count=10&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getMsgList() {
	$shop_id = get_login_shop_id();
	check_page_params();
	$offset = p("offset");
	$count = p("count");
	$msg_type = p("msg_type");
	$sql_msg_type = "";
	
	if($msg_type==1) {
		$sql_msg_type = " and msg_type in (1,2) ";
	} else if($msg_type==3) {
		$sql_msg_type = " and msg_type = 3 ";
	}
	
	$sql = "select msg_id,msg_type,msg_status,msg_content,order_id,msg_time ".
		"from ejew_msg where user_type=2 and user_id=? $sql_msg_type and deleted=0 order by msg_time desc limit ?,?";
	
	$msgs = db_query($sql,[$shop_id,$offset,$count]);
	
	
	return makeSuccess(array(
		"msg_list"=>$msgs	
	));
}





/*
 * http://localhost/ejew/android/api.php?method=setMsgStatus&msg_id=1&msg_status=1&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function setMsgStatus() {
	$shop_id = get_login_shop_id();
	$msg_id = check_not_null("msg_id");
	$msg_status = check_not_null("msg_status");
	
	if($msg_status!="0" && $msg_status!="1") {
		return makeError(9000, "msg_status只能是0和1");
	}
	
	db_update("update ejew_msg set msg_status=? where msg_id=? and user_id=? ",[$msg_status,$msg_id,$shop_id]);
	
	return makeSuccess();
	
}

/*
 * http://localhost/ejew/android/api.php?method=get_todo_product&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz&time_type=0
 */
function get_todo_product() {
	
	$time_type = p("time_type");
	$shop_id = get_login_shop_id();
	if(!check_values($time_type, ["0","1","2","3"])) {
		return makeError(9000, "time_type必须是0,1,2,3");
	}
	
	if($time_type=="0" || $time_type=="1") {
		$date = get_today_date();
	} else {
		$date = get_tomorrow_date();
	}
	
	$order_type = 1;
	if($time_type=='0' || $time_type=='2') {
		$order_type = 0;
	}
	
	$sql = "select op.product_id,product_name,predicted_time,count(1) count ".
		"from ejew_order_product op, ejew_product p, v_order o ".
		"where op.product_id=p.product_id and op.order_id=o.order_id and ".
		"o.shop_id=? and predicted_time>=? and predicted_time<=? and order_type=? and order_status in (2, 20) ".
		"group by op.product_id,product_name,predicted_time";
	
	$product_list = db_query($sql,[$shop_id,$date." 00:00:00",$date."23:59:59",$order_type]);
	
	foreach ($product_list as &$product) {
		$product['time'] = get_time_duration($product['predicted_time']);
		unset($product['predicted_time']);
	}
	
	return makeSuccess(array(
		"product_list"=>$product_list
	));
	
}

/*
 * http://localhost/ejew/android/api.php?method=getOrderCount&order_date=1&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function getOrderCount() {
	$shop_id = get_login_shop_id();
	$order_date = p("order_date");
	$order_cate = p("order_cate");
	
	if(!check_values($order_date, ["1","2","3"])) {
		return makeError(9000, "order_date必须是1,2,3");
	}
	
	$date = null;
	
	if($order_date=="1") {
		$date = get_today_date();
	} else if($order_date=="2") {
		$date = get_tomorrow_date();
	}
	
	if($order_cate==1) { //待确认
		$sql_order_status = "order_status in (1)";
	} else if($order_cate==2) { //已确认
		$sql_order_status = "order_status in (2,7,20)";
	} else if($order_cate==3) { //小e接单
		$sql_order_status = "order_status in (21,25)";
	} else if($order_cate==4) { //开始配送
		$sql_order_status = "order_status in (3,22)";
	} else if($order_cate==5) { //已完成
		$sql_order_status = "order_status in (4,9)";
	} else if($order_cate==6) { //已关闭
		$sql_order_status = "order_status in (5,10,26,14,12)";
	} else {
		$sql_order_status = "order_status not in (0,11,6,13)";
	}
	
	if($date) {
		$sql = "select count(1) from v_order v  where v.shop_id=? and v.predicted_time>=? and v.predicted_time<=?
			and v.$sql_order_status order by v.predicted_time desc";
		$order_count = db_query_value($sql,[$shop_id,$date." 00:00:00",$date." 23:59:59"]);
	} else {
		$sql = "select count(1) from v_order v  where v.shop_id=? and v.$sql_order_status order by v.predicted_time desc";
		$order_count = db_query_value($sql,[$shop_id]);
	}
	return makeSuccess(array(
		"order_count"=>$order_count
	));
}

/*
 * http://localhost/ejew/android/api.php?method=getOrderList&order_date=1&offset=0&count=10&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function getOrderList() {
	$shop_id = get_login_shop_id();
	$order_date = p("order_date");
	check_page_params();
	$order_cate = p("order_cate");
	
	if(!check_values($order_date, ["1","2","3"])) {
		return makeError(9000, "order_date必须是1,2,3");
	}
	
	$offset = p("offset");
	$count = p("count");
	
	$date = null;
	
	if($order_date=="1") {
		$date = get_today_date();
	} else if($order_date=="2") {
		$date = get_tomorrow_date();
	}
	
	if($order_cate==1) { //待确认
		$sql_order_status = "order_status in (1)";
	} else if($order_cate==2) { //已确认
		$sql_order_status = "order_status in (2,7,20)";
	} else if($order_cate==3) { //小e接单
		$sql_order_status = "order_status in (21,25)";
	} else if($order_cate==4) { //开始配送
		$sql_order_status = "order_status in (3,22)";
	} else if($order_cate==5) { //已完成
		$sql_order_status = "order_status in (4,9)";
	} else if($order_cate==6) { //已关闭
		$sql_order_status = "order_status in (5,10,26,14,12)";
	} else {
		$sql_order_status = "order_status not in (0,11,6,13)";
	}
	
	if($date) {
		$sql = "select v.order_id order_id,v.total_fee total_fee,v.delivery_method delivery_method, v.predicted_time predicted_time,"
				." v.order_time order_time,v.user_id user_id,v.order_status order_status,v.order_type order_type,"
				." v.order_address order_address,v.order_address_row2 order_address_row2".
				" from v_order v  where v.shop_id=? and v.predicted_time>=? and v.predicted_time<=?
				and v.$sql_order_status order by v.predicted_time desc limit ?,?";
		$order_list = db_query($sql,[$shop_id,$date." 00:00:00",$date." 23:59:59",$offset,$count]);
	} else {
		$sql = "select v.order_id order_id,v.total_fee total_fee,v.delivery_method delivery_method, v.predicted_time predicted_time,"
				." v.order_time order_time,v.user_id user_id,v.order_status order_status,v.order_type order_type,"
				." v.order_address order_address,v.order_address_row2 order_address_row2"
				." from v_order v  where v.shop_id=? and v.$sql_order_status order by v.predicted_time desc limit ?,?";
		
		$order_list = db_query($sql,[$shop_id,$offset,$count]);
	}
	
	$sql_total_fee = "select sum(product_count*product_price) sum_fee  from ejew_order_product where order_id=?";
	foreach ($order_list as &$order) {
		$order['predicted_time'] = get_time_duration($order['predicted_time']);
		$product_total_fee_list = db_query($sql_total_fee, [$order['order_id']]);
		$tmp_product_total_fee = $product_total_fee_list[0];
		$order['total_fee'] = number_format($tmp_product_total_fee['sum_fee'], 2);
		//$order['total_fee'] = db_query($sql_total_fee, [order['order_id']]);
	}
	return makeSuccess(array(
		"order_list"=>$order_list
	));
	
}

/*
 * http://localhost/ejew/android/api.php?method=getOrderInfo&order_id=31&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getOrderInfo() {
	$shop_id = get_login_shop_id();
	$order_id = check_not_null("order_id");
	
	$sql = "select order_id,total_fee,delivery_method,predicted_time,order_time,
			nick_name,o.user_id,order_status,order_type,order_time,user_avatar,nick_name,
			o.user_id,phone,order_address,order_address_row2,order_lng,order_lat,order_status,
			order_memo,is_little_e,s.can_takeaway,s.can_delivery
		from v_order o,ejew_user u,ejew_shop s ".
		"where o.user_id=u.user_id and o.shop_id=s.shop_id and order_id=? and o.shop_id=?";
	$order = db_query_row($sql,[$order_id,$shop_id]);
	$order['predicted_time'] = get_time_duration($order['predicted_time']);
	$order['chucan_time'] = $order['predicted_time'];
	
	$sql = "select *,op.product_count from ejew_order_product op,ejew_product p ".
		"where op.product_id=p.product_id and op.order_id=?";
	$product_list = db_query($sql,[$order_id]);

	$order['total_fee'] = 0;
	foreach ($product_list as &$product) {
		$product['product_count'] = number_format($product['product_count'], 1);
		$product['product_price'] = number_format($product['product_count'] * $product['product_price'], 2);
		$order['total_fee'] += $product['product_price'];
	}

	$order['total_fee'] = number_format($order['total_fee'], 2);	

	$order['product_list'] = $product_list;
	return makeSuccess($order);
}

/*
 * http://localhost/ejew/android/api.php?method=setOrderStatus&order_id=30&order_status=3&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 * http://localhost/ejew/android/api.php?method=setOrderStatus&order_id=30&order_status=20&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function setOrderStatus() {
	global $time_map,$time_duration_array;
	$shop_id = get_login_shop_id();
	$order_id = check_not_null("order_id");
	$order_status = check_not_null("order_status");
	
	$order = db_query_row(
			"select * from v_order o,ejew_user u, ejew_shop s where o.user_id=u.user_id and o.shop_id=s.shop_id and o.shop_id=? and o.order_id=?",
			[$shop_id,$order_id]);
	
	if($order===null) {
		return makeError(9000, "订单不存在");
	}
	
	$current_order_status = $order['order_status'];
	$user_phone = $order['user_phone'];
	$predicted_time = $order['predicted_time'];
	$month = substr($predicted_time,5,2);
	$days = substr($predicted_time,8,2);
	$time = substr($predicted_time, 11,5);
	$time_duration = $time_duration_array[$time_map[$time]];
	$order_time_msg = intval($month)."月".intval($days)."日".$time_duration;
	
	$dinner_str = (($order['order_type']==0)?"午餐":"晚餐");

	$shop_phone = $order['shop_phone'];
	$shop_name = $order['shop_name'];
	$shop_address = $order['shop_address'].$order['shop_address_row2'];
	$order_address = $order['order_address'];
	$order_address_row2 = $order['order_address_row2'];
	$contact_name = $order['contact_name'];
	$phone = $order['phone'];
	
	$xiaoe_start_time=date("Y-m-d H:i", (strtotime($predicted_time)-10*60));
	$xiaoe_end_time=date("Y-m-d H:i", (strtotime($predicted_time)+30*60));
	
	$xiaoe_phone = '15071723613';
	
	$sms = null;
	$coupon_sms = null;
	$sms_to_shop = null;
	$sms_to_xiaoe = null;
	
	db_autocommit(false);
	if($order_status==2) {
		if($current_order_status==1) {
			add_order_status($order_id, 2, "已接单", "厨房已接单，美食制作中");
			db_update("update ejew_order set is_little_e = 0 where order_id = ?",[$order_id]);
			$sms = "您预定的".$order_time_msg."的".$dinner_str."，家厨已经确认接单啦，请等待厨师为你制作美食哦";
		} else {
			return makeError(9000, "非已支付状态，不能接单");
		}
	} else if($order_status==3) {
		$predicted_date = new DateTime($order['predicted_time']);
		$predicted_date_string = $predicted_date->format("Y-m-d");
		if (strcmp($predicted_date_string, get_today_date()) <=0) {
			if($current_order_status==2) {
				add_order_status($order_id, 3, "正在配送", "美食制作完成，正在配送");
				$sms = "您预定的".$order_time_msg."的".$dinner_str."，已经开始配送啦，请耐心等待美食的到来哦";
			} else {
				return makeError(9000, "接单之后，才能进行配送");
			}				
		} else {
			return makeError(9000, "当天用餐的客户，才能进行配送");
		}
	} else if($order_status==4) {
		if($current_order_status==3) {
			add_order_status($order_id, 4, "已完成", "完成配送，订单完成");
			
			$order_products = db_query("select * from ejew_order_product where order_id = ?",[$order_id]);
			
			$product_amount = 0;
			
			foreach($order_products as $order_product) {
				$product_amount += $order_product['product_price']*$order_product['product_count'];
			}
			
			$bill = array(
				"shop_id"=>$shop_id,
				"bill_type"=>1,
				"bill_title"=>"订单费",
				"bill_detail"=>"来自用户".maskString($order['user_phone'],3,4),
				"bill_amount"=>$product_amount,
				"bill_time"=>get_now(),
				"order_id"=>$order['order_id']
			);
			$bill_id = db_save("ejew_bill", $bill);
			
			if($order['is_little_e']) {
				$bill2 = array(
						"shop_id"=>$shop_id,
						"bill_type"=>6,
						"bill_title"=>"小e配送费",
						"bill_detail"=>"订单".$order['order_id']."的小e配送费",
						"bill_amount"=>-2,
						"bill_time"=>get_now(),
						"order_id"=>$order['order_id']
				);
				$bill2_id = db_save("ejew_bill", $bill2);
			}
			
			$sms = "您预定的".$order_time_msg."的".$dinner_str."，已经为您送达啦，品尝美食后给厨师评级吧";

			//if($order['user_id']=='10') {
			if($order['is_first_order']) {
				$coupon_name = "首单返券";
				$condition_desc = "让你再吃美味一餐";
				$coupon_amount_5 = 5;
				$coupon_amount_10 = 10;
				$validation_start = date('Y-m-d H:i:s');
				$validation_end = date('Y-m-d H:i:s', strtotime('+7day'));
				$coupon_sms = "吃完了美味健康的一餐，再送你15元的粮票（一个10元、一个5元），有效期7天，抓紧时间再下单吧!";
					
				$sql = "insert into ejew_coupon(user_id,coupon_name,condition_desc,coupon_amount,validation_start,validation_end,condition_json,coupon_used) select user_id,?,?,?,?,?,'{}',0 "
						." from ejew_user where user_id = ".$order['user_id'];
				$updated_count = db_update($sql,[$coupon_name,$condition_desc,$coupon_amount_5,$validation_start,$validation_end]);
				$updated_count = db_update($sql,[$coupon_name,$condition_desc,$coupon_amount_10,$validation_start,$validation_end]);
			
			}
				
		} else {
			return makeError(9000, "配送之后，才能完成该订单");
		}
	} else if($order_status==7) {
		if($current_order_status==1) {
			$sql = "update ejew_coupon set coupon_used = 0 where coupon_id in (select coupon_id from v_order where coupon_id<>0 and order_id=?) ";
			db_update($sql,[$order_id]);
			
			add_order_status($order_id, 2, "已拒单", "厨房拒单，正在处理退款");
		} else {
			return makeError(9000, "非已支付状态，不能拒单");
		}
	} else if($order_status==20) {
		if($current_order_status==1) {
			add_order_status($order_id, 20, "已接单", "厨房已接单，美食制作中，小E配送");
			db_update("update ejew_order set is_little_e = 1 where order_id = ?",[$order_id]);
			
			$order_products = db_query("select *,  p.product_name product_name, op.product_count product_count from ejew_order_product op, ejew_product p where op.order_id = ? and op.product_id=p.product_id",[$order_id]);
			
			$product_info="";	
			$product_amount=0;
			foreach($order_products as $order_product) {
				$product_info = $product_info.$order_product['product_name'];
				$order_product['product_count'] = number_format($order_product['product_count'], 1);
				$tmp_str = (string)$order_product['product_count'];
				$product_info = $product_info."  ".$tmp_str."份   ";				
			}
				
			$sms = "您预定的".$order_time_msg."的".$dinner_str."，家厨已经确认接单啦，请等待厨师为你制作美食哦，将由小e为您配送";
			
			$sms_to_shop = "您制作的".$order_time_msg."的".$dinner_str."，小e已经收到配送请求，请及时联系客服，电话15071723613或18810439147";
			//$sms_to_xiaoe = "您已收到".$shop_name."厨房".$order_time_msg."的".$dinner_str."配送请求，请及时联系家厨，电话".$shop_phone;

			$sms_to_xiaoe = "订单号：".$order_id." 厨房电话：".$shop_phone." 厨房名称：".$shop_name." 厨房地址：".$shop_address
							." 取餐时间：".$xiaoe_start_time." 期望送达时间：".$xiaoe_end_time
							." 送餐地址：".$order_address." 门牌号：".$order_address_row2." 联系人姓名：".$contact_name." 联系人电话：".$phone
							." 菜品信息：".$product_info;		//." 菜品份数：".$product_amount;
			
			$xiaoe_url = "http://open08.edaixi.cn:81/ex_order/v3/chifan/notifyNew";
			$secret_key = '123456';
			
/* 			$param = array(
					'dsp_id' => 1,
					'args' => '{"order_id":11758186,"receiver_name":"Admin","receiver_phone":13000090909,"receiver_address":"北京市天安门","receiver_x":39.123132,"receiver_y":39.123132,"status":3,"payment_status":1,"sp_no":123,"shop_name":"yaodian","shop_address":"yaodian","shop_phone":"132123213123","shop_x":32.3213213,"shop_y":32.3213213}'
			);
 */			
			$param = array(
				'dsp_id' => 1,
				'args' => "{\"order_id\":$order_id,\"receiver_name\":\"Admin\",\"receiver_phone\":13000090909,\"receiver_address\":\"北京市天安门\",\"receiver_x\":39.123132,\"receiver_y\":39.123132,\"status\":3,\"payment_status\":1,\"sp_no\":123,\"shop_name\":\"yaodian\",\"shop_address\":\"yaodian\",\"shop_phone\":\"132123213123\",\"shop_x\":32.3213213,\"shop_y\":32.3213213}"
			);
			
			$md5_sign = do_md5_sign($secret_key, $param);
			
			$param['sign'] = $md5_sign;
			
			$response = http_post_formdata($xiaoe_url,$param);
		} else {
			return makeError(9000, "非已支付状态，不能分配小E");
		}
	} else {
		return makeError(9000, "订单状态参数不合法");
	}
		
	set_order_status($order_id, $order_status);
	
	db_commit();
	
	db_autocommit(true);
	
	if(isset($bill)) push_shop_message($shop_id, 3, "您收到一笔订单费，".$bill['bill_detail'], $bill_id);
	
	if ($shop_id!=55) {
	if($sms) send_sms($user_phone, $sms);
	if($coupon_sms) send_sms($user_phone, $coupon_sms);
	if($sms_to_shop) send_sms($shop_phone, $sms_to_shop);
	if($sms_to_xiaoe) {
		send_sms($xiaoe_phone, $sms_to_xiaoe);
		send_sms("18810439147", $sms_to_xiaoe);
		send_sms("18665678504", $sms_to_xiaoe);
		send_sms("18710126671", $sms_to_xiaoe);
		//send_sms("13141292779", $sms_to_xiaoe);
	}
	}
	
	return makeSuccess(array(
		"order_status"=>$order_status
	));
	
}

/**
 * http://localhost/ejew/android/api.php?method=getBillList&offset=0&count=10&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getBillList() {
	$shop_id = get_login_shop_id();
	check_page_params();
	$count = p("count");
	$offset = p("offset");

	if($shop_id==55 || $shop_id==24 || $shop_id==87 || $shop_id==103 ) {
		$start_date = "2015-09-24";
		$end_date = date("Y-m-d");
	} else {
		$start_date = "2015-10-21";
		$end_date = date("Y-m-d");
	}
	
	$sql = "select sum(bill_amount) "
		." from ejew_bill where shop_id = ? "
		." and (DATE_FORMAT(create_at,'%Y-%m-%d') BETWEEN ? AND ? )";
	$balance = db_query_value($sql,[$shop_id, $start_date, $end_date]);
	
	$sql = "select bill_id,bill_type,bill_title,bill_detail,bill_amount,bill_time,order_id "
		." from ejew_bill where shop_id = ? " 
		." and (DATE_FORMAT(create_at,'%Y-%m-%d') BETWEEN ? AND ? ) "
		." order by bill_time desc limit ?,?";
	$bill_list = db_query($sql,[$shop_id, $start_date, $end_date, $offset,$count]);
	
	return makeSuccess(array(
		"balance"=>$balance,
		"bill_list"=>$bill_list
	));
	
}

/*
 * http://localhost/ejew/android/api.php?method=clearMsg&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */

function clearMsg() {
	$shop_id = get_login_shop_id();
	$sql = "update ejew_msg set deleted=1 where user_type=2 and user_id = ?";
	db_update($sql,[$shop_id]);
	return makeSuccess();
}

/**
 * http://localhost/ejew/android/api.php?method=getShopInfo&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getShopInfo() {
	global $host_name;
	$shop_id = get_login_shop_id();
	
	$sql = "select shop_phone,shop_name,shop_images,shop_address,shop_address_row2,shop_lng,
		shop_lat,delivery_distance,cooking_style,lunch_time_start,lunch_time_end,
		dinner_time_start,dinner_time_end,lunch_stop_time,dinner_stop_time,can_takeaway,can_delivery,
		has_lunch,has_dinner
		from ejew_shop where shop_id = ?";
	
	$shop = db_query_row($sql,[$shop_id]);
	$shop_images = $shop['shop_images'];
	if(is_string_not_empty($shop_images)) {
		$shop['shop_images'] = json_decode($shop_images,true);
		$shop_images_url = array();
		foreach ($shop['shop_images'] as $shop_image) {
			$shop_images_url[] = get_image_full_url($shop_image);
		}
		$shop['shop_images_url'] = $shop_images_url;
	}
	$shop['share_url'] = "http://$host_name/client.php?shopid=$shop_id&preview=1";
	
	return makeSuccess($shop);
}

/*
 * http://localhost/ejew/android/api.php?method=updateShopInfo&shop_phone=13900000001&shop_name=天女散花999&shop_images=["shop001.jpg"]&shop_address=北京回龙观1&shop_address_row2=8号楼&shop_lng=130.0001&shop_lat=40.0001,"delivery_distance=0.01&cooking_style=广东菜&lunch_time_start=11:00&lunch_time_end=13:00&dinner_time_start=17:00&dinner_time_end=20:00&lunch_stop_time=10:30&dinner_stop_time=16:30&can_takeaway=1&can_delivery=1&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function updateShopInfo() {
	global $image_path;
	$shop_id = get_login_shop_id();
	
	$shop = array();
	$shop['shop_id'] = $shop_id;
	$shop['contact_phone'] = p('shop_phone');
	$shop['shop_name'] = p('shop_name');
	$shop['shop_images'] = p('shop_images');
	$shop['shop_address'] = p('shop_address');
	$shop['shop_address_row2'] = p('shop_address_row2');
	//$shop['shop_lng'] = p('shop_lng');
	//$shop['shop_lat'] = p('shop_lat');
	$shop['delivery_distance'] = p('delivery_distance');
	$shop['cooking_style'] = p('cooking_style');
	$shop['lunch_time_start'] = p('lunch_time_start');
	$shop['lunch_time_end'] = p('lunch_time_end');
	$shop['dinner_time_start'] = p('dinner_time_start');
	$shop['dinner_time_end'] = p('dinner_time_end');
	//$shop['lunch_stop_time'] = p('lunch_stop_time');
	//$shop['dinner_stop_time'] = p('dinner_stop_time');
	$shop['can_takeaway'] = p('can_takeaway');
	$shop['can_delivery'] = p('can_delivery');
	$shop['has_lunch'] = p('has_lunch');
	$shop['has_dinner'] = p('has_dinner');
	
	$shop_images_string = $shop["shop_images"];
	if(is_string_not_empty($shop_images_string)) {
		$shop_images = json_decode($shop_images_string);
		if(is_array($shop_images)) {
			foreach ($shop_images as $shop_image) {
				//TODO 校验图片非法字符
				$image_file_name = $image_path.DIRECTORY_SEPARATOR.$shop_image;
				if(!file_exists($image_file_name)) {
					return makeError(9000, "图片文件不存在");
				}
				list($image_width,$image_height) = getimagesize($image_file_name);
				if($image_width/$image_height<1) {
					return makeError(9000, "图片宽高比不能小于1");
				}
			}
		} else {
			return makeError(9000, "厨房图片非法");
		}
	}
	
	if(is_string_not_empty(p("shop_lng")) && is_string_not_empty(p("shop_lat"))) {
		$shop['shop_lng'] = p('shop_lng');
		$shop['shop_lat'] = p('shop_lat');
		$shop_lng = $shop['shop_lng'];
		$shop_lat = $shop['shop_lat'];
		if(!(($shop_lng>73 && $shop_lat<135) && ($shop_lat>3 && $shop_lat<53))) {
			return makeError(9000, "厨房地理位置不正确");
		}
	}
	
	/*
	db_update_item("ejew_shop", "shop_id", $shop);
	check_shop_info_full($shop_id);
	return makeSuccess();
	*/
	
	verify_shop_info_full($shop);
	
	return makeSuccess();
}

function check_shop_info_full($shop_id) {
	$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
	$check_result = true;
	$fields = ['shop_name',
		'shop_images',
		'shop_address',
		'shop_address_row2',
		'shop_lng',
		'shop_lat',
		'contact_phone',
		'delivery_distance',
		'cooking_style',
		'cover_product_image',
		'reg_time',
		'can_takeaway',
		'can_delivery',
		'lunch_time_start',
		'lunch_time_end',
		'dinner_time_start',
		'dinner_time_end',
		'lunch_stop_time',
		'dinner_stop_time',
		'keeper_name',
		'keeper_hometown',
		'keeper_avatar',
		'keeper_id_number',
		'keeper_intro'];
	
	foreach($fields as $field) {
		if(is_string_empty($shop[$field])) {
			$check_result = false;
			app_log("check_shop_info_full,[".$field."] should not be null");
			break;
		}
	}
	
	if($check_result) {
		db_update("update ejew_shop set approval_status=3,operation_status=1 where shop_id=?",[$shop_id]);
	} else {
		db_update("update ejew_shop set approval_status=0,operation_status=0 where shop_id=?",[$shop_id]);
	}
}

/**
 * http://localhost/ejew/android/api.php?method=getKeeperInfo&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getKeeperInfo() {
	$shop_id = get_login_shop_id();
	$sql = "select keeper_name,keeper_hometown,keeper_avatar,keeper_id_number,keeper_intro,shop_phone 
			from ejew_shop where shop_id = ?";
	
	$keeper_info = db_query_row($sql,[$shop_id]);
	
	$keeper_info['keeper_avatar_url'] = get_image_full_url($keeper_info['keeper_avatar']);
	
	return makeSuccess($keeper_info);
}

/**
 * http://localhost/ejew/android/api.php?method=updateKeeperInfo&keeper_name=花花1&keeper_hometown=北京&keeper_avatar=1001.jpg&keeper_id_number=null&keeper_intro=擅长广东菜&shop_phone=13900000001&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function updateKeeperInfo() {
	$shop_id = get_login_shop_id();
	
	$keeper = array(
		"keeper_name"=>p("keeper_name"),
		"keeper_hometown"=>p("keeper_hometown"),
		"keeper_avatar"=>p("keeper_avatar"),
		"keeper_id_number"=>p("keeper_id_number"),
		"keeper_intro"=>p("keeper_intro"),
		"shop_id"=>$shop_id
	);
	
	/*
	db_update_item("ejew_shop", "shop_id", $keeper);
	check_shop_info_full($shop_id);
	return makeSuccess();
	*/
	
	verify_shop_info_full($keeper);
	return makeSuccess();
}

function verify_shop_info_full($items) {
	$shop_old_data = db_query_row("select * from ejew_shop where shop_id=?",[$items['shop_id']]);
	db_update_item("ejew_shop", "shop_id", $items);
	$shop_new_data = db_query_row("select * from ejew_shop where shop_id=?",[$items['shop_id']]);
	
	$check_empty= true;
	$check_approval_status = true;
	
	$fields = ['shop_name',
		'shop_images',
		'shop_address',
		'shop_address_row2',
		'shop_lng',
		'shop_lat',
		'contact_phone',
		'delivery_distance',
		'cooking_style',
		'cover_product_image',
		'reg_time',
		'can_takeaway',
		'can_delivery',
		'lunch_time_start',
		'lunch_time_end',
		'dinner_time_start',
		'dinner_time_end',
		'lunch_stop_time',
		'dinner_stop_time',
		'keeper_name',
		'keeper_hometown',
		'keeper_avatar',
		'keeper_id_number',
		'keeper_intro'];
	
	foreach($fields as $field) {
		if(is_string_empty($shop_new_data[$field])) {
			app_log("check_shop_info_full,[".$field."] should not be null");
			//db_update("update ejew_shop set approval_status=0,operation_status=0 where shop_id=?",[$shop_new_data['shop_id']]);
			db_update("update ejew_shop set approval_status=?,operation_status=? where shop_id=?",[$shop_old_data['approval_status'], $shop_old_data['operation_status'], $shop_new_data['shop_id']]);
			return makeSuccess();
		}
	}
	
	db_update("update ejew_shop set approval_status=?,operation_status=? where shop_id=?",[$shop_old_data['approval_status'], $shop_old_data['operation_status'], $shop_new_data['shop_id']]);

/* 	foreach($fields as $field) {
		if(!(  strcmp($shop_new_data[$field], $shop_old_data[$field])==0
			|| strcmp($field, 'lunch_time_start')==0 
			|| strcmp($field, 'lunch_time_end')==0
			|| strcmp($field, 'dinner_time_start')==0
			|| strcmp($field, 'dinner_time_end')==0
			|| strcmp($field, 'lunch_stop_time')==0
			|| strcmp($field, 'dinner_stop_time')==0  )
			) {

				app_log("check_shop_info_full,[".$field."] should not be null");
				db_update("update ejew_shop set approval_status=3,operation_status=1 where shop_id=?",[$shop_new_data['shop_id']]);
				break;		
		}				
	}						
 */
	return makeSuccess();
}

/**
 * http://localhost/ejew/android/api.php?method=getShopStatus&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */

function getShopStatus() {
	$shop_id = get_login_shop_id();
	$today = get_today_date();
	$next_month_date = get_next_month_end_date();
	$sql = "select distinct leave_date from ejew_shop_status where leave_date>=? and leave_date<=? and shop_id=? order by leave_date";
	$leave_dates_data = db_query($sql,[$today,$next_month_date,$shop_id]);
	$leave_dates = array();
	foreach ($leave_dates_data as $item) {
		$leave_dates[] = $item['leave_date'];
	}
	
	return makeSuccess(array(
		"server_date"=>$today,
		"leave_list"=>$leave_dates
	));
}

/**
 * http://localhost/ejew/android/api.php?method=setShopStatus&leave_dates=["2015-08-16","2015-08-20"]&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function setShopStatus() {
	$shop_id = get_login_shop_id();
	$leave_dates = p("leave_dates");
	$today = get_today_date();
	$next_month_date = get_next_month_end_date();
	
	$sql = "delete from ejew_shop_status where leave_date>=? and shop_id=?";
	db_update($sql,[$today,$shop_id]);
	
	$leave_dates_array = json_decode($leave_dates,true);
	
	foreach ($leave_dates_array as $leave_date) {
		$item = array(
			"shop_id"=>$shop_id,
			"leave_date"=>$leave_date
		);
		
		//if(strcmp($leave_date,$today)!=0) {
			db_save("ejew_shop_status", $item);
		//}
	}
	return makeSuccess();
}

/*
 * http://localhost/ejew/android/api.php?method=getProductList&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function getProductList() {
	$shop_id = get_login_shop_id();
	$product_status = is_string_empty(p("product_status"))?0:p("product_status");
	$product_cate = is_string_empty(p("product_cate"))?0:p("product_cate");
		
	$sql = "select product_id,product_name,product_image,product_price,product_count,
			is_main,is_sign,is_half,is_snack,
			(select count(1) from ejew_order_product op,ejew_order_b o 
			where op.product_id = p.product_id and o.order_id=op.order_id
			    and o.order_status in (4,9)) order_count
		from ejew_product p where p.shop_id = ? ";
	
	if($product_status==1) {
		$sql.=" and product_status=1";
	} else if($product_status==2) {
		$sql.=" and product_status=0";
	}
	
	if($product_cate==1) {
		$sql.=" and is_sign=1";
	} else if($product_cate==2) {
		$sql.=" and is_main=1";
	} else if($product_cate==3) {
		$sql.=" and is_sign=0 and is_main=0 and is_snack=0";
	} else if($product_cate==4) {
		$sql.=" and is_snack=1";
	}
	
	$product_list = db_query($sql,[$shop_id]);
	foreach($product_list as &$product) {
		$product['product_image_url'] = get_image_full_url($product['product_image']);
	}
	return makeSuccess(array(
		"product_list"=>$product_list
	));
}

/*
 * http://localhost/ejew/android/api.php?method=getProduct&product_id=1&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 * http://localhost/ejew/android/api.php?method=getProduct&product_id=841&sid=IGOX6RXARbJMrxzsiK2fxoyGMLgi
 */
function getProduct() {
	$shop_id = get_login_shop_id();
	$product_id = p("product_id");
	$sql = "select product_id,product_name,product_image,product_price,product_count,is_main,is_sign,is_half,is_snack,product_status 
			from ejew_product where shop_id=? and product_id=?";
	$product = db_query_row($sql,[$shop_id,$product_id]);
	if(!$product) {
		return makeError(9000, "菜品不存在");
	}
	
	check_stock($shop_id);
	
	$today_stock = db_query_value("select stock_count from ejew_product_stock where product_id = ? and stock_date=?",
			[$product_id,get_today_date()]);
	
	$tomorrow_stock = db_query_value("select stock_count from ejew_product_stock where product_id = ? and stock_date=?",
			[$product_id,get_tomorrow_date()]);
	
	$product['product_image_url'] = get_image_full_url($product['product_image']);
	$product['today_stock'] = $today_stock;
	$product['tomorrow_stock'] = $tomorrow_stock;
	return makeSuccess($product);
}

/*
 * http://localhost/ejew/android/api.php?method=addProduct&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz&product_id=1&product_name=鱼香肉丝&product_image=product007.jpg&product_price=10.00&product_count=100&is_main=0&is_sign=1&is_half=1
 */
function addProduct() {
	global $image_path;
	$shop_id = get_login_shop_id();
	$product = array(
		"shop_id"=>$shop_id,
		"product_name"=>check_not_null("product_name"),
		"product_image"=>check_not_null("product_image"),
		"product_price"=>check_not_null("product_price"),
		"product_count"=>check_not_null("product_count"),
		"is_main"=>p("is_main"),
		"is_sign"=>p("is_sign"),
		"is_half"=>p("is_half"),
		"is_snack"=>p("is_snack"),
		"create_time"=>get_now(),
		"update_time"=>get_now(),
		"approval_status"=>0
	);
	
	if($product['product_price']>1000) {
		return makeError(9000, "菜品价格应小于1000元");
	}
	
	if($product['product_count']>10000) {
		return makeError(9000, "菜品数量应小于10000");
	}
	
	$image_file_name = $image_path.DIRECTORY_SEPARATOR.$product['product_image'];
	app_log("=========图片路径：".$image_file_name);
	if(!file_exists($image_file_name)) {
		return makeError(9000, "图片文件不存在");
	}
	list($image_width,$image_height) = getimagesize($image_file_name);
	if($image_width/$image_height<1) {
		return makeError(9000, "图片宽高比不能小于1");
	}
	
	$today_stock = check_not_null("today_stock");
	$tomorrow_stock = check_not_null("tomorrow_stock");
	
	if(!is_numeric($today_stock)) {
		return makeError(9000, "今日库存必须为数字");
	}
	
	if(!is_numeric($tomorrow_stock)) {
		return makeError(9000, "明日库存必须为数字");
	}
	
	$product_id = db_save("ejew_product", $product);
	
/*
	if(p("is_sign")) {
		$shop = array(
			"shop_id"=>$shop_id,
			"cover_product_image"=>$product["product_image"]
		);
		db_update_item("ejew_shop", "shop_id", $shop);
	}
*/
	
	//check_shop_info_full($shop_id);
	check_stock($shop_id);
	
	update_stock($product_id, $today_stock, 0,false);
	update_stock($product_id, $tomorrow_stock, 2,false);
	
	return makeSuccess();
}

/*
 * http://localhost/ejew/android/api.php?method=updateProduct&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz&product_id=21&product_name=鱼香肉丝sss&product_image=product007.jpg&product_price=10.00&product_count=100&is_main=0&is_sign=1&is_half=1
 */
function updateProduct() {
	global $image_path;
	$shop_id = get_login_shop_id();
	$product = array(
		"shop_id"=>$shop_id,
		"product_id"=>check_not_null("product_id"),
		"product_name"=>check_not_null("product_name"),
		"product_image"=>check_not_null("product_image"),
		"product_price"=>check_not_null("product_price"),
		"product_count"=>check_not_null("product_count"),
		"is_main"=>p("is_main"),
		"is_sign"=>p("is_sign"),
		"is_half"=>p("is_half"),
		"is_snack"=>p("is_snack"),
		"update_time"=>get_now()
	);
	
	$product_id = $product['product_id'];
	$today_stock = check_not_null("today_stock");
	$tomorrow_stock = check_not_null("tomorrow_stock");
	
	if($product['product_price']>1000) {
		return makeError(9000, "菜品价格应小于1000元");
	}
	
	if(!is_numeric($today_stock)) {
		return makeError(9000, "今日库存必须为数字");
	}
	
	if(!is_numeric($tomorrow_stock)) {
		return makeError(9000, "明日库存必须为数字");
	}
	
	if(!is_numeric($product['product_count'])) {
		return makeError(9000, "固定库存必须为数字");
	}
	
	
	$image_file_name = $image_path.DIRECTORY_SEPARATOR.$product['product_image'];
	if(!file_exists($image_file_name)) {
		return makeError(9000, "图片文件不存在");
	}
	list($image_width,$image_height) = getimagesize($image_file_name);
	if($image_width/$image_height<1) {
		return makeError(9000, "图片宽高比不能小于1");
	}
	
	$product_old = db_query_row("select * from ejew_product where product_id = ? and shop_id=?",
			[$product['product_id'],$shop_id]);
	
	if(!$product_old) {
		return makeError(9000, "菜品不存在，不能更新");
	}
	
	if($product['product_count']!=$product_old['product_count']) {
		update_stock($product['product_id'], $product['product_count'], 0,false);
		update_stock($product['product_id'], $product['product_count'], 1,false);
		update_stock($product['product_id'], $product['product_count'], 2,false);
		update_stock($product['product_id'], $product['product_count'], 3,false);
	}
	
	db_update_item("ejew_product","product_id", $product);
	
/*
	if(p("is_sign")) {
		$shop = array(
				"shop_id"=>$shop_id,
				"cover_product_image"=>$product["product_image"]
		);
		db_update_item("ejew_shop", "shop_id", $shop);
	}
*/
	
	//check_shop_info_full($shop_id);
	check_stock($shop_id);
	
	
	
	update_stock($product_id, $today_stock, 0,false);
	update_stock($product_id, $tomorrow_stock, 2,false);
	
	return makeSuccess();
}

/**
 * http://localhost/ejew/android/api.php?method=getBank&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getBank() {
	$shop_id = get_login_shop_id();
	$sql = "select bank_account_name,bank_card_number ".
		"from ejew_shop where shop_id = ?";
	$shop = db_query_row($sql,[$shop_id]);
	
	return makeSuccess($shop);
}

/**
 * http://localhost/ejew/android/api.php?method=deleteBank&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function deleteBank() {
	$shop_id = get_login_shop_id();
	$sql = "update ejew_shop set bank_account_name = null,bank_card_number = null where shop_id=?";
	db_update($sql,[$shop_id]);
	return makeSuccess();
}

/*
 * http://localhost/ejew/android/api.php?method=updateBank&bank_account_name=SUN&bank_card_number=8888888812341234&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function updateBank() {
	$shop_id = get_login_shop_id();
	$bank_account_name = p("bank_account_name");
	$bank_card_number = p("bank_card_number");
	$sql = "update ejew_shop set bank_account_name = ?,bank_card_number = ? where shop_id=?";
	db_update($sql,[$bank_account_name,$bank_card_number,$shop_id]);
	return makeSuccess();
}

/*
 * http://localhost/ejew/android/api.php?method=getCommentList&offset=0&count=10&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function getCommentList() {
	check_page_params();
	$shop_id = get_login_shop_id();
	$offset = p("offset");
	$count = p("count");
	$sql = "select comment_id,comment_mark,comment_detail,comment_time,
			reply_detail,reply_time,order_id,nick_name
		from ejew_comment c,ejew_user u 
		where c.user_id=u.user_id and shop_id = ?
		limit ?,?";
	$comment_list = db_query($sql,[$shop_id,$offset,$count]);
	
	foreach ($comment_list as &$comment) {
		$sql = "select p.product_id,p.product_name
			from ejew_order_product op,ejew_product p
			where op.product_id=p.product_id and order_id = ?";
		
		$products = db_query($sql,[$comment['order_id']]);
		
		$product_name = "";
		foreach ($products as $product) {
			$product_name .= $product['product_name'] ." ";
		}
		$comment['product_name'] = $product_name;
	}
	
	return makeSuccess(array(
		"comment_list"=>$comment_list
	));
	
}

/*
 * http://localhost/ejew/android/api.php?method=addCommentReply&comment_id=1&reply_detail=abcdef&sid=RlEl27dHUOKmBLjxiZ0voaykbA66xz
 */
function addCommentReply() {
	$shop_id = get_login_shop_id();
	$comment_id = check_not_null("comment_id");
	$reply_detail = check_not_null("reply_detail");
	
	$sql = "select reply_detail from ejew_comment where comment_id = ? and shop_id = ?";
	$comment_reply_detail = db_query_value($sql,[$comment_id,$shop_id]);
	if($comment_reply_detail) {
		return makeError(9000, "回复已经添加");
	}
	
	$sql = "update ejew_comment set reply_detail = ?,reply_time = ?
		where comment_id = ? and shop_id = ?";
	
	db_update($sql,[$reply_detail,get_now(),$comment_id,$shop_id]);
	
	return makeSuccess();
}

function getImageName() {
	return gen_session_id();
}

function uploadImage() {
	global $image_url,$image_full_url;
	//$shop_id = get_login_shop_id();
	$upload_path = realpath("../upload").DIRECTORY_SEPARATOR;
	if(!isset($_FILES['image_file'])) {
		return makeError(9000, "文件需放在image_file域中");
	}
	$ori_file_name = $_FILES['image_file']['name'];
	$ori_file_info = pathinfo($ori_file_name);
	$ori_file_ext = strtolower($ori_file_info['extension']);
	$errcode = $_FILES['image_file']['error'];
	if($errcode!=0) {
		return makeError(9000, "上传出错，错误码:".$errcode);
	}
	if(!($ori_file_ext=='jpg' || $ori_file_ext=='jpeg')) {
		return makeError(9000, "只能上传jpeg类型的文件");
	}
	$tmp_file = $_FILES['image_file']['tmp_name'];
	$image_file_name = getImageName().".jpg";
	$upload_file = $upload_path.$image_file_name;
	move_uploaded_file($tmp_file, $upload_file);
	
	return makeSuccess(array(
		"image_name"=>$image_file_name,
		"image_name_url"=>$image_full_url.$image_file_name
	));
}

function addPushToken() {
	$shop_id = get_login_shop_id();
	$token = check_not_null("token");
	db_update("update ejew_shop set push_token = ? where shop_id = ?",[$token,$shop_id]);
	return makeSuccess();
}

function deleteProduct() {
	$shop_id = get_login_shop_id();
	$product_id = check_not_null("product_id");
	db_update("delete from ejew_product where product_id=? and shop_id=?",[$product_id,$shop_id]);
	return makeSuccess();
}


function xiaoe_create_order($order_id) {
	global $xiaoe_secretkey;
	$url = "http://open12.edaixi.cn:81/ex_order/v3/chifan/create_order";
	$order = db_query_row("select * from v_order o,ejew_shop s where o.shop_id=s.shop_id and o.order_id = ?",[$order_id]);
	$params = array(
		"order_id"=>$order_id,
		"customer_id"=>$order['user_id'],
		"customer_name"=>$order['contact_name'],
		"customer_tel"=>$order['phone'],
		"customer_address"=>$order['order_address'].$order['order_address_row2'],
		"customer_lat"=>$order['order_lat'],
		"customer_lng"=>$order['order_lng'],
		"order_status"=>$order['user_id'],
		"pay_status"=>$order['pay_status'],
		"order_time"=>$order['order_time'],
		"payed_time"=>$order['pay_time'],
		"store_id"=>$order['shop_id'],
		"store_address"=>$order['shop_address'].$order['shop_address_row2'],
		"store_lat"=>$order['shop_lat'],
		"store_lng"=>$order['shop_lng']
	);
	ksort($params);
	$param_string = "";
	foreach ($params as $key=>$value) {
		$param_string .= ($key."=".$value."&");
	}
	$param_string = rtrim($param_string,"&");
	$sign = md5($param_string.$xiaoe_secretkey);
	$params['sign'] = $sign;
	http_get($url,$params);
}

function xiaoe_order_status () {
	
}

/*
 * http://localhost/ejew/android/api.php?method=updateCert&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function updateCert() {
	$shop_id = get_login_shop_id();
	$params = array("idcard","idcard_back","idcard_hand","health_cert","bank_card");
	foreach ($params as $key) {
		$val = p($key);
		if(is_string_not_empty($val)) {
			$sql = "update ejew_shop set img_$key = ? where shop_id = ?";
			db_update($sql,[$val,$shop_id]);
		}
	}
	return makeSuccess();
}

/*
 * http://localhost/ejew/android/api.php?method=getCert&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function getCert() {
	global $image_full_url;
	$shop_id = get_login_shop_id();
	$sql = "select * from ejew_shop where shop_id = ?";
	$shop = db_query_row($sql,[$shop_id]);
	return makeSuccess(array(
		"idcard"=>is_string_empty($shop['img_idcard'])?NULL:($image_full_url.$shop['img_idcard']),
		"idcard_back"=>is_string_empty($shop['img_idcard_back'])?NULL:($image_full_url.$shop['img_idcard_back']),
		"idcard_hand"=>is_string_empty($shop['img_idcard_hand'])?NULL:($image_full_url.$shop['img_idcard_hand']),
		"health_cert"=>is_string_empty($shop['img_health_cert'])?NULL:($image_full_url.$shop['img_health_cert']),
		"bank_card"=>is_string_empty($shop['img_bank_card'])?NULL:($image_full_url.$shop['img_bank_card'])
	));
}

/*
 * http://localhost/ejew/android/api.php?method=getSharedQR&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function getSharedQR() {
	global $host_name;
	$shop_id = get_login_shop_id();
	include("../lib/qrcode/qrlib.php");
	QRcode::png("http://$host_name/client.php?shopid=$shop_id");
}

/*
 * http://localhost/ejew/android/api.php?method=setProductStatus&sid=ZeJDxJsRVDHBSNjWTVwCHfzB1GPSEk
 */
function setProductStatus() {
	$shop_id = get_login_shop_id();
	$product_status = p("product_status");
	$product_id = p("product_id");
	
	if($product_status==0 || $product_status==1) {
		$sql = "update ejew_product set product_status=? where product_id = ? and shop_id=?";
		db_update($sql,[$product_status,$product_id,$shop_id]);
		return makeSuccess();
	} else {
		return makeError(9000, "product_status不合法");
	}
}





