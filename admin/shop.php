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

$where_sql = "";

if(is_string_not_empty($keyword) && is_string_not_empty($field)>0) {
	if($field=="shop_id") {
		$where_sql = " shop_id=? ";
	} else if($field=="shop_phone") {
		$where_sql = " shop_phone=? ";
	} else if($field=="shop_name") {
		$where_sql = " shop_name like concat('%',?,'%') ";
	} else if($field=="keeper_name") {
		$where_sql = " keeper_name like concat('%',?,'%') ";
	} else if($field=="approval_status") {
		$where_sql = " approval_status=? ";
		$approval_status = $keyword;
	} else if($field=="") {
		$where_sql = " ";
	}
}

$sql_raw = " from ejew_shop ";
$sql_count = "select count(shop_id) ".$sql_raw.
(is_string_empty($where_sql)?"":(" where ".$where_sql));

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($keyword));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);
$sql = "select *".$sql_raw.(is_string_empty($where_sql)?"":" where ".$where_sql)." order by reg_time desc ".$sql_limit;

$data = db_query($sql, is_string_empty($where_sql)?NULL:array($keyword));

$headers = array(
	"shop_id"=>"商户ID",
	"shop_name"=>"商户名称",
	"city"=>"所在城市",
	"shop_phone"=>"商户手机",
	"keeper_name"=>"厨师姓名",
	"reg_time"=>"注册日期",
	"approval_status"=>"审核状态",
	"operation_status"=>"营业状态"
);

$actions = array (
		"Detail"=>"查看",
		"Edit"=>"修改",
		"Delete"=>"删除",
		"Product"=>"菜品",
		"ShopStatus"=>"作息"
);

$lovCols = array (
	"approval_status"=>"approval_status",
	"operation_status"=>"operation_status"
);

show_admin_header("商户查询");
?>
<script type="text/javascript">

var this_month_prefix = null;
var next_month_prefix = null;

function onDetail(id) {
	window.open("shop_detail.php?shop_id="+id);
}

function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}

function onEdit(id) {
	window.open("shop_edit.php?shop_id="+id);
}

function onProduct(id) {
	window.open("product_manager.php?shop_id="+id);
}

function getXX(n) {
	return (n<10)?("0"+n):(""+n);
}

function onShopStatus(id) {
	$.post("shop_status.php",{shop_id:id},function(data){
		$("#ShopStatusDiv").html(data);
		$("#ShopStatusDiv").dialog({
			title:"修改订单状态",modal:true,width:500,
			buttons:{
				"确定":function(){
					var leave_dates = new Array();
					$("#thisMonth .admin_calendar_cell_selected").text(function(n,text){
						leave_dates.push(this_month_prefix+getXX(text));
					});
					$("#nextMonth .admin_calendar_cell_selected").text(function(n,text){
						leave_dates.push(next_month_prefix+getXX(text));
					});
					var leave_dates_string = JSON.stringify(leave_dates);
					$.post("actions.php?action=set_shop_status",{shop_id:id,leave_dates:leave_dates_string},function(data){
						if(data.success) {
							$("#ShopStatusDiv").dialog("close");
						} else {
							alert(data.errmsg);
						}
					},"json");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			}
		});
	},"text");
}

function onDelete(id) {
	if(confirm("确定要删除该商户吗？")) {
		$.post("actions.php?action=delete_shop",{"shop_id":id},function(data){
			if(data.success) {
				window.location.reload();
			} else {
				alert(data.errmsg);
			}
		},"json");
	}
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


<div id="ShopStatusDiv">
	
</div>



<form id="queryForm" method="get" action="shop.php">
<div class="blockDiv">
<b>商户查询</b>
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
<input type="button" value="查看所有商户" onclick="window.location.href='shop.php'"/>
<input type="button" value="添加商户" onclick="window.location.href='shop_edit.php'"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count,"shop_id",$actions,$lovCols)?>
</div>
<?php 
show_admin_footer();
?>