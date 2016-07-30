<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$keyword = trim(p("keyword"));
$page = get_fixed_page();

$where_sql = "";
if(is_string_not_empty($keyword)) {
	$where_sql = " admin_name like  concat('%',?,'%')";
}

$sql_count = "select count(admin_id) from ejew_admin ". ((strlen($where_sql)>0)?('where '.$where_sql):"");

if(is_string_empty($where_sql)) {
	$item_count = db_query_value($sql_count);
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_admin where deleted=0 ORDER BY is_super DESC,admin_status DESC".$sql_limit;
	$data = db_query($sql);
} else {
	$item_count = db_query_value($sql_count,array($keyword));
	$sql_limit = get_sql_limit($page, $item_count);
	$sql = "select * from ejew_admin where  deleted=0 and ".$where_sql." ORDER BY is_super DESC,admin_status DESC ".$sql_limit;
	$data = db_query($sql,array($keyword));
}

$page_count = get_page_count($item_count);

$headers = array(
	"admin_name"=>"管理员用户名",
	"is_super"=>"是否超级管理员",
	"admin_status"=>"是否启用"
);

$actions = array (
	"Edit"=>"编辑",
);
$lovCols = array (
	"is_super"=>"is_super",
	"admin_status"=>"admin_status"
);
show_admin_header("管理员维护");
?>

<script type="text/javascript">
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>

<form id="queryForm" method="get" action="admin.php">
<div class="centerDiv" style="margin:0 auto 10px auto;">
<b>管理员维护</b>
管理员名字
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询" onclick=""/>
<input type="button" value="查看所有用户" onclick="window.location.href='admin.php'"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count,"admin_id",$actions,$lovCols,$item_count)?>
</div>

<?php
show_admin_footer();
?>