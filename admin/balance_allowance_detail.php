<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$start_date = trim(p("start_date"));
$end_date = trim(p("end_date"));
if(is_string_empty($start_date)) {
	$start_date = "2015-08-01";
}
if(is_string_empty($end_date)) {
	$end_date = date("Y-m-d");
}

$page = 1;
$page_count = 20;
$shop_id = p("shop_id");

$shop = db_query_row("select * from ejew_bill where deleted=0 and shop_id=?",array($shop_id));

if($shop) {
	//$data = db_query("select * from ejew_bill where deleted=0 and shop_id=? order by bill_time desc ",array($shop_id));
/* 	$sql = "select *, "
			." (DATE_FORMAT(o.predicted_time,'%Y-%m-%d')<>DATE_FORMAT(o.order_time,'%Y-%m-%d') and bill_amount > 0 and ob.is_first_order=0) * 5.00 pre_order_amount, "
			." (ob.is_first_order=1 and bill_amount > 0) * 15.00 is_first_order_amount, "
			." (bill_amount > 0) * ob.coupon_amount use_coupon_amount, "
			." (bill_amount > 0) * ob.total_fee total_fee_amount"
			." from ejew_bill b, ejew_order o , ejew_order_b ob where b.deleted=0 and b.shop_id=? "
			." and b.order_id=o.order_id and b.order_id = ob.order_id "
			." and (DATE_FORMAT(o.predicted_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." and ((pre_order_amount + is_first_order_amount + use_coupon_amount + total_fee_amount） <>total_fee_amount) "
			." order by b.bill_time desc ";

 */

/* 	$sql = "select *, DATE_FORMAT(o.order_time,'%Y-%m-%d') order_time, DATE_FORMAT(o.predicted_time,'%Y-%m-%d') predicted_time, "
			." (DATE_FORMAT(o.predicted_time,'%Y-%m-%d')<>DATE_FORMAT(o.order_time,'%Y-%m-%d') and bill_amount > 0 and ob.is_first_order=0) * 5.00 pre_order_amount, "
			." (ob.is_first_order=1 and bill_amount > 0) * 15.00 is_first_order_amount, "
			." (bill_amount > 0) * ob.coupon_amount use_coupon_amount, "
			." (bill_amount > 0) * ob.total_fee total_fee_amount"
			." from ejew_bill b, ejew_order o , ejew_order_b ob where b.deleted=0 and b.shop_id=? "
			." and b.order_id=o.order_id and b.order_id = ob.order_id "
			." and (DATE_FORMAT(o.predicted_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." order by b.bill_time desc,  o.user_id desc , predicted_time desc, order_time desc ";
 */
	
	$sql = "select *, DATE_FORMAT(o.order_time,'%Y-%m-%d') order_time, DATE_FORMAT(o.predicted_time,'%Y-%m-%d') predicted_time, "
			." (DATE_FORMAT(o.predicted_time,'%Y-%m-%d')<>DATE_FORMAT(o.order_time,'%Y-%m-%d') and bill_amount > 0 and ob.is_first_order=0) * 5.00 pre_order_amount, "
			." (ob.is_first_order=1 and bill_amount > 0) * 15.00 is_first_order_amount, "
			." (bill_amount > 0) * ob.coupon_amount use_coupon_amount, "
			." (bill_amount > 0) * ob.total_fee total_fee_amount"
			." from ejew_bill b, ejew_order o , ejew_order_b ob where b.deleted=0 and b.shop_id=? "
			." and b.order_id=o.order_id and b.order_id = ob.order_id "
			." and (DATE_FORMAT(o.order_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." order by b.bill_time desc,  o.user_id desc , predicted_time desc, order_time desc ";
	
	$data = db_query($sql, array($shop_id, $start_date, $end_date));

	$new_user_id = NULL;
 	$new_order_date = NULL;
	$new_predicted_date = NULL;
	$old_user_id = NULL;
	$old_order_date = NULL;
	$old_predicted_date = NULL;
	
	foreach($data as $bill_item) {
 		if(($bill_item['pre_order_amount']) > '0.00') {
			$new_user_id = $bill_item['user_id'];
 			$new_order_date = $bill_item['order_time'];
			$new_predicted_date = $bill_item['predicted_time'];
			
			if (strcmp($new_user_id, $old_user_id) == 0 
				&& strcmp($new_order_date, $old_order_date) == 0 
				&& strcmp($new_predicted_date, $old_predicted_date) == 0) {
					$bill_item['pre_order_amount'] = '0.00';
					continue;
			}
		}
 		
		$old_user_id = $bill_item['user_id'];
		$old_order_date = $bill_item['order_time'];
		$old_predicted_date = $bill_item['predicted_time'];	
	}
	
	$sum_pre_order_amount = 0;
	$sum_is_first_order_amount = 0;
	$sum_use_coupon_amount = 0;
	$sum_total_fee_amount = 0;
	$sum_bill_amount = 0;

	$sql_sum_bill_amount = "select sum(bill_amount) "
						." from ejew_bill where shop_id = ? "
						." and (DATE_FORMAT(create_at,'%Y-%m-%d') BETWEEN ? AND ? )";
	$sum_bill_amount = db_query_value($sql_sum_bill_amount,[$shop_id, $start_date, $end_date]);
	
	foreach($data as $bill_item) {
		$sum_is_first_order_amount += $bill_item['is_first_order_amount'];
		$sum_use_coupon_amount += $bill_item['use_coupon_amount'];
		$sum_total_fee_amount += $bill_item['total_fee_amount'];
	}

	$sum_pre_order_amount = $sum_bill_amount - $sum_is_first_order_amount - $sum_use_coupon_amount - $sum_total_fee_amount;
	
	$headers = array (
		"bill_id"=>"账单ID",
		"order_id"=>"订单ID",
		"user_id"=>"用户ID",
		"bill_title"=>"标题",
		"pre_order_amount"=>"提前: " .$sum_pre_order_amount,
		"is_first_order_amount"=>"首单: ".$sum_is_first_order_amount,
		"use_coupon_amount"=>"优惠券: ".$sum_use_coupon_amount,
		"total_fee_amount"=>"微信: ".$sum_total_fee_amount,
		"bill_amount"=>"金额: ".$sum_bill_amount,
		"bill_detail"=>"描述",
		"bill_time"=>"时间"
	);
}

show_admin_header("补贴明细");
?>

<?php if($shop) {?>

<script type="text/javascript">
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}

</script>

<div class="sectionTitle"><div>
补贴明细
</div></div>

<div class="blockDiv">
<?php show_data_table($headers, $data, NULL, NULL,"shop_id")?>
</div>

<?php } else {?>
<div class="centerDiv">
账单信息不存在
</div>
<?php }?>

<?php 
show_admin_footer();
?>