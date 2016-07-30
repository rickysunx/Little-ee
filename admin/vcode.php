<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

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
	$sql = "select * from ejew_vcode where deleted=0 ORDER BY create_at DESC".$sql_limit;
	$data = db_query($sql);
} else {
	$item_count = db_query_value($sql_count,array($keyword));
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_vcode where deleted=0 and ".$where_sql." ORDER BY create_at DESC ".$sql_limit;
	$data = db_query($sql,array($keyword));
}

$page_count = get_page_count($item_count);

$headers = array(
	"phone"=>"手机号",
	"vcode_number"=>"短信验证码"
);

show_admin_header("验证码查询");
?>

<script type="text/javascript">
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>

<form id="queryForm" method="get" action="vcode.php">
<div class="centerDiv" style="margin:0 auto 10px auto;">
<b>验证码查询</b>
手机号码
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询" onclick=""/>
<input type="button" value="查看所有验证码" onclick="window.location.href='vcode.php'"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count, 'vcode_id')?>
</div>

<?php
show_admin_footer();
?>