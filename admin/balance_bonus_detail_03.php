<?php
require '../inc/inc.php';
require 'funcs.php';
require 'combine_frame.php';
check_login();

$shop_id = trim(p("shop_id"));

$start_date = trim(p("start_date"));
$end_date = trim(p("end_date"));
if(is_string_empty($start_date)) {
	$start_date = "2015-09-24";
}
if(is_string_empty($end_date)) {
	$end_date = date("Y-m-d");
}

$sql_bonus_allowance = "select DATE_FORMAT(predicted_time,'%Y-%m-%d') days, "
			." (CASE WHEN count(distinct order_id)<=0 THEN 0 ELSE count(distinct order_id) END) as order_count, "
			." count(distinct user_id) user_count, "
			." count(distinct shop_id) shop_count, "
			." COUNT(CASE WHEN (total_fee+coupon_amount)>=15 THEN 1 ELSE NULL END) AS more_15_count,"
			." sum(is_little_e) little_e_count , "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 00 AND 15 THEN 1 ELSE NULL END) AS lunch_count, "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 15 AND 24 THEN 1 ELSE NULL END) AS dinner_count, "
			." sum(is_first_order) first_order_count, "
			." count(CASE WHEN coupon_amount>0 THEN 1 ELSE NULL END) as use_coupon_count, "
			." count(CASE WHEN DATE_FORMAT(predicted_time,'%Y-%m-%d')>DATE_FORMAT(order_time,'%Y-%m-%d') THEN 1 ELSE NULL END) as pre_order_count, "
			." (CASE WHEN count(distinct order_id)>=5 THEN 5*10 ELSE (IF(count(distinct order_id)=4, 4*10, 0)) END) AS bonus, "
			." (CASE WHEN count(distinct order_id)>=3 THEN 0 ELSE (3-count(distinct order_id))*10 END) AS allowance "					
			." from v_order where shop_id = ? and (DATE_FORMAT(predicted_time,'%Y-%m-%d') BETWEEN ? AND ? ) and order_status in (4,9) group by days "
			." order by days asc ";
$data_bonus_allowance = db_query($sql_bonus_allowance, array($shop_id, $start_date, $end_date));

$data_initial_bonus_allowance = array(array('date'=>$start_date));
for($i=0;$i<27; $i++) {
	$data_initial_bonus_allowance[$i]['date'] =  date('Y-m-d',strtotime("$start_date + $i day"));
}

$bonus_count=0;
$allowance_count=0;
$other_allowance=0;
$all_bonus_allowance_income=0;

$i=0;
foreach($data_bonus_allowance as $item) {
	$bonus_count += $item['bonus'];
	$allowance_count += $item['allowance'];
	$i++;
}

$shop_leave_dates = db_query("select count(distinct leave_date) leave_dates from ejew_shop_status where shop_id = ? and date_format(leave_date, '%Y-%m-%d') between '2015-09-24' and '2015-10-20' ", array($shop_id));

$other_allowance = (27-$i-$shop_leave_dates[0]['leave_dates'])*30;
$all_bonus_allowance_income=$other_allowance+$bonus_count+$allowance_count;

$shop_names = db_query("select shop_name from ejew_shop where shop_id = ? ", array($shop_id));

$headers = array(
	"days"=>"日期",
	"order_count"=>"订单数",
	"user_count"=>"用户数",
	"more_15_count"=>"超过15元",
	"little_e_count"=>"小e配送",
	"lunch_count"=>"午餐",
	"dinner_count"=>"晚餐",
	"first_order_count"=>"首单",
	"use_coupon_count"=>"用优惠券",
	"pre_order_count"=>"提前下单",
	"bonus"=>"奖金: ".$bonus_count,
	"allowance"=>"补贴: ".$allowance_count
);

show_admin_header("订单管理");
?>

<script type="text/javascript">
function on_query(date, item) {
	window.open("combine_query.php?query_date=" + date + "&query_item=" + item);
}

function onOrder(id) {
	window.open("order.php?field=userId&keyword="+id);
}

function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>

<div class="blockDiv">
<b>商户ID: </b>
<?php echo $shop_id?>
<span style="padding:0 20px"></span>
<!-- <b>&nbsp &nbsp &nbsp &nbsp</b> -->
<b>       商户名称: </b>
<?php echo $shop_names[0]['shop_name']?>
<span style="padding:0 20px"></span>
<!-- <b>&nbsp &nbsp &nbsp &nbsp</b> -->
<b>       未营业天数: </b>
<?php echo $shop_leave_dates[0]['leave_dates']?>
<span style="padding:0 20px"></span>
<!-- <b>&nbsp &nbsp &nbsp &nbsp</b> -->
<b>       接不到单补贴: </b>
<?php echo $other_allowance?>
<span style="padding:0 20px"></span>
<!-- <b>&nbsp &nbsp &nbsp &nbsp</b> -->
<b>       奖励补贴合计: </b>
<?php echo $all_bonus_allowance_income?>
</div>


<div class="blockDiv">
<?php show_data_table($headers, $data_bonus_allowance, NULL, NULL,"predictedTime")?>
</div>


<?php 
show_admin_footer();
?>
