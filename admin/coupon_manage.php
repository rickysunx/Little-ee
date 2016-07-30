<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$keyword = trim(p("keyword"));
$page = get_fixed_page();

$where_sql = "";

$sql_count = "select count(coupon_id) from ejew_coupon where deleted=0 ";

if(is_string_empty($keyword)) {
	$item_count = db_query_value($sql_count);
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_coupon c, ejew_user u where c.user_id=u.user_id order by c.create_at desc ".$sql_limit;
	$data = db_query($sql);
} else {
	$item_count = db_query_value($sql_count);
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_coupon c, ejew_user u where u.user_phone =? and c.user_id=u.user_id order by c.create_at desc ".$sql_limit;
	$data = db_query($sql,array($keyword));
} 

$page_count = get_page_count($item_count);

$headers = array(
	"coupon_id"=>"优惠券ID",
	"user_phone"=>"用户手机",
	"coupon_used"=>"是否使用",
	"create_at"=>"发放日期"
);

$actions = array (
	"Detail"=>"查看"
);

show_admin_header("优惠券查询");
?>

<script type="text/javascript">
function onDetail(id) {
	window.open("user_detail.php?user_id="+id);
}
function onOrder(id) {
	window.open("order.php?field=userId&keyword="+id);
}
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>

<form id="queryForm" method="get" action="coupon_manage.php">
<div class="centerDiv" style="margin:0 auto 10px auto;">
<b>用户查询</b>
手机号
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询" onclick=""/>
<input type="button" value="查看所有优惠券" onclick="window.location.href='coupon_manage.php'"/>
</div>
</form>

<div class="centerDiv">
<?php show_data_table($headers, $data, NULL, NULL,"user_id",$actions)?>
</div>

<?php
show_admin_footer();
?>