<?php
require "inc/inc.php";
require "inc/api_common.php";

$req_id = gen_request_id();

/**
 * success SUCCESS FAIL
 * msg
 */
function output_result0($success,$msg="OK") {
	global $req_id;
	$content = '<xml>';
	$content .= '<return_code><![CDATA['.($success?"SUCCESS":"FAIL").']]></return_code>';
	$content .= '<return_msg><![CDATA['.$msg.']]></return_msg>';
	$content .= '</xml>';
	
	app_log("---[NOTIFY_RESPONSE][".$req_id."]".$content);
	
	echo $content;
}


$notify_xml = file_get_contents("php://input");
app_log('---[NOTIFY_REQUEST]['.$req_id.']'.$notify_xml);

$data = xml2array($notify_xml);

app_log(var_export($data,true));

if($data['return_code']=='SUCCESS') {
	$order_id = $data['out_trade_no'];
	$order_id = ltrim($order_id,"REAL");
	$current_order_status = db_query_value("select order_status from ejew_order_b where order_id=?",[$order_id]);
	$shop_id = db_query_value("select shop_id from ejew_order where order_id=?",[$order_id]);
	
	if($shop_id==55) {
		$order_user = db_query_row("select * from ejew_order eo, ejew_user eu where eo.order_id=? and eo.user_id=eu.user_id", [$order_id]);
		send_sms($order_user['user_phone'], "恭喜你获得小e时代嘉年华活动入场资格，当天凭该短信入场，10月24日中午11点望京商业中心，我们不见不散哦！");
	}
	
	if($current_order_status==0 || $current_order_status==11) {
		app_log("====in current order status========");
		db_autocommit(false);
		$item = array(
			"order_id"=>$order_id,
			"order_status"=>1,
			"pay_channel"=>"weixin",
			"pay_time"=>get_now(),
			"pay_status"=>1
		);
		db_update_item("ejew_order_b", "order_id", $item);
		
		$item = array(
				"order_id"=>$order_id,
				"order_status"=>2,
				"order_status_title"=>"已支付",
				"create_time"=>get_now()
		);
		db_save("ejew_order_status",$item);
		
		db_commit();
		app_log("====call push_shop_message========");
		push_shop_message($shop_id, 1, ("您收到一笔订单，订单号:".$order_id), $order_id);
		
		$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
		send_sms($shop['shop_phone'], "您有一个新订单，请打开家厨端查看！");
		
		db_commit();
	}
	output_result0(true);
}







