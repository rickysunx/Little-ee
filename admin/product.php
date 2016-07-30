<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$field = trim(p("field"));
$keyword = trim(p("keyword"));
$page = p("page");
if(strlen($page)==0) $page=1;
$approval_status = "";

if(is_string_empty($field)) {
	$field = "approval_status";
	$keyword = "3";
}

$where_sql = "";

if(is_string_not_empty($keyword) && is_string_not_empty($field)>0) {
	if($field=="shop_id") {
		$where_sql = " s.shop_id=? ";
	} else if($field=="shop_phone") {
		$where_sql = " s.shop_phone=? ";
	} else if($field=="shop_name") {
		$where_sql = " s.shop_name like concat('%',?,'%') ";
	} else if($field=="keeper_name") {
		$where_sql = " s.keeper_name like concat('%',?,'%') ";
	} else if($field=="approval_status") {
		$where_sql = " p.approval_status=? ";
		$approval_status = $keyword;
	} else if($field=="") {
		$where_sql = " ";
	}
}

$sql_raw = " from ejew_product p,ejew_shop s where p.shop_id=s.shop_id ";
$sql_count = "select count(product_id) ".$sql_raw.
(is_string_empty($where_sql)?"":(" and ".$where_sql));

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($keyword));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);
$sql = "select *,p.approval_status product_approval_status".$sql_raw.(is_string_empty($where_sql)?"":" and ".$where_sql)." order by reg_time desc ".$sql_limit;

$data = db_query($sql, is_string_empty($where_sql)?NULL:array($keyword));

$headers = array(
	"product_name"=>"菜品名称",
	"product_image"=>"菜品图片",
	"product_price"=>"菜品价格",
	"product_count"=>"预估库存",
	"product_approval_status"=>"审核状态"
);

$actions = array (
	"Detail"=>"查看"
);

$lovCols = array (
	"product_approval_status"=>"approval_status"
);

show_admin_header("菜品审核");
?>
<script type="text/javascript">
function onDetail(id) {
	window.open("product_detail.php?product_id="+id);
}
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
$(document).ready(function(){
	$("#fieldSelect").change(function() {
		var fieldValue = $("#fieldSelect").val();
		if(fieldValue=="approval_status") {
			$("#keywordInput").hide();
			$("#approvalStatusSelect").show();
			var approvalStatusValue = $("#approvalStatusSelect").val();
			$("#keywordInput").val(approvalStatusValue);
		} else {
			$("#keywordInput").show();
			$("#approvalStatusSelect").hide();
			$("#keywordInput").val("");
		}
	});

	$("#approvalStatusSelect").change(function(){
		var approvalStatusValue = $("#approvalStatusSelect").val();
		$("#keywordInput").val(approvalStatusValue);
	});

	$("#fieldSelect").change();
	<?php if($field=='approval_status') {?>
		$("#approvalStatusSelect").val(<?php echo $approval_status?>);
	<?php } else {?>
		$("#keywordInput").val("<?php echo $keyword?>");
	<?php }?>
});
</script>

<form id="queryForm" method="get" action="product.php">
<div class="blockDiv">
<b>菜品审核</b>
<select id="fieldSelect" name="field">
	<option value="shop_id"<?php output_select("shop_id",$field);?>>商户号</option>
	<option value="shop_phone"<?php output_select("shop_phone",$field);?>>商户手机</option>
	<option value="shop_name"<?php output_select("shop_name",$field);?>>商户名称</option>
	<option value="keeper_name"<?php output_select("keeper_name",$field);?>>厨师姓名</option>
	<option value="approval_status"<?php output_select("approval_status",$field);?>>审核状态</option>
</select>
<input id="keywordInput" type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/>
<select id="approvalStatusSelect" style="display:none;">
	<option value="3">待审核</option>
	<option value="1">审核通过</option>
	<option value="2">审核不通过</option>
	<option value="0">未审核</option>
</select>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询"/>
<input type="button" value="查看待审核菜品" onclick="window.location.href='product.php'"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count,"product_id",$actions,$lovCols)?>
</div>
<?php 
show_admin_footer();
?>