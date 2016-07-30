<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$product_id = p("product_id");

$product = db_query_row("select * from ejew_product where deleted=0 and product_id=?",array($product_id));

$product_header = array(
	"product_name"=>"菜品名称",
	"product_image"=>"菜品图片",
	"product_price"=>"菜品价格",
	"product_count"=>"预估库存",
	"approval_status"=>"审核状态"
);

$product_lovCols = array (
	"approval_status"=>"approval_status",
	"operation_status"=>"operation_status"
);


$approval_logs = db_query("select * from ejew_approval_log al,ejew_admin a where a.admin_id=al.admin_id and approval_type=2 and obj_id=?",array($product_id));
$approval_log_header = array (
		"admin_name"=>"审核人",
		"approval_time"=>"审核时间",
		"approval_status"=>"审核状态",
		"justification"=>"审核内容"
);
$approval_lovCols = array (
		"approval_status"=>"approval_status"
);

show_admin_header("菜品信息");
?>

<?php if($product) {?>

<script type="text/javascript">

function onDetail(id) {
	window.open("product_detail.php?product_id="+id);
}

function approvePass() {
	if(confirm("确定要通过审核？")) {
		$.post("actions.php",{action:'product_approve',product_id:<?php echo $product["product_id"]?>,approval_status:1},function(data){
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
						action:'product_approve',product_id:<?php echo $product["product_id"]?>,
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
		$.post("actions.php",{action:'product_approve',product_id:<?php echo $product["product_id"]?>,approval_status:3},function(data){
			if(data.success) {
				window.location.reload();
			} else {
				alert(data.errmsg);
			}
		},"json");
	}
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
<input type="button" value="审核通过"<?php echo ($product['approval_status']==3)?" onclick='approvePass(true);'":"disabled"?>/>
<input type="button" value="审核不通过"<?php echo ($product['approval_status']==3)?" onclick='approveNotPass();'":"disabled"?>/>
<input type="button" value="取消审核"<?php echo ($product['approval_status']==1||$product['approval_status']==2)?" onclick='cancelApproval();'":"disabled"?>/>
</div>

<div class="blockDiv">
<?php show_property($product,$product_header,$product_lovCols)?>
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
?><?php
