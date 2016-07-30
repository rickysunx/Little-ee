<?php
require "../inc/inc.php";
require "../inc/api_common.php";

$method = p("method");

$method_list = array(
"get_index_list",
"get_shop_detail",
"save_address",
"get_address_chooser_list",
"get_address",
"get_address_html",
"login",
"check_login",
"get_vcode",
"prepare_order",
"create_order",
"pay_order",
"get_order_list",
"get_order_status",
"get_comment_list",
"wx_login",
"save_order_comment",
"get_coupon_list",
"get_coupon_select_list",
"get_shop_info",
"cancel_order",
"save_feedback",
"reset_coupon",
"get_special_user_id",
"prepare_order_activity_1024",		
"create_order_activity_1024"
);

$reqid = gen_request_id();
app_log("===============Client_Service[".$method."][".$reqid."]================");
$api_time_start = get_milliseconds();
app_log("[".$reqid."][GET]".json_encode($_GET,JSON_UNESCAPED_UNICODE));
app_log("[".$reqid."][POST]".json_encode($_POST,JSON_UNESCAPED_UNICODE));

if(check_values($method,$method_list)) {
	$result = call_user_func($method);
} else {
	return makeError(8000, "方法不支持:".$method);
}


app_log("[".$reqid."][RET]".json_encode($result,JSON_UNESCAPED_UNICODE));
output_result($result);

$api_time_end = get_milliseconds();
app_log("===============End of Client_Service[".$method."][".$reqid."][".($api_time_end-$api_time_start)." ms]================");

function get_index_list() {
	$lng = p("lng");
	$lat = p("lat");
	$time_type = p("time_type");
	$offset = p("offset");
	$count = p("count");
	check_page_params();
	$time = substr(get_now(),11,5);
	$today_date = get_today_date();
	$tomorrow_date = get_tomorrow_date();
	
	$hasPos = is_string_not_empty($lng) && is_string_not_empty($lat);
	
	if($hasPos) {
		
		if($time_type==0 || $time_type==1) {
			$sql_time_greater = ($time_type==0)?("'".$time."'>lunch_stop_time"):("'".$time."'>dinner_stop_time");
			$sql_time_less = ($time_type==0)?("'".$time."'<=lunch_stop_time"):("'".$time."'<=dinner_stop_time");
			$sql_has = ($time_type==0)?" s.has_lunch=1 ":" s.has_dinner=1 ";
			$sql_has .= " and s.operation_status = 1 and s.shop_id not in 
					(select shop_id from ejew_shop_status where leave_date = '".$today_date."')";
			
			$sql = 
				"select * from (select *,f_distance(shop_lng,shop_lat,?,?) distance,
				(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count
				from ejew_shop s where s.approval_status in (1) and ".
				$sql_time_less." and f_distance(shop_lng,shop_lat,?,?)<=delivery_distance and ".$sql_has.
				"order by rand() ) m1
				union
				select * from (select *,f_distance(shop_lng,shop_lat,?,?) distance,
				(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count
				from ejew_shop s where s.approval_status in (1) and 
				f_distance(shop_lng,shop_lat,?,?)>delivery_distance and ".$sql_has." and ".$sql_time_less." order by rand() ) m2
				union
				select * from (select *,f_distance(shop_lng,shop_lat,?,?) distance,
				(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count
				from ejew_shop s where s.approval_status in (1) and
				f_distance(shop_lng,shop_lat,?,?)>delivery_distance and ".$sql_has." and ".$sql_time_greater."
				order by rand()) m3
				union
				select * from (select *,f_distance(shop_lng,shop_lat,?,?) distance,
				(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count
				from ejew_shop s where s.approval_status in (1) and
				f_distance(shop_lng,shop_lat,?,?)<=delivery_distance and ".$sql_has." and ".$sql_time_greater."
				order by rand()) m4
				limit ?,?";
			
				$shops = db_query($sql,[$lng,$lat,$lng,$lat,$lng,$lat,$lng,$lat,$lng,$lat,$lng,$lat,$lng,$lat,$lng,$lat,$offset,$count]);
		} else {
			$sql_has = ($time_type==2)?" s.has_lunch=1 ":" s.has_dinner=1 ";
			$sql_has .= " and s.operation_status = 1 and s.shop_id not in
					(select shop_id from ejew_shop_status where leave_date = '".$tomorrow_date."')";
			
			$sql = "select *,f_distance(shop_lng,shop_lat,?,?) distance,
				(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count,
				f_distance(shop_lng,shop_lat,?,?)>delivery_distance distance_overflow
				from ejew_shop s where s.approval_status in (1) and ".$sql_has." order by distance_overflow,distance,rand() desc limit ?,?";
			$shops = db_query($sql,[$lng,$lat,$lng,$lat,$offset,$count]);
		}
	} else {
		$sql_has = ($time_type==0||$time_type==2)?" s.has_lunch=1 ":" s.has_dinner=1 ";
		if($time_type==0 || $time_type==1) {
			$sql_has .= " and s.operation_status = 1 and s.shop_id not in
					(select shop_id from ejew_shop_status where leave_date = '".$today_date."')";
		} else {
			$sql_has .= " and s.operation_status = 1 and s.shop_id not in
					(select shop_id from ejew_shop_status where leave_date = '".$tomorrow_date."')";
		}
		$sql = "select *,
				(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count
				from ejew_shop s where s.approval_status in (1) and ".$sql_has." order by rand() limit ?,?";
		$shops = db_query($sql,[$offset,$count]);
	}
	
	ob_start();
	$len = count($shops);
	
	for($i=0;$i<$len;$i++) {
		$shop = $shops[$i];
?>
<div class="Item" onclick="showShop(<?php echo $shop['shop_id']?>);">
	<?php if(isset($shop['distance']) && $shop['distance']>$shop['delivery_distance']) {?>
	<div class="Notice">超出配送范围啦</div>
	<?php } else {
		if($time_type==="0") {
			if($time>$shop['lunch_stop_time']) {
				echo '<div class="Notice">超过预定时间啦</div>';
			}
		} else if($time_type==="1") {
			if($time>$shop['dinner_stop_time']) {
				echo '<div class="Notice">超过预定时间啦</div>';
			}
		}
	}?>
	<div class="Image"><img width="100%" src="<?php echo get_image_url($shop['cover_product_image'])?>"/></div>
	<div class="Info">
		<div class="Avatar"><img src="<?php echo get_image_url($shop['keeper_avatar'])?>"/></div>
		<div class="Intro">
			<div class="ShopName"><?php echo $shop['shop_name']?></div>
			<div class="ShopDesc">擅长<?php echo mb_substr($shop['cooking_style'],0,10,"UTF-8")?></div>
		</div>
		<div class="OrderCount"><?php echo $shop['order_count']?>人吃过</div>
	</div>
</div>
<?php
	}
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
}


//---------------------------------------------------------
function get_shop_detail() {
	$shop_id = p("shop_id");
	
	check_stock($shop_id);
	close_unpaid_order();
	
	$lng = p("lng");
	$lat = p("lat");
	$hasPos = false;
	if(is_string_not_empty($lng) && is_string_not_empty($lat)) {
		$hasPos = true;
	}
	
	$today_date = get_today_date();
	$tomorrow_date = get_tomorrow_date();
	
	if($hasPos) {
		$shop = db_query_row("select *,f_distance(shop_lng,shop_lat,?,?) distance,".
				"(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count, ".
				"(select count(comment_id) from ejew_comment c where c.order_id in ".
				"(select order_id from ejew_order where shop_id = s.shop_id)) comment_count ".
				"from ejew_shop s where shop_id = ?",array($lng,$lat,$shop_id));
	} else {
		$shop = db_query_row("select *,".
				"(select count(order_id) from v_order o where o.shop_id=s.shop_id and o.order_status in (4,9)) order_count, ".
				"(select count(comment_id) from ejew_comment c where c.order_id in ".
				"(select order_id from ejew_order where shop_id = s.shop_id)) comment_count ".
				"from ejew_shop s where shop_id = ?",array($shop_id));
	}
	
	$time_type = p("time_type");
	
	if($time_type==0 || $time_type==1) {
		$stock_date = get_today_date();
	} else {
		$stock_date = get_tomorrow_date();
	}
	
	if($time_type==0 || $time_type==2) {
		$stock_period = 0;
	} else {
		$stock_period = 1;
	}
	
	$products = db_query("select *,".
			"(select count(order_product_id) from ejew_order_product op ".
			"where op.product_id=p.product_id and op.order_id in ".
			"(select order_id from ejew_order_b where order_status in (4,9))) order_count, ".
			"(select stock_count from ejew_product_stock st where stock_date = ? and product_id=p.product_id) stock_count ".
			"from ejew_product p where p.shop_id = ? and p.product_status=1 ".
			"order by p.is_sign desc,p.is_main,p.update_time desc",array($stock_date,$shop_id));
	
	$shop_images = ["images/sample_shop.jpg"];
	if(is_string_not_empty($shop["shop_images"])) {
		$shop_images = json_decode($shop["shop_images"]);
	}
	
	ob_start();
?>
	<script type="text/javascript">
	productItems = new Array();
	<?php foreach($products as $product) {?>
	productItems.push({
		product_id:<?php echo $product['product_id']?>,
		product_name:"<?php echo $product['product_name']?>",
		product_price:<?php echo $product['product_price']?>,
		is_main:<?php echo $product['is_main']?>
	});
	<?php }?>
	</script>
	<div id="ShopCover" class="Cover"><?php 
	for($i=0;$i<count($shop_images);$i++) {
		echo "<img src='".get_image_url($shop_images[$i])."' ".($i>0?"style='display:none;'":"")."/>";	
	}
	?></div>
	<div class="ShopName"><span><?php echo $shop['shop_name']?></span><?php if($shop['can_takeaway']) {?><span class="DeliveryMethod">可自取</span><?php }?></div>
	<div class="ShopDesc">擅长<?php echo mb_substr($shop['cooking_style'],0,10,"UTF-8")?></div>
	<div class="OrderCount"><?php echo $shop['order_count']?>人吃过</div>
	<div class="Avatar"><img src="<?php echo get_image_url($shop['keeper_avatar'])?>"/></div>
	<div class="KeeperName"><?php echo $shop['keeper_name']." ".$shop['keeper_hometown']?>人</div>
	<div class="CommentCount" onclick="showComment();"><?php echo $shop['comment_count']?>条评论</div>
	<div id="KeeperIntroContainer" class="KeeperIntroContainer">
		<div id="KeeperIntro" class="KeeperIntro"><?php echo $shop['keeper_intro']?></div>
		<div id="KeeperIntroMoreContainer" class="KeeperIntroMoreContainer" style="display:none;" onclick="slideKeeperIntroDown();"><img src="images/down_more.png"></div>
	</div>
	<div class="ShopAddress">
		<div class="Address" onclick="showShopMap(<?php echo $shop['shop_id']?>);"><img src="images/location2.png"/> <span><?php echo $shop['shop_address']?></span></div>
		<div class="Tel"><img src="images/tel.png" onclick="window.location.href='tel:<?php echo $shop['shop_phone']?>'"/></div>
	</div>
	
	<div class="ProductTitle"><img src="images/product_title_<?php echo $time_type?>.png"/></div>
	
	<div id="SProductList" class="SProductList">	
	<?php foreach($products as $product) {?>
		<div class="Item">
			<div class="Image"><img src="<?php echo get_image_url($product['product_image'])?>"/></div>
			<div class="Info">
				<div class="Name"><?php echo $product['product_name']?></div>
				<div class="Count"><?php echo $product['order_count']?>人吃过</div>
				<div class="PriceRow">
					<span class="Price">￥<?php echo $product['product_price']?></span>
					<div>
						<?php if($product['stock_count']<=0) {?>
						<span>卖光了</span>
						<?php } else {?>
						<img id="productListMinus_<?php echo $product['product_id']?>" 
							onclick="decCartItem(<?php echo $product['product_id']?>,
							<?php echo ($product['is_half']==1)?"true":"false"?>,
							<?php echo $product['stock_count']?>);" src="images/minus.png" style="display:none;"/>
						<span id="productListCount_<?php echo $product['product_id']?>" style="display:none;">0</span>
						<img src="images/plus.png" 
							onclick="incCartItem(<?php echo $product['product_id']?>,
							<?php echo ($product['is_half']==1)?"true":"false"?>,
							<?php echo $product['stock_count']?>);"/>
						<?php }?>
					</div>
				</div>
			</div>
		</div>
	<?php }?>
	</div>
	
	<div style="height:50px;"></div>
	
	<?php 
	$time = substr(get_now(),11,5);
	
	if($time_type==0 || $time_type==1) {
		$leave_count = db_query_value("select count(1) from ejew_shop_status where shop_id=? and leave_date=?",[$shop_id,$today_date]);	
	} else {
		$leave_count = db_query_value("select count(1) from ejew_shop_status where shop_id=? and leave_date=?",[$shop_id,$tomorrow_date]);
	}
	
	$time_45_later = new DateTime();
	$time_45_later->add(new DateInterval("PT45M"));
	$time_45_later_string = $time_45_later->format("H:i");
	
	$now_hour = substr($time_45_later_string,0,2);
	$now_minute = substr($time_45_later_string,3,2);
	
	if($now_minute>30) {
		$now_hour++;
		$now_minute = 0;
	} else {
		$now_minute = 30;
	}
	
	$now_half = getXX($now_hour).":".getXX($now_minute);
	
	if($time_type==0||$time_type==2) {
		//午餐
		$time_start = $shop['lunch_time_start'];
		$time_end = $shop['lunch_time_end'];
	} else {
		//晚餐
		$time_start = $shop['dinner_time_start'];
		$time_end = $shop['dinner_time_end'];
	}
	
	if($time_type==0 || $time_type==1) {
		if($now_half>$time_start) {
			$time_start = $now_half;
		}
	}
	
	
	if($time_start>=$time_end) {
		echo '<div class="Notice">不能接单啦</div>';
	} else if($shop['operation_status']!=1) {
		echo '<div class="Notice">暂时不营业啦</div>';
	} else if($leave_count>0) {
		echo '<div class="Notice">今天休息啦</div>';
	} else if(isset($shop['distance']) && $shop['distance']>$shop['delivery_distance']) {
		echo '<div class="Notice">超出配送范围啦</div>';
	} else {
		if($time_type==="0" && $time>$shop['lunch_stop_time']) {
			echo '<div class="Notice">超过预定时间啦</div>';
		} else if($time_type==="1" && $time>$shop['dinner_stop_time']) {
			echo '<div class="Notice">超过预定时间啦</div>';
		} else {
		?>
		<div id="Cart" class="Cart">
			<b onclick="showCreateOrder();">选好了</b>
			<span id="CartSum">合计：￥0</span>
		</div>
		<?php }?>
	<?php }?>
	
<?php

	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
}

function save_address() {
	$user_id = get_login_user_id();
	if(!$user_id) {
		return makeError(9000, "尚未登录");
	}
	$phone = trim(p("phone"));
	if(!is_mobile_phone($phone)) {
		return makeError(9000, "手机号码不合法");
	}
	$user_address = check_not_null("user_address","地址栏");
	$user_address_row2 = check_not_null("user_address_row2","门牌号");
	
	if(strlen($user_address)>100) {
		return makeError(9000, "地址需小于100字符");
	}
	
	if(strlen($user_address_row2)>100) {
		return makeError(9000, "门牌号需小于100字符");
	}
	
	$item = array(
		"contact_name"=>check_not_null("contact_name","联系人"),
		"phone"=>$phone,
		"user_address"=>$user_address,
		"user_address_row2"=>$user_address_row2,
		"user_id"=>get_login_user_id(),
		"address_lng"=>check_not_null("lng","地理位置"),
		"address_lat"=>check_not_null("lat","地理位置")
	);
	$address_id = trim(p("address_id"));
	if(is_string_empty($address_id)) {
		db_save("ejew_user_address", $item);
	} else {
		$item["address_id"] = $address_id;
		db_update_item("ejew_user_address", "address_id", $item);
	}
	
	return makeSuccess();
}


function get_address_chooser_list() {
	$frame = p("frame");
	$address_id = p("address_id");
	$address_list = db_query("select * from ejew_user_address where user_id = ?",array(get_login_user_id()));
	
	ob_start();
	
	if(count($address_list)==0) {
?>
	<div class="noDataList"><img src="images/no_address.png"/></div>
	<script type="text/javascript">$("#AddressChooserList").css("backgroundColor","#F0F0F0");</script>
<?php 
		
	} else {
?>
<script type="text/javascript">$("#AddressChooserList").css("backgroundColor","#FFF");</script>
<?php 
	}
	
	foreach($address_list as $addr) {
?>
<div class="Item">
<?php if($frame=='CreateOrder') {?>
	<img id="AddressChooserTick_<?php echo $addr['address_id']?>" class="Tick AddressChooserTick" src="images/<?php 
	echo ($address_id==$addr['address_id'])?"yes":"yes_grey"
	?>.png"/>
<?php }?>
	<div class="Address<?php echo $frame=='CreateOrder'?"":" NoTick"?>" 
	<?php if($frame=='CreateOrder') {?>
		onclick="selectAddress(<?php echo $addr['address_id']?>);"
	<?php } else {?>
		onclick="updateAddress(<?php echo $addr['address_id'].",'".$frame."'"?>);"
	<?php }?>
		>
		<div class="Row1"><?php echo $addr['contact_name']." ".$addr['phone']?></div>
		<div class="Row2"><?php echo $addr['user_address'].$addr['user_address_row2']?></div>
	</div>
	<img class="Editor" src="images/editor.png" onclick="updateAddress(<?php echo $addr['address_id'].",'".$frame."'"?>);"/>
</div>
<?php
	}
	
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
	
}

function get_address() {
	$address_id = p("address_id");
	$address = db_query_row("select * from ejew_user_address where address_id = ? and user_id = ?",
			array($address_id,get_login_user_id()));
	return makeSuccess(array(
			"contact_name"=>$address['contact_name'],
			"phone"=>$address['phone'],
			"user_address"=>$address['user_address'],
			"user_address_row2"=>$address['user_address_row2'],
			"address_id"=>$address['address_id']
	));
}

function get_address_html() {
	$address_id = p("address_id");
	$addr = db_query_row("select * from ejew_user_address where address_id = ? and user_id = ?",
			array($address_id,get_login_user_id()));
	ob_start();
?>
<div class="Text">
	<input id="OrderAddressId" type="hidden" value="<?php echo $addr['address_id'];?>"/>
	<div class="Contact"><?php echo $addr['contact_name']." ".$addr['phone']?></div>
	<div class="Addr"><?php echo $addr['user_address'].$addr['user_address_row2']?></div>
</div>
<div class="Next"><img class="BigNextIcon" src="images/big_next.png"/></div>
<?php 
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));

}

function login() {
	global $vcode_timeout,$session_timeout;
	$phone = p("phone");
	$vcode = p("vcode");
	
	$phone_len = strlen($phone);
	
	if(!($phone_len==11 && is_number_string($phone))) {
		return makeError(9000, "电话号码需11位纯数字");
	}
	
	db_update("update ejew_vcode set vcode_status=2 where create_time < ?",array(time()-$vcode_timeout));
	
	$row = db_query_row("select vcode_number from ejew_vcode where vcode_status=0 and phone=?",array($phone));
	
	if($row) {
		if($vcode!=$row['vcode_number']) {
			return makeError(9000, "验证码不正确，请重新输入");
		}
	} else {
		return makeError(9000, "无效验证码");
	}
	
	db_update("update ejew_vcode set vcode_status=2 where phone=?",array($phone));
	
	$user = db_query_row("select * from ejew_user where user_phone=?",array($phone));
	
	if(!$user) {
		$user = array(
			"user_phone"=>$phone,
			"reg_time"=>get_now(),
			"reg_channel"=>"weixin",
			"user_status"=>1
		);
		db_save("ejew_user", $user);
		$user = db_query_row("select * from ejew_user where user_phone=?",array($phone));
	}
	
	$wx_user = get_login_wx_user();
	if($wx_user) {
		$update_user = array(
			"user_id"=>$user['user_id'],
			"nick_name"=>$wx_user['nickname'],
			"user_avatar"=>$wx_user['headimgurl']
		);
		db_update_item("ejew_user", "user_id", $update_user);
	}
	
	$session_id = gen_session_id();
	$now = time();
	
	db_save("ejew_session",array(
		"session_id"=>$session_id,
		"session_type"=>1,
		"user_id"=>$user["user_id"],
		"create_time"=>$now,
		"update_time"=>$now
	));
	
	setcookie("ejew_sid",$session_id,$now+$session_timeout,"/");
	
	$mask_phone = maskString($user['user_phone'], 3, 4);
	
	return makeSuccess(array(
		"user_phone"=>$mask_phone,
		"user_avatar"=>get_image_url("1001.jpg")
	));
}

function check_login() {
	global $session_timeout;
	$user = get_login_user();
	if($user) {
		$mask_phone = maskString($user['user_phone'], 3, 4);
		$sid = $_COOKIE['ejew_sid'];
		setcookie("ejew_sid",$sid,time()+$session_timeout,"/");
		return makeSuccess(array(
			"user_phone"=>$mask_phone,
			"user_avatar"=>$user['user_avatar'],
			"is_login"=>true
		));
	} else {
		return makeSuccess(array(
			"is_login"=>false
		));
	}
}

function get_vcode() {
	global $vcode_timeout;
	$phone = p("phone");
	
	$phone_len = strlen($phone);
	
	if(!($phone_len==11 && is_number_string($phone))) {
		return makeError(9000, "电话号码需11位纯数字");
	}
	
	$value = db_query_row("select vcode_id from ejew_vcode where phone=? and create_time > ? and vcode_status=0 and vcode_type=1",array($phone,time()-60));
	
	if($value) {
		return makeError(9000, "同一手机1分钟内只能获取一次验证码");
	}
	
	$value = db_query_row("select vcode_id,vcode_number from ejew_vcode where phone=? and create_time > ? and vcode_status=0 and vcode_type=1",array($phone,time()-300));
	
	if($value) {
		$vcode = $value['vcode_number'];
	} else {
		db_update("update ejew_vcode set vcode_status=2 where phone = ? and vcode_type=1",array($phone));
		$vcode = mt_rand(1000,9999);
		db_save("ejew_vcode", array(
				"vcode_number"=>$vcode,
				"phone"=>$phone,
				"vcode_status"=>0,
				"create_time"=>time(),
				"validate_time"=>time()+$vcode_timeout,
				"vcode_type"=>1
		));
	}
	
	send_sms($phone, "您登陆e家e味的验证码是:".$vcode);
	
	return makeSuccess();
}

function get_login_user () {
	$user_id = get_login_user_id();
	if(!$user_id) return NULL;
	$user = db_query_row("select * from ejew_user where user_id=?",array($user_id));
	return $user;
}

function get_login_user_id () {
	if(!isset($_COOKIE['ejew_sid'])) {
		return NULL;
	}
	$sid = $_COOKIE['ejew_sid'];
	$session = db_query_row("select * from ejew_session where session_id=?",array($sid));
	if(!$session) return NULL;
	return $session['user_id'];
}

function get_special_user_id () {
	$no_right_id=1000000;
	if(isset($_COOKIE['ejew_sid'])) {
		$sid = $_COOKIE['ejew_sid'];
		$session = db_query_row("select * from ejew_session where session_id=?",array($sid));
		if($session) $no_right_id = $session['user_id'];
	}	
	return makeSuccess(array(
			"user_id"=>$no_right_id
	));
}

function get_login_wx_openid () {
	if(!isset($_COOKIE['wx_sid'])) {
		return NULL;
	}
	$sid = $_COOKIE['wx_sid'];
	$session = db_query_row("select * from ejew_session where session_id=?",array($sid));
	if(!$session) return NULL;
	return $session['openid'];
}

function get_login_wx_user () {
	$wx_openid = get_login_wx_openid();
	if(!$wx_openid) return NULL;
	$wx_user = db_query_row("select * from ejew_wx_user where openid=?",[$wx_openid]);
	return $wx_user;
}

function prepare_order() {
	global $time_duration_array,$time_map;
	$user = get_login_user();
	$user_id = $user['user_id'];
	$now = get_now();
	if(!$user) {
		return makeError(1010, "尚未登录");
	}
	
	$time_type = p("time_type");
	$shop_id = p("shop_id");
	
	$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
	
	close_unpaid_order();

	$order_count = db_query_value("select count(order_id) from v_order where user_id=? and order_status not in (5,6,7,10,11,12,13,14,25,26) ",[$user['user_id']]);
	$is_first_order = ($order_count==0)?1:0;
	
	$pre_order_count = db_query_value("select count(order_id) from v_order where user_id=? "
			." and order_status not in (5,6,7,10,11,12,13,14,25,26) " 
			." and predicted_time like ? ",[$user_id, "%".get_tomorrow_date()."%"]);
	//$is_first_pre_order = ($pre_order_count==0)?1:0;
	$is_first_pre_order = 0;
	
	$coupon_count = db_query_value("select count(1) from ejew_coupon where coupon_used=0 and user_id=? and validation_end>=?",
			[$user['user_id'],get_today_date()." 00:00:00"]);
	
	$result = array(
		"shop_id"=>$shop['shop_id'],
		"can_takeaway"=>$shop['can_takeaway'],
		"can_delivery"=>$shop['can_delivery']
	);
	
	//检测就餐时间
	$now_time = substr($now,11,5);
	if($time_type==0) {
		if($now_time>$shop['lunch_stop_time']) {
			return makeError(9999, "该店铺已经停止接单午餐");
		}
	} else if($time_type==1) {
		if($now_time>$shop['dinner_stop_time']) {
			return makeError(9999, "该店铺已经停止接单晚餐");
		}
	}
	
	$time_45_later = new DateTime();
	$time_45_later->add(new DateInterval("PT45M"));
	$time_45_later_string = $time_45_later->format("H:i");
	
	$now_hour = substr($time_45_later_string,0,2);
	$now_minute = substr($time_45_later_string,3,2);
	
	if($now_minute>30) {
		$now_hour++;
		$now_minute = 0;
	} else {
		$now_minute = 30;
	}
	
	$now_half = getXX($now_hour).":".getXX($now_minute);
	$time_array = array();
	for($time_type=0;$time_type<4;$time_type++) {
		
		if($time_type==0||$time_type==2) {
			//午餐
			$time_start = $shop['lunch_time_start'];
			$time_end = $shop['lunch_time_end'];
		} else {
			//晚餐
			$time_start = $shop['dinner_time_start'];
			$time_end = $shop['dinner_time_end'];
		}
		
		if($time_type==0 || $time_type==1) {
			if($now_half>$time_start) {
				$time_start = $now_half;
			}
		}
		
		$time_start_index = $time_map[$time_start];
		$time_end_index = $time_map[$time_end];
		
		
		
		for($i=$time_start_index;$i<$time_end_index;$i++) {
			$time_array[] = array(
				"key"=>($time_type<=1)?($time_type.":".$i):($time_type.":".$i),
				"value"=>($time_type<=1)?"今日".$time_duration_array[$i]:"明日".$time_duration_array[$i]
			);
		}
	}
	
	$result["delivery_time"] = $time_array;
	$result["coupon_count"] = $coupon_count;
	$result["is_first_order"] = $is_first_order;
	$result["is_first_pre_order"] = $is_first_pre_order;
	
	$default_address_id = db_query_value("select max(address_id) from ejew_user_address where user_id = ?",[$user_id]);
	$result['default_address_id'] = $default_address_id;
	
	return makeSuccess($result);
}

function prepare_order_activity_1024() {
	global $time_duration_array,$time_map;
	$user = get_login_user();
	$user_id = $user['user_id'];
	$now = get_now();
	if(!$user) {
		return makeError(1010, "尚未登录");
	}

	$time_type = p("time_type");
	$shop_id = p("shop_id");

	$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);

	close_unpaid_order();

	$order_count = db_query_value("select count(order_id) from v_order where user_id=? and order_status not in (5,6,7,10,11,12,13,14,25,26) ",[$user['user_id']]);
	$is_first_order = ($order_count==0)?1:0;

	$pre_order_count = db_query_value("select count(order_id) from v_order where user_id=? "
			." and order_status not in (5,6,7,10,11,12,13,14,25,26) "
			." and predicted_time like ? ",[$user_id, "%".get_tomorrow_date()."%"]);
	//$is_first_pre_order = ($pre_order_count==0)?1:0;
	$is_first_pre_order = 0;

	$coupon_count = db_query_value("select count(1) from ejew_coupon where coupon_used=0 and user_id=? and validation_end>=?",
			[$user['user_id'],get_today_date()." 00:00:00"]);

	$result = array(
			"shop_id"=>$shop['shop_id'],
			"can_takeaway"=>$shop['can_takeaway'],
			"can_delivery"=>$shop['can_delivery']
	);

	//检测就餐时间
	$now_time = substr($now,11,5);

	$time_45_later = new DateTime();
	$time_45_later->add(new DateInterval("PT45M"));
	$time_45_later_string = $time_45_later->format("H:i");

	$now_hour = substr($time_45_later_string,0,2);
	$now_minute = substr($time_45_later_string,3,2);

	if($now_minute>30) {
		$now_hour++;
		$now_minute = 0;
	} else {
		$now_minute = 30;
	}

	$now_half = getXX($now_hour).":".getXX($now_minute);

	if($time_type==0||$time_type==2) {
		//午餐
		$time_start = $shop['lunch_time_start'];
		$time_end = $shop['lunch_time_end'];
	} else {
		//晚餐
		$time_start = $shop['dinner_time_start'];
		$time_end = $shop['dinner_time_end'];
	}

	if($time_type==0 || $time_type==1) {
		if($now_half>$time_start) {
			$time_start = $now_half;
		}
	}

	$time_start_index = $time_map[$time_start];
	$time_end_index = $time_map[$time_end];

	$time_array = array();

	for($i=$time_start_index;$i<$time_end_index;$i++) {
		$time_array[] = array(
				"key"=>$i,
				"value"=>$time_duration_array[$i]
		);
	}

	$result["delivery_time"] = $time_array;
	$result["coupon_count"] = $coupon_count;
	$result["is_first_order"] = $is_first_order;
	$result["is_first_pre_order"] = $is_first_pre_order;

	$default_address_id = db_query_value("select max(address_id) from ejew_user_address where user_id = ?",[$user_id]);
	$result['default_address_id'] = $default_address_id;

	return makeSuccess($result);
}

/**
 * 
 */
function create_order() {
	global $time_array,$first_order_discount,$pre_order_discount;
	
	$address_id = p("address_id");
	$user_id = get_login_user_id();
	$deliverytime_and_timetype = p("delivery_time_index");
	list($time_type,$delivery_time_index) = explode(":",$deliverytime_and_timetype);
	$order_memo = p("order_memo");
	$is_takeaway = p("is_takeaway");
	$shop_id = p("shop_id");
	//$time_type = p("time_type");
	$cart_items_json = p("cart_items");
	$now = get_now();
	$order_type = ($time_type==0||$time_type==2)?0:1;
	$delivery_method = p("delivery_method");
	$total_fee_client = p("total_fee");
	$coupon_id = p("coupon_id");
	$today_date = get_today_date();
	$tomorrow_date = get_tomorrow_date();
	
	$predicted_date = ($time_type==0||$time_type==1)?$today_date:$tomorrow_date;
	$predicted_time = $predicted_date." ".$time_array[$delivery_time_index].":00";
	
	$address = db_query_row("select * from ejew_user_address where address_id=?",[$address_id]);
	
	$products = db_query("select * from ejew_product where shop_id=?",[$shop_id]);
	$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
	$shop_order_in_service_amount = db_query("select count(distinct order_id) in_service_amount "
			." from v_order "
			." where shop_id=? "
			." and predicted_time like ? "
			." and order_status in (1,2,3,4,9,20) ",[$shop_id, "%".$predicted_date."%"]);
	
	if(!$shop) {
		return makeError(9000, "厨房不存在");
	}
	
 	if($shop_id==113) {
		if($shop_order_in_service_amount[0]['in_service_amount']>100) {
			 return makeError(9000, "厨房每天接单不能超过30单，为保证服务品质请选择其他厨房！");
		}
	} else if($shop_id==15) {
		if($shop_order_in_service_amount[0]['in_service_amount']>50) {
			 return makeError(9000, "厨房每天接单不能超过30单，为保证服务品质请选择其他厨房！");
		}
	} else if($shop_order_in_service_amount[0]['in_service_amount']>30) {
		return makeError(9000, "厨房每天接单不能超过30单，为保证服务品质请选择其他厨房！");
	}
	
	if(!check_values($is_takeaway, array(0,1))) {
		return makeError(9000, "是否自取参数不合法");
	}
	
	check_stock($shop_id);
	
	$now_time = substr($now,11,5);
	if($time_type==0) {
		if($now_time>$shop['lunch_stop_time']) {
			return makeError(9999, "该店铺已经停止接单午餐");
		}
	} else if($time_type==1) {
		if($now_time>$shop['dinner_stop_time']) {
			return makeError(9999, "该店铺已经停止接单晚餐");
		}
	}
	
	$time_45_later = new DateTime();
	$time_45_later->add(new DateInterval("PT45M"));
	$time_45_later_string = $time_45_later->format("H:i");
	
	$now_hour = substr($time_45_later_string,0,2);
	$now_minute = substr($time_45_later_string,3,2);
	
	if($now_minute>30) {
		$now_hour++;
		$now_minute = 0;
	} else {
		$now_minute = 30;
	}
	
	$now_half = getXX($now_hour).":".getXX($now_minute);
	
	if($time_type==0||$time_type==2) {
		//午餐
		$time_start = $shop['lunch_time_start'];
		$time_end = $shop['lunch_time_end'];
	} else {
		//晚餐
		$time_start = $shop['dinner_time_start'];
		$time_end = $shop['dinner_time_end'];
	}
	
	if($time_type==0 || $time_type==1) {
		if($now_half>$time_start) {
			$time_start = $now_half;
		}
	}
	
	
	if($time_start>=$time_end) {
		return makeError(9000, "选择的用单时间失效，请重新选择");
	}
	
	db_autocommit(false);
	
	$product_map = array();
	
	foreach($products as $product) {
		$product_map[$product['product_id']] = $product;
	}
	
	if($is_takeaway) {
		$delivery_fee = 0;
	} else {
		$delivery_fee = 0;
	}
	
	close_unpaid_order();

	$order_count = db_query_value("select count(order_id) from v_order where user_id=? and order_status not in (5,6,7,10,11,12,13,14,25,26) ",[$user_id]);
	$is_first_order = ($order_count==0)?1:0;
	
	$pre_order_count = db_query_value("select count(order_id) from v_order where user_id=? "
			." and order_status not in (5,6,7,10,11,12,13,14,25,26) " 
			." and predicted_time like ? ",[$user_id, "%".get_tomorrow_date()."%"]);
	//$is_first_pre_order = ($pre_order_count==0)?1:0;
	$is_first_pre_order = 0;
	
	$cart_items = json_decode($cart_items_json,true);
	$total_fee = 0.0;
	foreach ($cart_items as $item) {
		if(!is_numeric($item['count'])) {
			return makeError(9000, "菜品数量必须是数字");
		}
		$total_fee += $item['count']*$product_map[$item['product_id']]['product_price'];
	}
	
	$coupon_amount = 0;
	
	if($coupon_id) {
		$coupon = db_query_row("select * from ejew_coupon 
				where coupon_id = ? and validation_end>=? and coupon_used = 0",[$coupon_id,get_today_date()." 00:00:00"]);
		if($coupon) {
			$coupon_amount = $coupon['coupon_amount'];
			$total_fee -= $coupon_amount;
		} else {
			return makeError(9000, "粮票无效");
		}
	}
	
	$total_fee += $delivery_fee;
	$total_fee -= $is_first_order?$first_order_discount:0;
	
	if(!$is_first_order) {		
/* 		if($is_first_pre_order) {
			if($time_type==2 || $time_type==3) {
				$total_fee -= $pre_order_discount;
			}				
		}
 */	}
	
	if($total_fee<0) $total_fee=0;
	
	if($total_fee!=$total_fee_client) {
		return makeError(9999, "产品金额发生变化，请重新下单");
	}
		
	//检测就餐时间
	$now_time = substr($now,11,5);
	if($time_type==0) {
		if($now_time>$shop['lunch_stop_time']) {
			return makeError(9999, "该厨房已经停止接单午餐");
		}
	} else if($time_type==1) {
		if($now_time>$shop['dinner_stop_time']) {
			return makeError(9999, "该厨房已经停止接单晚餐");
		}
	}
	
	//检测厨房是否开张
	if($shop['operation_status']!=1) {
		return makeError(9999, "该厨房停止营业啦");
	}
	
	if($time_type==0 || $time_type==1) {
		$leave_count = db_query_value("select count(1) from ejew_shop_status where shop_id=? and leave_date=?",[$shop_id,$today_date]);
	} else {
		$leave_count = db_query_value("select count(1) from ejew_shop_status where shop_id=? and leave_date=?",[$shop_id,$tomorrow_date]);
	}
	
	if($leave_count>0) {
		return makeError(9999, "该厨房今天休息啦");
	}
	
	$order = array(
		"order_channel"=>"weixin",
		"user_id"=>$user_id,
		"shop_id"=>$shop_id,
		"order_time"=>$now,
		"order_type"=>$order_type,
		"predicted_time"=>$predicted_time,
		"order_address"=>$address['user_address'],
		"order_address_row2"=>$address['user_address_row2'],
		"contact_name"=>$address['contact_name'],
		"order_lng"=>$address['address_lng'],
		"order_lat"=>$address['address_lat'],
		"phone"=>$address['phone'],
		"order_memo"=>$order_memo,
		"coupon_id"=>$coupon_id?$coupon_id:0
	);
	
	$order_id = db_save("ejew_order", $order);
	
	$order_b = array(
		"order_id"=>$order_id,
		"order_status"=>$total_fee==0?1:0,
		"complain_status"=>0,
		"is_first_order"=>$is_first_order,
		"first_order_amount"=>$is_first_order?$first_order_discount:0,
		"delivery_method"=>$delivery_method,
		"delivery_fee"=>$delivery_fee,
		"coupon_amount"=>$coupon_amount,
		"total_fee"=>$total_fee,
		"pay_status"=>0
	);
	
	db_save("ejew_order_b",$order_b);
	
	if($coupon_id) {
		$coupon_item = array(
			"coupon_id"=>$coupon_id,
			"coupon_used"=>1
		);
		db_update_item("ejew_coupon", "coupon_id", $coupon_item);
	}
	
	foreach($cart_items as $item) {
		$order_product = array(
				"order_id"=>$order_id,
				"product_id"=>$item['product_id'],
				"product_count"=>$item['count'],
				"product_price"=>$product_map[$item['product_id']]['product_price']
		);
		db_save("ejew_order_product", $order_product);
		
		$product = db_query_row("select * from ejew_product where product_id = ?",[$item['product_id']]);
		
		if(!$product) {
			return makeError(9000, "菜品不存在");
		}
		
		$stock_count = db_query_value("select stock_count from ejew_product_stock 
				where product_id = ? and stock_date = ?",/* and stock_period = ? "*/
				[$item['product_id'],$predicted_date/*,$order_type*/]);
		
		if(($stock_count-$item['count'])<0) {
			return makeError(9000, $product['product_name']."库存不足");
		}
		
		update_stock($item['product_id'], "-".$item['count'], $time_type);
		
	}
	
	$order_status = array(
		"order_id"=>$order_id,
		"order_status"=>$total_fee==0?1:0,
		"order_status_title"=>"订单提交成功",
		"order_status_content"=>"订单号：".$order_id."，请耐心等待商家确认",
		"create_time"=>$now
	);
	
	db_save("ejew_order_status", $order_status);
	
	db_commit();
	
	if($total_fee==0) {
		app_log("====call push_shop_message========");
		push_shop_message($shop_id, 1, ("您收到一笔订单，订单号:".$order_id), $order_id);
		
		$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
		send_sms($shop['shop_phone'], "您有一个新订单，请打开家厨端查看！");
		
		db_commit();
	}
		
	return makeSuccess(array(
		"order_id"=>$order_id
	));
}

function create_order_activity_1024() {
	global $time_array,$first_order_discount,$pre_order_discount;
	
	$address_id = 567;
	$user_id = get_login_user_id();
	$delivery_time_index = 23;
	$order_memo = "";
	$is_takeaway = 0;
	$shop_id = 55;
	$time_type = 2;
	$cart_items_json = p("cart_items");
	$now = get_now();
	$order_type = 1;
	$delivery_method = 1;
	$total_fee_client = 1.00;
	$coupon_id = 10000000;
	$today_date = get_today_date();
	$tomorrow_date = get_tomorrow_date();
	
	$predicted_date = ($time_type==0||$time_type==1)?$today_date:$tomorrow_date;
	$predicted_time = $predicted_date." ".$time_array[$delivery_time_index].":00";
	
	$address = db_query_row("select * from ejew_user_address where address_id=?",[$address_id]);
	
	$products = db_query("select * from ejew_product where shop_id=?",[$shop_id]);
	$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
	
	
	db_autocommit(false);
	
	$product_map = array();
	
	foreach($products as $product) {
		$product_map[$product['product_id']] = $product;
	}
	
	$total_fee = 0.0;
	
	$total_fee=1.00;	
		
	$order = array(
		"order_channel"=>"weixin",
		"user_id"=>$user_id,
		"shop_id"=>55,
		"order_time"=>$now,
		"order_type"=>$order_type,
		"predicted_time"=>$predicted_time,
		"order_address"=>"望京商业广场",
		"order_address_row2"=>"1-1-1",
		"contact_name"=>"Special One",
		"order_lng"=>113.8,
		"order_lat"=>39.9,
		"phone"=>'12345678901',
		"order_memo"=>"",
		"coupon_id"=>0
	);
	
	$order_id = db_save("ejew_order", $order);
	
	$order_b = array(
		"order_id"=>$order_id,
		"order_status"=>$total_fee==0?1:0,
		"complain_status"=>0,
		"is_first_order"=>0,
		"first_order_amount"=>0,
		"delivery_method"=>$delivery_method,
		"delivery_fee"=>0,
		"coupon_amount"=>0,
		"total_fee"=>1.00,
		"pay_status"=>0
	);
	
	db_save("ejew_order_b",$order_b);
	
	db_commit();
		
	return makeSuccess(array(
		"order_id"=>$order_id
	));
}

function pay_order() {
	$order_id = check_not_null("order_id");
	$order = db_query_row("select * from v_order where order_id=?",[$order_id]);
	if(!$order) {
		return makeError(9000, "订单不存在");
	}
	$prepare_result = wx_prepare_order($order);
	if($prepare_result['return_code']=='SUCCESS' && $prepare_result['result_code']=='SUCCESS') {
		$result = array(
				"appId"=>$prepare_result['appid'],
				"timeStamp"=>(string)time(),
				"nonceStr"=>get_random_string(24),
				"package"=>"prepay_id=".$prepare_result['prepay_id'],
				"signType"=>"MD5"
		);
		$result['paySign'] = wx_sign($result);
	} else {
		return makeError(9000, "创建微信订单失败");
	}
	return makeSuccess($result); 
}

function get_order_list () {
	global $errMsg,$listOfValue;
	$user_id = get_login_user_id();
	if(!$user_id) {
		return makeError(1010, $errMsg['1010']);
	}
	
	$offset = p("offset");
	$count = p("count");
	check_page_params();
	
	$orders = db_query("select * ".
			"from ejew_order a,ejew_order_b b,ejew_shop s ".
			"where a.order_id=b.order_id and a.shop_id=s.shop_id and a.user_id=? and b.order_status<>0 ".
			" order by order_time desc limit ?,? ",[$user_id,$offset,$count]);
	
	ob_start();
		
	foreach ($orders as $order) {
?>
<div class="Item">
	<div class="Row1" onclick="loadOrderStatus(<?php echo $order['order_id']?>);">
		<span onclick="showShop(<?php echo $order['shop_id']?>,'Order');"><?php echo $order['shop_name']?></span>
		<img height="10px" src="images/next.png"/>
		<i><?php echo get_lov('order_status_user',$order['order_status'])?></i>
	</div>
	
	<div class="Row2" onclick="loadOrderStatus(<?php echo $order['order_id']?>);">
		<span>就餐时间：</span>
		<b><?php 
			echo '('.get_lov('delivery_method',$order['delivery_method']).
				") ".$order['predicted_time']?></b>
	</div>
	
	<div class="Row3">
		<span onclick="loadOrderStatus(<?php echo $order['order_id']?>);">订单合计：</span>
		<b onclick="loadOrderStatus(<?php echo $order['order_id']?>);"><?php echo floatval($order['total_fee'])?>元</b>
		<?php if($order['order_status']==0) {?>
		<i onclick="wx_pay_order('<?php echo $order['order_id']?>');">支付</i>
		<?php }?>
	</div>
</div>
<?php
	}
	
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
	
}


function get_order_status() {
	global $time_map,$time_duration_array;
	$order_id = p("order_id");
	$order_status_list = db_query("select * from ejew_order_status where order_id=? order by order_status_id desc",[$order_id]);
	$count = count($order_status_list);
	$now = get_now();
	
	$order = db_query_row("select * ".
			"from ejew_order a,ejew_order_b b,ejew_shop s ".
			"where a.order_id=b.order_id and a.shop_id=s.shop_id and a.order_id=? ",
			[$order_id]);
	
	$order_products = db_query("select *,op.product_count pcount,op.product_price*op.product_count amount from ejew_order_product op,ejew_product p ".
			"where op.product_id=p.product_id and op.order_id=?",[$order_id]);
			
	ob_start();
	
?>
	<div class="OrderTab">
		<div  id="OrderTabStatus" class="Item Selected" onclick="showOrderStatus();">订单状态</div>
		<div id="OrderTabDetail" class="Item" onclick="showOrderDetail();">订单详情</div>
		<div class="Seperator"></div>
	</div>
	
	<div id="OrderDetailList" class="OrderDetailList" style="display:none;">
	
		<div class="Section">
			<div class="Item NoBorder">
				<div class="ShopName"><?php echo $order['shop_name']?></div>
				<img class="Next" src="images/next.png"/>
			</div>
			<?php foreach($order_products as $order_product) {?>
			<div class="Item">
				<span><?php echo $order_product['product_name']?> x <?php echo floatval($order_product['pcount'])?></span>
				<i>￥<?php echo floatval($order_product['amount'])?></i>
			</div>
			<?php }?>
		</div>
		
		<div class="Section">
			<?php if($order['delivery_fee']>0) {?>
			<div class="Item1">
				<span>运费</span>
				<i>￥<?php echo floatval($order['delivery_fee'])?></i>
			</div>
			<?php }?>
			<?php if($order['first_order_amount']>0) {?>
			<div class="Item1">
				<span>首单优惠</span>
				<i>-￥<?php echo floatval($order['first_order_amount'])?></i>
			</div>
			<?php }?>
			<div class="Item1">
				<span>粮票</span>
				<i>-￥<?php echo floatval($order['coupon_amount'])?></i>
			</div>
			<div class="Item1 NoBorder">
				<span>合计</span>
				<i>￥<?php echo floatval($order['total_fee'])?></i>
			</div>
		</div>
		
		<div class="Section Pad">
			<b>订单编号：<?php echo $order['order_id']?></b>
			<b>下单时间：<?php echo $order['order_time']?></b>
			<b>支付方式：微信支付</b>
			<b>联系方式：<?php echo $order['phone']?></b>
			<b>就餐时间：<?php 
			$predicted_time = $order['predicted_time'];
			$date = substr($predicted_time,5,5);
			$time = substr($predicted_time,11,5);
			$time_index = $time_map[$time];
			$time_duration = $time_duration_array[$time_index];
			echo $date." ".$time_duration;
			?></b>
			<b>就餐方式：<?php echo get_lov("delivery_method", $order['delivery_method'])?></b>
			<b>送餐地址：<?php echo $order['order_address'].$order['order_address_row2']?></b>
		</div>
	
	</div>
	
	<div id="OrderStatusList" class="StatusList">
<?php 
	
	
	for($i=0;$i<$count;$i++) {
		$item = $order_status_list[$i];
?>
<div class="Item">
	<div class="Head">
		<?php if($i==0) {?>
		<div class="Round"></div>
		<?php } else {?>
		<div class="RoundGray"></div>
		<?php }?>
		<?php if($i<$count-1) {?>
		<div class="Line"></div>
		<?php }?>
	</div>
	<div class="Content">
		<div class="Row1"><?php echo $item['order_status_title']?></div>
		<div class="Row2"><?php echo is_string_empty($item['order_status_content'])?"&nbsp;":$item['order_status_content']?></div>
		<div class="Time"><?php echo substr($item['create_time'],11,5)?></div>
		<div class="Arrow"></div>
	</div>
</div>
<?php 
	}
?>
</div>

<div style="height:80px;"></div>

<div class="OrderStatusBar">
	<?php if($order['order_status']==4) {?>
	<span onclick="editComment(<?php echo $order['order_id']?>);">评价</span>
	<?php }?>
	<?php
		$predicted_datetime = new DateTime($predicted_time);
		$predicted_datetime->add(new DateInterval("PT1H"));
		$finish_time_out = $predicted_datetime->format("Y-m-d H:i:s");
		if($order['order_status']==0 || $order['order_status']==1) {?>
	<span onclick="cancelOrder(<?php echo $order['order_id']?>);">取消订单</span>
	<?php } else if(( check_values($order['order_status'], array(2,3,20,21,22)) && $now>$finish_time_out)) {?>
	<span onclick="cancelOrder(<?php echo $order['order_id']?>,true,'<?php echo $order["contact_phone"]?>');">取消订单</span>
	<?php }?>
</div>
<?php 
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
	
}

function get_comment_list() {
	$shop_id = p("shop_id");
	$comments = db_query(
			"select * from ejew_comment c,ejew_user u 
			where c.user_id=u.user_id and shop_id = ?",
		[$shop_id]);
	
	foreach ($comments as &$comment) {
		$order_id = $comment['order_id'];
		$products = db_query("select product_name from ejew_order_product op,ejew_product p
			where op.product_id=p.product_id and order_id = ?",[$order_id]);
		
		$product_string = "";
		foreach($products as $product) {
			$product_string .= $product['product_name']." ";
		}
		$product_string = rtrim($product_string);
		$comment['product'] = $product_string;
	}
	
	ob_start();
	
	if(count($comments)==0) {
?>
	<div class="noDataList"><img src="images/no_comment.png"/></div>
	<script type="text/javascript">$("#CommentList").css("backgroundColor","#F0F0F0");</script>
<?php 
	} else {
?>
<script type="text/javascript">$("#CommentList").css("backgroundColor","#FFF");</script>
<?php 
	}
	
	
	foreach($comments as &$comment) {
		
?>
<div class="Item">
	<div class="Row1">
		<span><?php echo maskString($comment['user_phone'], 3, 4)?></span>
		<i>
			<img src="images/star_<?php echo $comment['comment_mark']>=1?"on":"off"?>.png"/>
			<img src="images/star_<?php echo $comment['comment_mark']>=2?"on":"off"?>.png"/>
			<img src="images/star_<?php echo $comment['comment_mark']>=3?"on":"off"?>.png"/>
			<img src="images/star_<?php echo $comment['comment_mark']>=4?"on":"off"?>.png"/>
			<img src="images/star_<?php echo $comment['comment_mark']>=5?"on":"off"?>.png"/>
		</i>
		<b><?php echo substr($comment['comment_time'],0,10)?></b>
	</div>
	<div class="Row2"><?php echo $comment['comment_detail']?></div>
	<?php if(is_string_not_empty($comment['reply_detail'])) {?>
	<div class="Row3">
		<span>厨师回复：<?php echo $comment['reply_detail']?></span>
		<b></b>
	</div>
	<?php }?>
	<div class="Row4"><?php echo $comment['product']?></div>
</div>
<?php
		
	}
	$html = ob_get_contents();
	ob_end_clean();
	app_log($html);
	return makeSuccess(array(
		"html"=>$html
	));
}

function wx_login() {
	$wx_user = get_login_wx_user();
	if($wx_user) {
		return makeSuccess(array(
			"user_avatar"=>$wx_user['headimgurl']
		));
	} else {
		return makeSuccess(array("user_avatar"=>"images/default_avatar.png"));
	}
	
	/*
	global $wx_appid,$wx_appsecret;
	$code = p("code");
	$url = "https://api.weixin.qq.com/sns/oauth2/access_token";
	$params = array(
			"appid"=>$wx_appid,
			"secret"=>$wx_appsecret,
			"code"=>$code,
			"grant_type"=>"authorization_code"
	);
	
	$content = http_get($url,$params);
	$response = json_decode($content,true);
		
	$access_token = $response['access_token'];
	$expires_time = time()+$response['expires_in'];
	$refresh_token = $response['refresh_token'];
	$openid = $response['openid'];
	
	$url = "https://api.weixin.qq.com/sns/userinfo";
	$params = array(
		"access_token"=>$access_token,
		"openid"=>$openid,
		"lang"=>"zh_CN"
	);
	
	$content = http_get($url,$params);
	$response = json_decode($content,true);
		
	$nickname = $response['nickname'];
	$sex = $response['sex'];
	$province = $response['province'];
	$city = $response['city'];
	$country = $response['country'];
	$headimgurl = $response['headimgurl'];
	$session_id = gen_session_id();
	
	$wx_user = array(
		"openid"=>$openid,
		"access_token"=>$access_token,
		"expires_time"=>$expires_time,
		"refresh_token"=>$refresh_token,
		"nickname"=>$nickname,
		"sex"=>$sex,
		"province"=>$province,
		"city"=>$city,
		"country"=>$country,
		"headimgurl"=>$headimgurl,
		"session_id"=>$session_id
	);
	
	db_update("delete from ejew_wx_user where openid=?",[$openid]);
	
	db_save("ejew_wx_user", $wx_user);
	
	return makeSuccess(array(
			"user_avatar"=>$headimgurl
	));
	*/
}

/*
function wx_post_test() {
	$post_data = "<xml><aaa>1111</aaa></xml>";
	http_post_xml("http://localhost/ejew/client/api.php?method=wx_post_recv", $post_data);
}

function wx_post_recv() {
	ob_start();
	readfile("php://input");
	$data = ob_get_contents();
	ob_end_flush();
	app_log("-----------".$data);
	return "result:".$aaa;
}
*/

function wx_notify_test() {
	$post_data = "<xml>
			<return_code>SUCCESS</return_code>
			<out_trade_no>32</out_trade_no>
		</xml>";
	http_post_xml("http://localhost/ejew/wx_pay_notify.php?XDEBUG_SESSION_START=ECLIPSE_DBGP&KEY=14397104276957", $post_data);
	return makeSuccess();
}

function wx_pay_test() {
	$order = array(
		"order_id"=>"TEST0001",
		"total_fee"=>0.01
	);
	
	wx_prepare_order($order);
	
	return makeSuccess();
}

function wx_prepare_order ($order) {
	global $wx_appid,$wx_appsecret,$wx_pay_key,$wx_mch_id,$host_name;
	
	$openid = get_login_wx_openid();
	$time_start = date("YmdHis");
	$datetime = new DateTime();
	$datetime->add(new DateInterval("PT20M"));
	$time_end = $datetime->format("YmdHis");
	
	$params = array(
		"appid"=>$wx_appid,
		"mch_id"=>$wx_mch_id,
		"nonce_str"=>get_random_string(24),
		"body"=>"ejiaewei",
		"attach"=>"1",
		"out_trade_no"=>"REAL".$order['order_id'],
		"total_fee"=>$order['total_fee']*100,
		"spbill_create_ip"=>get_client_ip(),
		"time_start"=>$time_start,
		"time_expire"=>$time_end,
		"notify_url"=>"http://".$host_name."/wx_pay_notify.php",
		"trade_type"=>"JSAPI",
		"openid"=>$openid
	);
	
	$params['sign'] = wx_sign($params);
	$post_data = array2xml($params);
	
	$response = http_post_xml("https://api.mch.weixin.qq.com/pay/unifiedorder", $post_data);
	$response_data = xml2array($response);
	
	return $response_data;
}

function wx_sign($params){
	global $wx_pay_key;
	ksort($params);
	$string1 = "";
	foreach($params as $key=>$value) {
		$string1 .= $key."=".$value."&";
	}
	$string1 .= "key=".$wx_pay_key;
	app_log("string_sign:".$string1);
	$sign = strtoupper(md5($string1));
	return $sign;
}


function save_order_comment () {
	$user_id = get_login_user_id();
	if(is_string_empty($user_id)) {
		return makeError(9000, "未登录");
	}
	$order_id = p("order_id");
	$comment_mark = p("comment_mark");
	if((!is_number_string($comment_mark)) || $comment_mark<1 || $comment_mark>5) {
		return makeError(9000, "非法评级");
	}
	$comment_detail = p("comment_detail");
	if(is_string_empty($comment_detail)) {
		return makeError(9000, "评论不能为空");
	}
	if(strlen($comment_detail)>500) {
		return makeError(9000, "评论内容需小于500字符");
	}
	
	$comment = db_query_row("select comment_id from ejew_comment where order_id = ?",[$order_id]);
	if($comment) {
		return makeError(9000, "不能重复评论");
	}
	$order = db_query_row("select * from v_order where order_id = ?",[$order_id]);
	if(!$order) {
		return makeError(9000, "订单不存在");
	}
	
	if($order['order_status']!=4) {
		return makeError(9000, "只有已完成的订单才能评价");
	}
	
	db_autocommit(false);
	$comment = array(
		"shop_id"=>$order['shop_id'],
		"user_id"=>$user_id,
		"order_id"=>$order_id,
		"comment_mark"=>$comment_mark,
		"comment_detail"=>$comment_detail,
		"comment_time"=>get_now(),
		"comment_approval"=>3
	);
	
	$comment_id = db_save("ejew_comment", $comment);
	
	set_order_status($order_id, 9);
	add_order_status($order_id, 9, "已评价", "谢谢您的评价");
	
	db_commit();
	
	push_shop_message($order['shop_id'], 2, "您收到一条评论", $comment_id);
	
	db_commit();
	
	return makeSuccess();
	
}

function get_coupon_list() {
	$user_id = get_login_user_id();
	if(!$user_id) {
		return makeError(9000, "未登录");
	}
	
	$validate = p("validate");
	$sql = "select * from ejew_coupon where coupon_used=0 and user_id=? ";
	if($validate) {
		$sql .= " and validation_end>=?";
	} else {
		$sql .= " and validation_end<?";
	}
	
	$coupons = db_query($sql,[$user_id,get_today_date()." 00:00:00"]);
	
	ob_start();
	
	if(count($coupons)==0) {
?>
	<div class="noDataList"><img src="images/no_coupon.png"/></div>
<?php 
	}
	foreach ($coupons as $coupon) {
?>
<?php if($validate==1) {?>
<div class="Item">
	<div class="Col1"></div>
	<div class="Col1a"></div>
	<div class="Col2"></div>
	<div class="Col2a"></div>
	<div class="Amount">
		<span>￥</span>
		<b><?php echo floatval($coupon['coupon_amount'])?></b>
	</div>
	<div class="Desc">
		<div class="Type"><?php echo $coupon['coupon_name']?></div>
		<div class="Info">有效期至<?php echo substr($coupon['validation_end'],0,10)?></div>
		<div class="Info"><?php echo $coupon['condition_desc']?></div>
	</div>
</div>
<?php } else {?>
<div class="Item greyBorder">
	<div class="Col1_grey"></div>
	<div class="Col1a"></div>
	<div class="Col2_grey"></div>
	<div class="Col2a"></div>
	<div class="Amount_grey">
		<span>￥</span>
		<b><?php echo floatval($coupon['coupon_amount'])?></b>
	</div>
	<div class="Desc">
		<div class="Type_grey"><?php echo $coupon['coupon_name']?></div>
		<div class="Info_grey">有效期至<?php echo substr($coupon['validation_end'],0,10)?></div>
		<div class="Info_grey"><?php echo $coupon['condition_desc']?></div>
	</div>
</div>
<?php }?>
<?php 
	}
?>
<?php if($validate==1) {?>
<div class="Link">
	<?php if(count($coupons)>0) {?>
	<span>没有更多粮票了 |</span>
	<?php }?>
	<span class="PinkText" onclick="showInvalidCoupon();">查看过期粮票</span>
</div>
<?php } else {?>
<div class="Link">
	<span class="PinkText" onclick="showValidCoupon();">查看未过期粮票</span>
</div>

<?php }?>

<div style="height:50px;"></div>

<?php 
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
	
}

function get_coupon_select_list() {
	$user_id = get_login_user_id();
	if(!$user_id) {
		return makeError(9000, "未登录");
	}

	$coupons = db_query("select * from ejew_coupon where coupon_used=0 and user_id = ? and validation_end >= ?",
			[$user_id,get_today_date()." 00:00:00"]);

	ob_start();
	
	if(count($coupons)==0) {
	
?>
	<div class="noDataList"><img src="images/no_coupon.png"/></div>
<?php 
	}
	
	foreach ($coupons as $coupon) {
		?>

<div class="Item" onclick="selectCoupon(<?php echo $coupon['coupon_id'].",".$coupon['coupon_amount']?>, <?php echo ($coupon['coupon_name']=='首单返券')?1:0?>);">
	<div id="CouponSelector_<?php echo $coupon['coupon_id']?>" class="CouponSelector" style="display: none;"><img src="images/coupon_select.png"/></div>
	<div class="Col1"></div>
	<div class="Col1a"></div>
	<div class="Col2"></div>
	<div class="Col2a"></div>
	<div class="Amount">
		<span>￥</span>
		<b><?php echo floatval($coupon['coupon_amount'])?></b>
	</div>
	<div class="Desc">
		<div class="Type"><?php echo $coupon['coupon_name']?></div>
		<div class="Info">有效期至<?php echo substr($coupon['validation_end'],0,10)?></div>
		<div class="Info"><?php echo $coupon['condition_desc']?></div>
	</div>
</div>

<?php 
	}
	$html = ob_get_contents();
	ob_end_clean();
	
	return makeSuccess(array(
		"html"=>$html
	));
	
}

function get_shop_info() {
	$shop_id = p("shop_id");
	$shop = db_query_row("select shop_id,shop_name,shop_lng,shop_lat,shop_address,shop_address_row2 from ejew_shop where shop_id=?",[$shop_id]);
	if($shop) {
		return makeSuccess($shop);
	} else {
		return makeError(9000,"未查询到厨房信息");
	}
}

function cancel_order() {
	global $time_map,$time_duration_array;
	$order_id = p("order_id");
	$user_id = get_login_user_id();
	$now = get_now();
	
	if(!$user_id) {
		return makeError(9000, "用户未登录");
	}
	$order = db_query_row("select * from v_order o, ejew_user u where o.order_id = ? and o.user_id = ? and o.user_id=u.user_id",[$order_id,$user_id]);
	
	if($order==null) {
		return makeError(9000, "订单不存在");
	}
	
	$order_status = $order['order_status'];
	
	$predicted_time = $order['predicted_time'];
	$predicted_datetime = new DateTime($predicted_time);
	$predicted_datetime->add(new DateInterval("PT1H"));
	$finish_time_out = $predicted_datetime->format("Y-m-d H:i:s");
	$is_time_out = $now>$finish_time_out;
	
	$month = substr($predicted_time,5,2);
	$days = substr($predicted_time,8,2);
	$time = substr($predicted_time, 11,5);
	$time_duration = $time_duration_array[$time_map[$time]];
	$order_time_msg = intval($month)."月".intval($days)."日".$time_duration;	

	$dinner_str = (($order['order_type']==0)?"午餐":"晚餐");
	
	$user_phone = $order['user_phone'];
	
	$sms = null;
	
	if($is_time_out && check_values($order_status, array(2,3,20,21,22))) {
		
	} else if(check_values($order_status, array(0,1))) {
		
	} else {
		return makeError(9000, "当前状态，不能取消订单");
	}
	
	db_autocommit(false);
	
	$should_release_coupon = false;
	
	if($order_status==0) {
		set_order_status($order_id,11);
		add_order_status($order_id, 11, "订单取消", "");
		$should_release_coupon = true;
	} else if($order_status==1) {
		set_order_status($order_id,6);
		add_order_status($order_id, 6, "订单取消", "主动取消订单，退款处理中");
		$should_release_coupon = true;
	} else if(check_values($order_status, array(2,3,20,21,22))) {
		set_order_status($order_id, 6);
		add_order_status($order_id, 6, "订单取消", "取消订单，退款处理中");
		$should_release_coupon = true;
	}
	
 	$sms = "您预定的".$order_time_msg."的".$dinner_str."，你已经取消了订单，下次继续预定我们哦";
	if($sms) send_sms($user_phone, $sms);

	//add by zgx
 	$sms_shop = "大厨大厨注意了，就餐时间".$order_time_msg."，订餐人".$order['nick_name']."的订单，已经取消了，不用做了哦";
	$shopsql = "select shop_phone from ejew_shop where shop_id=".$order['shop_id'];
	$shop_phone = db_query_value($shopsql);
	if($sms_shop && $shop_phone) send_sms($shop_phone, $sms_shop);
	
	if($should_release_coupon) {
		$coupon_id = $order['coupon_id'];
		db_update("update ejew_coupon set coupon_used = 0 where coupon_id=?",[$coupon_id]);
		$order_products = db_query("select * from ejew_order_product where order_id=?",[$order_id]);
		foreach ($order_products as $order_product) {
			$predicted_date = substr($predicted_time, 0,10);
			$time_type = -1;
			if($predicted_date==get_today_date()) {
				$time_type = 0;
			} else {
				$time_type = 2;
			}
			
			if($time_type!=-1) {
				update_stock($order_product['product_id'], "+".$order_product['product_count'], $time_type);
			}
		}
	}
	
	db_commit();
	
	return makeSuccess();
	
}

function save_feedback() {
	$phone = p("phone");
	$content = check_not_null("content","反馈内容");
	if(!is_mobile_phone($phone)) {
		return makeError(9000, "请输入合法的手机号");
	}
	
	if(strlen($content)>2000) {
		return makeError(9000, "请输入2000字符以内");
	}
	
	$item = array(
		"phone"=>$phone,
		"content"=>htmlspecialchars($content)
	);
	
	db_save("ejew_feedback", $item);
	
	return makeSuccess();
}

function reset_coupon() {
	$coupon_id = p("coupon_id");
	db_update("update ejew_coupon set coupon_used = 0 where coupon_id=?",[$coupon_id]);	
}
