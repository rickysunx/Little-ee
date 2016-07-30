<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$keyword = trim(p("keyword"));
$page = get_fixed_page();

$where_sql = "";
if(is_string_not_empty($where_sql)) {
	$where_sql = " phone_number = ?";
}

$sql_count = "select count(user_id) from ejew_user ". ((strlen($where_sql)>0)?('where '.$where_sql):"");

if(is_string_empty($where_sql)) {
	$item_count = db_query_value($sql_count);
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_user order by reg_time desc ".$sql_limit;
	$data = db_query($sql);
} else {
	$item_count = db_query_value($sql_count,array($keyword));
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_user where ".$where_sql." order by reg_time desc ".$sql_limit;
	$data = db_query($sql,array($keyword));
}

$page_count = get_page_count($item_count);

$headers = array(
	"user_phone"=>"手机号",
	"nick_name"=>"用户昵称",
	"reg_time"=>"注册日期"
);

$actions = array (
	"Detail"=>"查看",
	"Order"=>"订单"
);

show_admin_header("用户查询");
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

<form id="queryForm" method="get" action="user.php">
<div class="centerDiv" style="margin:0 auto 10px auto;">
<b>用户查询</b>
手机号
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询" onclick=""/>
<input type="button" value="查看所有用户" onclick="window.location.href='user.php'"/>
</div>
</form>

<div class="centerDiv">
<?php show_data_table($headers, $data, $page, $page_count,"user_id",$actions)?>
</div>

<?php
show_admin_footer();
?>