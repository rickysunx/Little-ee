<?php

function output_result($result) {
	if($result) {
		$json =  json_encode($result,JSON_UNESCAPED_UNICODE);
		echo $json;
	}
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



function add_order_status($order_id,$status,$title,$content) {
	$order_status = array(
			"order_id"=>$order_id,
			"order_status"=>$status,
			"order_status_title"=>$title,
			"order_status_content"=>$content,
			"create_time"=>get_now()
	);

	db_save("ejew_order_status", $order_status);
}

function set_order_status($order_id,$status) {
	$order = array(
			"order_id"=>$order_id,
			"order_status"=>$status
	);

	db_update_item("ejew_order_b", "order_id", $order);
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

function close_unpaid_order() {
	global $pay_timeout;
	$now = new DateTime();
	$now->setTimestamp($now->getTimestamp()-$pay_timeout);
	$timeout_time = $now->format("Y-m-d H:i:s");
	db_autocommit(false);

	$sql = "select * from ejew_order_product p,ejew_order o where p.order_id = o.order_id and p.order_id in
			(select order_id from v_order where order_status=0 and order_time<?) ";
	$unpaid_products = db_query($sql,[$timeout_time]);

	foreach ($unpaid_products as $unpaid_product) {
		$stock_date = new DateTime($unpaid_product['predicted_time']);
		$stock_date_string = $stock_date->format("Y-m-d");
		$sql = "update ejew_product_stock
		set stock_count=stock_count+{$unpaid_product['product_count']}
		where product_id={$unpaid_product['product_id']} and stock_date = ?";
		db_update($sql,[$stock_date_string]);
	}
	
	$sql = "update ejew_coupon set coupon_used = 0 where coupon_id in (select coupon_id from v_order where order_status=0 and coupon_id<>0 and order_time<?) ";
	
	db_update($sql,[$timeout_time]);

	$sql = "update ejew_order_b set order_status=11 where order_status=0 and order_id in
			(select order_id from ejew_order where order_time<?)";
	db_update($sql,[$timeout_time]);

	db_commit();
	db_autocommit(true);

}

function update_stock($product_id,$count,$time_type,$add=true) {
	$date = ($time_type==0||$time_type==1)?get_today_date():get_tomorrow_date();
	$order_type = ($time_type==0||$time_type==2)?0:1;

	$sql = "update ejew_product_stock set stock_count = " .($add?("stock_count ".$count):$count).
	" where date_format(stock_date,'%Y-%m-%d') = ? and product_id = ?";// and stock_period = ?";

	db_update($sql,[$date,$product_id/*,$order_type*/]);

}



function check_page_params() {
	$offset = p("offset");
	$count = p("count");

	if( (!is_number_string($offset)) || (!is_number_string($count)) || is_string_empty($offset) || is_string_empty($count)) {
		output_result(makeError(9000, "offset和count不能为空，且必须是数字"));
		exit();
	}
}