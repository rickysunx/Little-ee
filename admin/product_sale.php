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

$keyword = trim(p("keyword"));
$page = get_fixed_page();

$where_sql = "";
if(is_string_not_empty($keyword)) {
	$where_sql = " phone = ?";
}

$sql_count = "select count(vcode_id) from ejew_vcode ". ((strlen($where_sql)>0)?('where '.$where_sql):"");

if(is_string_empty($where_sql)) {
	$item_count = db_query_value($sql_count);
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select *, o.product_id product_id, s.shop_name shop_name, p.product_name product_name, p.product_price product_price, sum(o.product_count) total_sales "
			." from ejew_order_product o, ejew_product p, ejew_shop s, v_order v "
			." where o.product_id=p.product_id and p.shop_id=s.shop_id and o.order_id=v.order_id "
			." and v.order_status in (4,9) "
			." and (DATE_FORMAT(v.predicted_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." group by o.product_id "
			." order by shop_name, total_sales desc;";
	$data = db_query($sql,array($start_date, $end_date));
} else {
	$item_count = db_query_value($sql_count,array($keyword));
	$sql_limit = get_sql_limit($page, $item_count);
/* 	$sql = "select o.product_id product_id, s.shop_name shop_name, p.product_name product_name, p.product_price product_price, count(o.product_count) total_sales "
			." from ejew_order_product o, ejew_product p, ejew_shop s, v_order v "
			." where o.product_id = ?"
			." and o.product_id=p.product_id and p.shop_id=s.shop_id and o.order_id=v.order_id "
			." and v.order_status in (4,9) "
			." and (DATE_FORMAT(v.predicted_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." group by o.product_id "
			." order by total_sales desc;";
 */
 	$sql = "select *, o.product_id product_id, s.shop_name shop_name, p.product_name product_name, p.product_price product_price, sum(o.product_count) total_sales "
			." from ejew_order_product o, ejew_product p, ejew_shop s, v_order v "
			." where o.product_id = ?"
			." and o.product_id=p.product_id and p.shop_id=s.shop_id and o.order_id=v.order_id "
			." and v.order_status in (4,9) "
			." group by o.product_id "
			." order by shop_name, total_sales desc;";
 	$data = db_query($sql, array($keyword, $start_date, $end_date));
}

$page_count = get_page_count($item_count);

$headers = array(
	"product_id"=>"菜品ID",
	"shop_name"=>"厨房名称",
	"shop_address"=>"厨房地址",
	"product_name"=>"菜品名称",
	"product_price"=>"菜品价格",
	"total_sales"=>"菜品销量"
);

show_admin_header("菜品销量查询");
?>

<script type="text/javascript">
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>

<form id="queryForm" method="get" action="product_sale.php">
<div class="centerDiv" style="margin:0 auto 10px auto;">
<b>菜品销量查询</b>
菜品ID
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
开始日期
<input type="text" name="start_date" style="width:150px;" value="<?php echo $start_date?>"/>
截止日期
<input type="text" name="end_date" style="width:150px;" value="<?php echo $end_date?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询" onclick=""/>
<input type="button" value="查看所有菜品销量" onclick="window.location.href='product_sale.php'"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, NULL, NULL, 'vcode_id')?>
</div>

<?php
show_admin_footer();
?>
