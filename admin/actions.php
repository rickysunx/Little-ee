<?php
require '../inc/inc.php';
require 'funcs.php';

function action_error_handler_actions($errcode , $errmsg,$errfile, $errline) {
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

function action_exception_handler_actions($exception) {
	handle_error($exception->getCode(), $exception->getMessage());
	
	error_log("error:".$exception->getFile()."[".$exception->getLine()."] ".$exception->getMessage());
	app_log("error:".$exception->getFile()."[".$exception->getLine()."] ".$exception->getMessage());
	
	app_log($exception->getTraceAsString());
	
	exit();
}

set_error_handler("action_error_handler_actions");
set_exception_handler("action_exception_handler_actions");

$action = p("action");

check_login_api();

app_log("---------Admin_action[".$action."]--------------");
if($action=='order_complain_save') {
	order_complain_save();
} else if($action=='order_complain_delete') {
	order_complain_delete();
} else if($action=='shop_approve') {
	shop_approve();
} else if($action=='set_shop_operation_status') {
	set_shop_operation_status();
} else if($action=='product_approve') {
	product_approve();
} else if($action=='comment_approve') {
	comment_approve();
} else if($action=='reply_approve') {
	reply_approve();
} else if($action=='change_pass') {
	change_pass();
} else {
	$result = call_user_func($action);
	output_result($result);
}

function output_result($result) {
	$json =  json_encode($result,JSON_UNESCAPED_UNICODE);
	echo $json;
}

function handle_success() {
	$result = array("success"=>true);
	output_result($result);
}

function handle_error($errcode,$errmsg) {
	$result = array(
			"success"=>false,
			"errcode"=>$errcode,
			"errmsg"=>$errmsg
	);
	output_result($result);
}

function check_stock0($shop_id,$date/*,$period*/) {
	/*
	 $sql =
	 "insert into ejew_product_stock (product_id,stock_date,stock_count,stock_period)
		select product_id,'".$date."',product_count,".$period." from ejew_product
		where product_id not in
		(select product_id from ejew_product_stock
		where stock_date = '".$date."' and stock_period = ".$period.")
		and shop_id = ?";
		db_update($sql,[$shop_id]);
		*/

	$sql =
	"insert into ejew_product_stock (product_id,stock_date,stock_count)
		select product_id,'".$date."',product_count from ejew_product
		where product_id not in
			(select product_id from ejew_product_stock
			where stock_date = '".$date."')
		and shop_id = ?";
	db_update($sql,[$shop_id]);
}

function check_stock($shop_id) {
	//check_stock0($shop_id, get_today_date(), 0);
	//check_stock0($shop_id, get_today_date(), 1);
	//check_stock0($shop_id, get_tomorrow_date(), 0);
	//check_stock0($shop_id, get_tomorrow_date(), 1);
	check_stock0($shop_id, get_today_date());
	check_stock0($shop_id, get_tomorrow_date());
}

function update_stock($product_id,$count,$time_type,$add=true) {
	$date = ($time_type==0||$time_type==1)?get_today_date():get_tomorrow_date();
	$order_type = ($time_type==0||$time_type==2)?0:1;

	$sql = "update ejew_product_stock set stock_count = " .($add?("stock_count ".$count):$count).
	" where stock_date = ? and product_id = ?";// and stock_period = ?";

	db_update($sql,[$date,$product_id/*,$order_type*/]);

}

function order_complain_save() {
	$order_id = p("order_id");
	$complain_tag = trim(p("complain_tag"));
	$complain_detail = p("complain_detail");
	
	if(is_string_empty($complain_tag)) {
		throw new Exception("投诉标签不能为空",7001);
	}
	
	if(is_string_empty($complain_detail)) {
		throw new Exception("投诉内容不能为空",7001);
	}
	
	$complain = array(
		"order_id"=>$order_id,
		"complain_tag"=>$complain_tag,
		"complain_detail"=>$complain_detail,
		"admin_id"=>get_login_admin()['admin_id'],
		"complain_time"=>get_now()
	);
	
	$affected_rows = db_save("ejew_order_complain", $complain);
	if($affected_rows) {
		handle_success();
		return;
	}
	
	handle_error(9999, "");
}

function order_complain_delete() {
	$complain_id = p("complain_id");
	if(is_string_empty($complain_id)) {
		handle_error(2001, "id不能为空");
		return;
	}
	$affected_rows = db_update("update ejew_order_complain set deleted=1 where complain_id=?",array($complain_id));
	handle_success();
}

function shop_approve() {
	$shop_id = p("shop_id");
	$approval_status = p("approval_status");
	$justification = p("justification");
	db_update("update ejew_shop set approval_status=? where shop_id=?",array($approval_status,$shop_id));
	$approval_log = array(
		"approval_type"=>1,
		"obj_id"=>$shop_id,
		"admin_id"=>get_login_admin_id(),
		"approval_time"=>get_now(),
		"approval_status"=>$approval_status,
		"justification"=>$justification
	);
	db_save("ejew_approval_log",$approval_log);
	handle_success();
}

function product_approve() {
	$product_id = p("product_id");
	$approval_status = p("approval_status");
	$justification = p("justification");
	db_update("update ejew_product set approval_status=? where product_id=?",array($approval_status,$product_id));
	$approval_log = array(
			"approval_type"=>2,
			"obj_id"=>$product_id,
			"admin_id"=>get_login_admin_id(),
			"approval_time"=>get_now(),
			"approval_status"=>$approval_status,
			"justification"=>$justification
	);
	db_save("ejew_approval_log",$approval_log);
	handle_success();
}

function set_shop_operation_status() {
	$shop_id = p("shop_id");
	$operation_status = p("operation_status");
	$justification = "商户营业状态变化";
	
	db_update("update ejew_shop set operation_status=? where shop_id=?",array($operation_status,$shop_id));
	$approval_log = array(
			"approval_type"=>11,
			"obj_id"=>$shop_id,
			"admin_id"=>get_login_admin_id(),
			"approval_time"=>get_now(),
			"approval_status"=>$operation_status,
			"justification"=>$justification
	);
	db_save("ejew_approval_log",$approval_log);

	handle_success();
}

function comment_approve() {
	$comment_id = p("comment_id");
	$approval_status = p("approval_status");
	db_update("update ejew_comment set comment_approval=? where comment_id=?",array($approval_status,$comment_id));
	handle_success();
}

function reply_approve() {
	$comment_id = p("comment_id");
	$approval_status = p("approval_status");
	db_update("update ejew_comment set reply_approval=? where comment_id=?",array($approval_status,$comment_id));
	handle_success();
}

function change_pass() {
	$old_password = p("old_password");
	$new_password = p("new_password");
	$new_password_2 = p("new_password_2");
	
	if($new_password!=$new_password_2) {
		handle_error(8000, "两次输入密码不一致");
		return;
	}
	
	$admin_pass = db_query_value("select admin_pass from ejew_admin where admin_id=".get_login_admin_id());
	if(md5($old_password)!=$admin_pass) {
		handle_error(8000, "旧密码不正确");
		return;
	}
	
	db_update("update ejew_admin set admin_pass=? where admin_id=?",array(md5($new_password),get_login_admin_id()));
	handle_success();
	
}

function save_shop() {
	$shop = array();
	$shop_id = p("shop_id");
	if(!is_string_empty($shop_id)) {
		$shop['shop_id'] = $shop_id;
	}
	$shop['city'] = check_not_null('city');
	$shop['shop_phone'] = check_not_null('shop_phone');
	$shop['shop_name'] = check_not_null('shop_name');
	$shop_images = p("shop_images");
	if(!is_array($shop_images)) {
		return makeError(9000, "厨房图片不能为空");
	}
	$shop['contact_phone'] = check_not_null('contact_phone');
	$shop['shop_images'] = json_encode(p('shop_images'));
	$shop['shop_address'] = check_not_null('shop_address');
	$shop['shop_address_row2'] = check_not_null('shop_address_row2');
	$shop['shop_lng'] = check_not_null('shop_lng');
	$shop['shop_lat'] = check_not_null('shop_lat');
	$shop['delivery_distance'] = check_not_null('delivery_distance');
	$shop['cooking_style'] = check_not_null('cooking_style');
	$shop['lunch_time_start'] = check_not_null('lunch_time_start');
	$shop['lunch_time_end'] = check_not_null('lunch_time_end');
	$shop['dinner_time_start'] = check_not_null('dinner_time_start');
	$shop['dinner_time_end'] = check_not_null('dinner_time_end');
	$shop['lunch_stop_time'] = check_not_null('lunch_stop_time');
	$shop['dinner_stop_time'] = check_not_null('dinner_stop_time');
	$shop['can_takeaway'] = check_not_null('can_takeaway');
	$shop['can_delivery'] = check_not_null('can_delivery');
	$shop['keeper_avatar'] = check_not_null('keeper_avatar');
	$shop['keeper_name'] = check_not_null("keeper_name");
	$shop['keeper_hometown'] = check_not_null("keeper_hometown");
	$shop['keeper_id_number'] = check_not_null("keeper_id_number");
	$shop['keeper_intro'] = check_not_null("keeper_intro");
	$shop['bank_account_name'] = check_not_null("bank_account_name");
	$shop['bank_card_number'] = check_not_null("bank_card_number");
	$shop['cover_product_image'] = check_not_null("cover_product_image");
	$shop['has_lunch'] = check_not_null('has_lunch');
	$shop['has_dinner'] = check_not_null('has_dinner');
	
	if(!$shop_id) {
		$shop['reg_time'] = get_now();
	}
	
	if(!is_mobile_phone($shop['shop_phone'])) {
		return makeError(9000, "注册手机格式不对");
	}
	
	$count_shop_name = 0;
	if($shop_id) {
		$count_shop_name = db_query_value("select count(1) from ejew_shop where shop_name=? and shop_id<>?",
			[$shop['shop_name'],$shop_id]);
	} else {
		$count_shop_name = db_query_value("select count(1) from ejew_shop where shop_name=?",[$shop['shop_name']]);
	}
	if($count_shop_name>0) {
		return makeError(9000, "厨房名称不能重复");
	}
	
	if(!is_numeric($shop['delivery_distance'])) {
		return makeError(9000, "送餐距离必须为数字");
	}
	
	if(!is_valid_id_number($shop['keeper_id_number'])) {
		return makeError(9000, "身份证号码需是15位或18位数字");
	}
	
	if($shop['lunch_time_start']>$shop['lunch_time_end']) {
		return makeError(9000, "午餐结束时间需大于开始时间");
	}
	
	if($shop['dinner_time_start']>$shop['dinner_time_end']) {
		return makeError(9000, "晚餐结束时间需大于开始时间");
	}
	
	if($shop_id) {
		db_update_item("ejew_shop", "shop_id", $shop);
	} else {
		$shop_id = db_save("ejew_shop", $shop);
	}
	
	check_shop_info_full($shop_id);
	
	return makeSuccess();
}

function get_product() {
	global $image_full_url;
	$product_id = p("product_id");
	if(is_string_empty($product_id)) {
		return makeError(9000, "id不能为空");
	}
	
	$product = db_query_row("select * from ejew_product where product_id=?",[$product_id]);
	if(!$product) {
		return makeError(9000, "菜品不存在");
	}
	
	$shop_id = $product['shop_id'];
	check_stock($shop_id);
	
	$product['product_image_url'] = $image_full_url.$product['product_image'];
	
	$today_stock = db_query_value("select stock_count from ejew_product_stock where product_id = ? and stock_date=?",
			[$product_id,get_today_date()]);
	
	$tomorrow_stock = db_query_value("select stock_count from ejew_product_stock where product_id = ? and stock_date=?",
			[$product_id,get_tomorrow_date()]);
	
	$product['today_stock'] = $today_stock;
	$product['tomorrow_stock'] = $tomorrow_stock;
	
	return makeSuccess($product);
}


function update_product() {
	global $image_path;
	$shop_id = p("shop_id");
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
		"update_time"=>get_now()
	);
	
	$product_id = $product['product_id'];
	
	if($product['product_price']>1000) {
		return makeError(9000, "菜品价格应小于1000元");
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
	
	$today_stock = check_not_null("today_stock");
	$tomorrow_stock = check_not_null("tomorrow_stock");
		
	db_update_item("ejew_product","product_id", $product);
	
	update_stock($product_id, $today_stock, 0,false);
	update_stock($product_id, $tomorrow_stock, 2,false);
	
	/*
	if(p("is_sign")) {
		$shop = array(
				"shop_id"=>$shop_id,
				"cover_product_image"=>$product["product_image"]
		);
		db_update_item("ejew_shop", "shop_id", $shop);
	}
	
	check_shop_info_full($shop_id);
	*/
	return makeSuccess();
}

function add_product() {
	global $image_path;
	$shop_id = p("shop_id");
	$product = array(
		"shop_id"=>$shop_id,
		"product_name"=>check_not_null("product_name"),
		"product_image"=>check_not_null("product_image"),
		"product_price"=>check_not_null("product_price"),
		"product_count"=>check_not_null("product_count"),
		"is_main"=>check_not_null("is_main"),
		"is_sign"=>check_not_null("is_sign"),
		"is_half"=>check_not_null("is_half"),
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

	$product_id = db_save("ejew_product", $product);
	
	
	check_stock($shop_id);
	
	update_stock($product_id, $today_stock, 0,false);
	update_stock($product_id, $tomorrow_stock, 2,false);

	/*
	if(p("is_sign")) {
		$shop = array(
				"shop_id"=>$shop_id,
				"cover_product_image"=>$product["product_image"]
		);
		db_update_item("ejew_shop", "shop_id", $shop);
	}

	check_shop_info_full($shop_id);
	*/
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

function delete_product() {
	$product_id = p("product_id");
	db_update("delete from ejew_product where product_id=?",[$product_id]);
	return makeSuccess();
}

function delete_shop() {
	$shop_id = p("shop_id");
	db_update("delete from ejew_shop where shop_id=?",[$shop_id]);
	return makeSuccess();
}

function set_order_status() {
	global $listOfValue,$time_map,$time_duration_array;
	$order_id = p("order_id");
	$order_status = p("order_status");
	if(!isset($listOfValue['order_status'][$order_status])) {
		return makeError(9000, "非合法订单状态");
	}
	
	$order_id = check_not_null("order_id");
	$order_status = check_not_null("order_status");
	
	$order = db_query_row(
			"select * from v_order o,ejew_user u where o.user_id=u.user_id and order_id=?",
			[$order_id]);
	
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
	$shop_id = $order['shop_id'];
	
	$dinner_str = (($order['order_type']==0)?"午餐":"晚餐");
	
	$sms = null;
	$coupon_sms = null;
	
	db_autocommit(false);
	
	$order_item = array(
		"order_id"=>$order_id,
		"order_status"=>$order_status
	);
	
	db_update_item("ejew_order_b", "order_id", $order_item);
	
	if($order_status==7 || $order_status==11) {
		$sql = "update ejew_coupon set coupon_used = 0 where coupon_id in (select coupon_id from v_order where coupon_id<>0 and order_id=?) ";
		db_update($sql,[$order_id]);
	}
	
	$order_status_item = array (
		"order_id"=>$order_id,
		"order_status"=>$order_status,
		"order_status_title"=>$listOfValue['order_status'][$order_status],
		"order_status_content"=>"客服修改状态为：".$listOfValue['order_status'][$order_status],
		"create_time"=>get_now()
	);
	
	db_save("ejew_order_status", $order_status_item);
	
	if(check_values($order_status, array(20,21,22))) {
		$order_item = array(
			"order_id"=>$order_id,
			"is_little_e"=>1
		);
		db_update_item("ejew_order", "order_id", $order_item);
	}
	
	if($order_status==4) {
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

		if($order['is_first_order']) {
			$coupon_name = "首单返券";
			$condition_desc = "让你再吃美味一餐";
			$coupon_amount_5 = 5;
			$coupon_amount_10 = 10;
			$validation_start = date('Y-m-d H:i:s');
			$validation_end = date('Y-m-d H:i:s', strtotime('+7day'));
			$coupon_sms = "吃完了美味健康的一餐，再送你15元的粮票（一个10元、一个5元），有效期7天，抓紧时间再下单吧!";
			
			$sql = "insert into ejew_coupon(user_id,coupon_name,condition_desc,coupon_amount,validation_start,validation_end,condition_json,coupon_used) select user_id,?,?,?,?,?,'{}',0 from ejew_user
			where user_id = ".$order['user_id'];						
			$updated_count = db_update($sql,[$coupon_name,$condition_desc,$coupon_amount_5,$validation_start,$validation_end]);
			$updated_count = db_update($sql,[$coupon_name,$condition_desc,$coupon_amount_10,$validation_start,$validation_end]);
				
		}
		
	} else if($order_status==20) {
		$sms = "您预定的".$order_time_msg."的".$dinner_str."，家厨已经确认接单啦，请等待厨师为你制作美食哦，将由小E为您配送";
	} else if($order_status==2) {
		$sms = "您预定的".$order_time_msg."的".$dinner_str."，家厨已经确认接单啦，请等待厨师为你制作美食哦";
		$order_item = array(
			"order_id"=>$order_id,
			"is_little_e"=>0
		);
		db_update_item("ejew_order", "order_id", $order_item);
	} else if($order_status==3) {
		$sms = "您预定的".$order_time_msg."的".$dinner_str."，已经开始配送啦，请耐心等待美食的到来哦";
	} else if($order_status==21) {
		$sms = "您预定的".$order_time_msg."的".$dinner_str."，小e已经接单啦";
	} else if($order_status==22) {
		$sms = "您预定的".$order_time_msg."的".$dinner_str."，小e已经取餐";
	}  else if($order_status==7) {
		$sms = "抱歉，您预定的".$order_time_msg."的".$dinner_str
			  ."，由于家厨无法接单，我们会给您做退款处理，"
			  ."订单费用会很快返回到你的原账户中，请换个家厨重新下单吧";
	}
	
	db_commit();
	
	db_autocommit(true);
	
	if(isset($bill)) push_shop_message($shop_id, 3, "您收到一笔订单费，".$bill['bill_detail'], $bill_id);
	if($sms) send_sms($user_phone, $sms);
	if($coupon_sms) send_sms($user_phone, $coupon_sms);
	
	return makeSuccess();
}

function get_order_status() {
	global $listOfValue;
	$order_id = p("order_id");
	$order = db_query_row("select * from v_order where order_id=?",[$order_id]);
	if(!$order) {
		return makeError(9000, "订单不存在");
	}
	
	$order_status = $order['order_status'];
	$order_status_detail = $listOfValue['order_status_admin'][$order_status];
	
	return makeSuccess(array(
		"order_status"=>$order_status,
		"order_status_detail"=>$order_status_detail
	));
}

function get_coupon_used() {
	global $listOfValue;
	$order_id = p("coupon_id");
	$order = db_query_row("select * from v_order where order_id=?",[$order_id]);
	if(!$order) {
		return makeError(9000, "订单不存在");
	}

	$order_status = $order['order_status'];
	$order_status_detail = $listOfValue['order_status_admin'][$order_status];

	return makeSuccess(array(
			"order_status"=>$order_status,
			"order_status_detail"=>$order_status_detail
	));
}

function set_shop_status() {
	$shop_id = check_not_null("shop_id");
	$leave_dates = check_not_null("leave_dates");
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
		db_save("ejew_shop_status", $item);
	}
	return makeSuccess();
}

function create_coupon() {
	$coupon_user = check_not_null("coupon_user");
	$coupon_name = check_not_null("coupon_name");
	$condition_desc = check_not_null("condition_desc");
	$coupon_amount = check_not_null("coupon_amount");
	$coupon_number = check_not_null("coupon_number");
	$validation_start = check_not_null("validation_start");
	$validation_end = check_not_null("validation_end");
	
	if(!is_numeric($coupon_amount)) {
		return makeError(9000, "金额必须为数字");
	}

	if(!is_numeric($coupon_amount) || $coupon_number >= 10 || $coupon_number < 1) {
		return makeError(9000, "数量必须大于1张，小于10张");
	}

	if($coupon_number > 1) {
/* 		if($coupon_user=="*") {
			return makeError(9000, "数量必须大于1张时，只能发给指定用户！");
		}		
 */	}
	
	$coupon_user_list = array();
	if($coupon_user!="*") {
		$coupon_user_list = explode(",",$coupon_user);
		$user_count = count($coupon_user_list);
		for($i=0;$i<$user_count;$i++) {
			$coupon_user_list[$i] = trim($coupon_user_list[$i]);
			if(!is_mobile_phone($coupon_user_list[$i])) {
				return makeError(9000, $coupon_user_list[$i]."号码格式不对");
			}
		}
		
		$coupon_user_sql = "'".implode("','", $coupon_user_list)."'";
		$sql = "insert into ejew_coupon(user_id,coupon_name,condition_desc,coupon_amount,validation_start,validation_end,condition_json,coupon_used) select user_id,?,?,?,?,?,'{}',0 from ejew_user 
				where user_phone in (".$coupon_user_sql.")";
		
		for($i=0;$i<$coupon_number;$i++) {
			$updated_count = db_update($sql,[$coupon_name,$condition_desc,$coupon_amount,$validation_start,$validation_end]);
		}		

		//$sms = "送您10元粮票！“10月27”——实在“爱吃”！首届“爱吃节”开始啦~每逢“27”必“爱吃”~只要你“爱吃”，小e不介意奉陪到底，10元粮票双手奉上~11月3日前，无使用门槛，只要你“爱吃”，小e管你饭！马上就点吧！";
		//$sms = "双11让你脱单！送你2张11元吃饭券，可请“她”或“他”一起享受家厨美味！有效期至2015.11.11晚24:00整，还在等什么？马上抢餐吧！";
		$sms = "只为带给你更多美味健康的饭菜，送你".$coupon_amount."元粮票，打开微信中的小e管饭可用";
		
		send_sms($coupon_user_list[0], $sms);
	} else {
		$coupon_user_list = db_query("select user_phone from ejew_user;");
		//$sms = "送您10元粮票！“10月27”——实在“爱吃”！首届“爱吃节”开始啦~每逢“27”必“爱吃”~只要你“爱吃”，小e不介意奉陪到底，10元粮票双手奉上~11月3日前，无使用门槛，只要你“爱吃”，小e管你饭！马上就点吧！";
		//$sms = "双11让你脱单！送你2张11元吃饭券，可请“她”或“他”一起享受家厨美味！有效期至2015.11.11晚24:00整，还在等什么？马上抢餐吧！";
		$sms = "只为带给你更多美味健康的饭菜，送你".$coupon_amount."元粮票，打开微信中的小e管饭可用";
		
		foreach($coupon_user_list as $coupon_user) {
			send_sms($coupon_user['user_phone'], $sms);
		}

		$sql = "insert into ejew_coupon(user_id,coupon_name,condition_desc,coupon_amount,validation_start,validation_end,condition_json,coupon_used) select user_id,?,?,?,?,?,'{}',0 from ejew_user";
	
		$updated_count = db_update($sql,[$coupon_name,$condition_desc,$coupon_amount,$validation_start,$validation_end]);
	}	
	
	return makeSuccess(array("updated_count"=>$updated_count));	
}

function create_material() {
	$material_shop = check_not_null("material_shop");
	$material_name = check_not_null("material_name");
	$material_number = check_not_null("material_number");

	if(!is_numeric($material_number)) {
		return makeError(9000, "金额必须为数字");
	}

/* 发放给所有商户
 *  	$sql = "INSERT INTO ejew_material (shop_id , material_name, material_count, apply_time) select shop_id ,?, ? , now() from  ejew_shop ";
 */	
	$sql = "INSERT INTO ejew_material (shop_id , material_name, material_count, apply_time) select shop_id ,?, ? , now() from  ejew_shop where shop_phone = ".$material_shop;
	$updated_count = db_update($sql,[$material_name,$material_number]);

	return makeSuccess(array("updated_count"=>$material_number));
}
