<?php
require '../inc/inc.php';
require 'funcs.php';
require 'combine_frame.php';
check_login();

$field = trim(p("field"));
$keyword = trim(p("keyword"));
$page = p("page");
if(strlen($page)==0) $page=1;

$where_sql = "";

if(is_string_not_empty($keyword)) {
	$where_sql = " DATE_FORMAT(predicted_time,'%y-%m-%d') = ? ";
}

$sql_raw = " from ejew_order o,ejew_order_b b,ejew_shop s,ejew_user u where o.order_id=b.order_id and o.shop_id=s.shop_id and o.user_id=u.user_id ";
$sql_count = "select count(o.order_id) ".$sql_raw.
	(is_string_empty($where_sql)?" and b.order_status<>0 ":(" and b.order_status<>0 and ".$where_sql));

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($keyword));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);

if(is_string_empty($where_sql)) {
	$item_count = db_query_value($sql_count);
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select DATE_FORMAT(predicted_time,'%Y-%m-%d') days, "
			." count(distinct order_id) order_count, "
			." count(distinct user_id) user_count, "
			." count(distinct shop_id) shop_count, "
			." COUNT(CASE WHEN (total_fee+coupon_amount)>=15 THEN 1 ELSE NULL END) AS more_15_count,"
			." sum(is_little_e) little_e_count , "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 00 AND 15 THEN 1 ELSE NULL END) AS lunch_count, "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 15 AND 24 THEN 1 ELSE NULL END) AS dinner_count, "
			." sum(is_first_order) first_order_count, "
			." count(CASE WHEN coupon_amount>0 THEN 1 ELSE NULL END) as use_coupon_count, "
			." count(CASE WHEN DATE_FORMAT(predicted_time,'%Y-%m-%d')>DATE_FORMAT(order_time,'%Y-%m-%d') THEN 1 ELSE NULL END) as pre_order_count "
			." from v_order where order_status in (4,9) group by days "
			." order by days desc ".$sql_limit;;
} else {
	$item_count = db_query_value($sql_count,array($keyword));
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select "
			." DATE_FORMAT(predicted_time,'%Y-%m-%d') days, "
			." count(distinct order_id) order_count, "
			." count(distinct user_id) user_count, "
			." count(distinct shop_id) shop_count, "
			." COUNT(CASE WHEN (total_fee+coupon_amount)>=15 THEN 1 ELSE NULL END) AS more_15_count,"
			." sum(is_little_e) little_e_count , "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 00 AND 15 THEN 1 ELSE NULL END) AS lunch_count, "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 15 AND 24 THEN 1 ELSE NULL END) AS dinner_count, "
			." sum(is_first_order) first_order_count, "
			." count(CASE WHEN coupon_amount>0 THEN 1 ELSE NULL END) as use_coupon_count, "
			." count(CASE WHEN DATE_FORMAT(predicted_time,'%Y-%m-%d')>DATE_FORMAT(order_time,'%Y-%m-%d') THEN 1 ELSE NULL END) as pre_order_count "
			." from v_order where order_status in (4,9) "
			." and ". $where_sql;
}

 $data = db_query($sql, is_string_empty($where_sql)?NULL:array($keyword));

$headers = array(
	"days"=>"日期",
	"order_count"=>"订单数",
	"user_count"=>"用户数",
	"shop_count"=>"商户数",
	"more_15_count"=>"超过15元",
	"little_e_count"=>"小e配送",
	"lunch_count"=>"午餐",
	"dinner_count"=>"晚餐",
	"first_order_count"=>"首单",
	"use_coupon_count"=>"用粮票",
	"pre_order_count"=>"提前下单"
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


<form id="queryForm" method="get" action="combine.php">
<div class="blockDiv">
<b>每日统计</b>
统计日期
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询"/>
<input type="button" value="查看每日统计" onclick="window.location.href='combine.php'"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count,"predictedTime")?>
</div>


<?php 
show_admin_footer();
?>
