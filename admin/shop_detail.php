<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$shop_id = p("shop_id");

$shop = db_query_row("select * from ejew_shop where deleted=0 and shop_id=?",array($shop_id));

$shop_header = array(
	"shop_id"=>"商户ID",
	"shop_name"=>"商户名称",
	"city"=>"所在城市",
	"shop_phone"=>"商户手机",
	"keeper_name"=>"厨师姓名",
	"reg_time"=>"注册日期",
	"approval_status"=>"审核状态",
	"operation_status"=>"营业状态",
	"shop_images"=>"商户图片",
	"keeper_avatar"=>"厨师头像"
);

$shop_lovCols = array (
	"approval_status"=>"approval_status",
	"operation_status"=>"operation_status"
);

if($shop) {
	$products = db_query("select * from ejew_product where deleted=0 and shop_id=?",array($shop_id));
	$product_header = array (
		"product_name"=>"菜品名称",
		"product_image"=>"菜品图片",
		"product_price"=>"菜品价格",
		"product_count"=>"预估库存"
	);
	$product_action = array (
		"Detail"=>"查看"
	);
	
	$approval_logs = db_query("select * from ejew_approval_log al,ejew_admin a where a.admin_id=al.admin_id and approval_type=1 and obj_id=?",array($shop_id));
	$approval_log_header = array (
		"admin_name"=>"审核人",
		"approval_time"=>"审核时间",
		"approval_status"=>"审核状态",
		"justification"=>"审核内容"
	);
	$approval_lovCols = array (
		"approval_status"=>"approval_status"
	);
}

show_admin_header("商户信息");
?>

<?php if($shop) {?>

<script type="text/javascript">

function onDetail(id) {
	window.open("product_detail.php?product_id="+id);
}

function approvePass() {
	if(confirm("确定要通过审核？")) {
		$.post("actions.php",{action:'shop_approve',shop_id:<?php echo $shop["shop_id"]?>,approval_status:1},function(data){
			if(data.success) {
				window.location.reload();
			} else {
				alert(data.errmsg);
			}
		},"json");
	}
}

function approveNotPass() {
	$("#approvalDialog").dialog({
		width:420,height:280,title:'输入信息',modal:true,
		buttons:{
			"确定":function(){
				$.post("actions.php",{
						action:'shop_approve',shop_id:<?php echo $shop["shop_id"]?>,
						approval_status:2,justification:$("#approval_justification").val()
					},function(data){
					if(data.success) {
						window.location.reload();
					} else {
						alert(data.errmsg);
					}
				},"json");
			},
			"取消":function(){$(this).dialog("close");}
		}
	});
}

function cancelApproval() {
	if(confirm("确定要取消审核？")) {
		$.post("actions.php",{action:'shop_approve',shop_id:<?php echo $shop["shop_id"]?>,approval_status:3},function(data){
			if(data.success) {
				window.location.reload();
			} else {
				alert(data.errmsg);
			}
		},"json");
	}
}

function setOperationStatus(operation_status) {
	$.post("actions.php",{action:'set_shop_operation_status',shop_id:<?php echo $shop["shop_id"]?>,
			operation_status:operation_status},function(data){
		if(data.success) {
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}
</script>

<div id="approvalDialog" style="display:none;">
	<div style="margin:3px;">请输入审核不通过信息</div>
	<div style="margin:3px;"><textarea rows="5" cols="40" id="approval_justification"></textarea></div>
</div>

<div class="sectionTitle"><div>
商户信息
</div></div>

<div class="blockDiv">
<input type="button" value="审核通过"<?php echo ($shop['approval_status']==3)?" onclick='approvePass(true);'":"disabled"?>/>
<input type="button" value="审核不通过"<?php echo ($shop['approval_status']==3)?" onclick='approveNotPass();'":"disabled"?>/>
<input type="button" value="取消审核"<?php echo ($shop['approval_status']==1||$shop['approval_status']==2)?" onclick='cancelApproval();'":"disabled"?>/>
<input type="button" value="封禁" onclick="setOperationStatus(2);"/>
<input type="button" value="恢复营业" onclick="setOperationStatus(1);"/>
<input type="button" value="暂停营业" onclick="setOperationStatus(0);"/>
</div>

<div class="blockDiv">
<?php show_property($shop,$shop_header,$shop_lovCols)?>
</div>

<div class="sectionTitle"><div>
菜品信息
</div></div>

<div class="blockDiv">
<?php show_data_table($product_header, $products, NULL,NULL,"product_id",$product_action)?>
</div>

<div class="sectionTitle"><div>
审核信息
</div></div>

<div class="blockDiv">
<?php show_data_table($approval_log_header, $approval_logs, NULL,NULL,NULL,NULL,$approval_lovCols)?>
</div>

<?php } else {?>
<div class="centerDiv">
商户信息不存在
</div>
<?php }?>

<?php 
show_admin_footer();
?>