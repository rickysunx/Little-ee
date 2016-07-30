<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$field = trim(p("field"));
$query_date = trim(p("query_date"));
$query_item = trim(p("query_item"));
$page = p("page");
if(strlen($page)==0) $page=1;

$where_sql = "";

$sql_count = "select count(distinct b.shop_id) shop_bill_count from ejew_bill b ";

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($query_date));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);

if(is_string_empty($query_date)) {
	$query_date = date("Y-m-d");
}
if(is_string_empty($query_item)) {
	$query_item = "";
}

/* $tmp="2015-09-19";
$sql_raw = " from ejew_order o,ejew_order_b b,ejew_shop s,ejew_user u where (DATE_FORMAT(o.predicted_time,'%Y-%m-%d') = ".$tmp. ") o.order_id=b.order_id and o.shop_id=s.shop_id and o.user_id=u.user_id ";
 */
$sql_raw = " from ejew_order o,ejew_order_b b,ejew_shop s,ejew_user u where o.order_id=b.order_id and o.shop_id=s.shop_id and o.user_id=u.user_id ";
$sql_count = "select count(o.order_id) ".$sql_raw.
	(is_string_empty($where_sql)?" and b.order_status<>0 ":(" and b.order_status<>0 and ".$where_sql));

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($keyword));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);

if($query_item=="more_15_count") {
	$where_sql = " o.order_id=?";
} else if($query_item=="little_e_count") {
	$sql = "select * ".$sql_raw." and b.order_status in (4, 9) "." and o.is_little_e = 1"." and (DATE_FORMAT(o.predicted_time,'%Y-%m-%d') = ? ) "
			." order by order_time desc ".$sql_limit;
} else if($query_item=="lunch_count") {
	$where_sql = " o.shop_id in (select shop_id from ejew_shop where shop_name like concat('%',?,'%'))";
} else if($query_item=="dinner_count") {
	$where_sql = " o.shop_id in (select shop_id from ejew_shop where shop_phone=? )";
} else if($query_item=="first_order_count") {
	$sql = "select * ".$sql_raw." and b.order_status in (4, 9) "." and b.is_first_order = 1"." and (DATE_FORMAT(o.predicted_time,'%Y-%m-%d') = ? ) "
			." order by order_time desc ".$sql_limit;
} else if($query_item=="use_coupon_count") {
	$where_sql = " o.shop_id = ? ";
}  else if($query_item=="pre_order_count") {
	$where_sql = " b.order_status  = ? ";
} else {
	$sql = "select * ".$sql_raw." and b.order_status in (4, 9) ".(is_string_empty($where_sql)?" ":" and ".$where_sql)." order by order_time desc ".$sql_limit;
}

$data = db_query($sql, array($query_date));

$headers = array(
	"order_id"=>"订单号",
	"user_phone"=>"用户",
	"shop_phone"=>"商户",
	"total_fee"=>"合计金额",
	"order_status"=>"订单状态",
	"pay_status"=>"支付状态",
	"order_time"=>"下单时间",
	"predicted_time"=>"送餐时间"
);

$actions = array (
	"Detail"=>"查看",
);

$lovCols = array (
	"order_status"=>"order_status",
	"pay_status"=>"pay_status"
);

show_admin_header("统计详情");
?>

<script type="text/javascript">
function onDetail(id) {
	window.open("order_detail.php?order_id="+id);
}

function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>


<form id="queryForm" method="get" action="combine_query.php">
<div class="blockDiv">
送餐日期
<input type="text" name="query_date" style="width:150px;" value="<?php echo $query_date?>"/>
过滤条件
<select name="query_item">
	<option value="orderId"<?php output_select("orderId",$query_item);?>>超过15元</option>
	<option value="little_e_count"<?php output_select("little_e_count",$query_item);?>>小e配送</option>
	<option value="userPhone"<?php output_select("userPhone",$query_item);?>>午餐</option>
	<option value="shopName"<?php output_select("shopName",$query_item);?>>晚餐</option>
	<option value="first_order_count"<?php output_select("first_order_count",$query_item);?>>首单</option>
	<option value="orderStatus"<?php output_select("orderStatus",$field);?>>用粮票</option>
	<option value="payStatus"<?php output_select("payStatus",$field);?>>提前下单</option>
</select>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count,"order_id",$actions,$lovCols)?>
</div>


<?php 
show_admin_footer();
?>
