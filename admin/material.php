<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

show_admin_header("物料发放");
?>

<script type="text/javascript">
var monthNames = new Array("1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月");
$(document).ready(function(){
	$("#validation_start").datepicker({
		dateFormat:"yy-mm-dd",changeYear:true,changeMonth:true,
		monthNames:monthNames,monthNamesShort:monthNames,showMonthAfterYear:true
	});
	$("#validation_end").datepicker({
		dateFormat:"yy-mm-dd",changeYear:true,changeMonth:true,
		monthNames:monthNames,monthNamesShort:monthNames,showMonthAfterYear:true
	});
});

function createMaterial() {
	if(!confirm("确定发放本物料吗？")) {
		return;
	}
	$.post("actions.php?action=create_material",$("#materialForm").serialize(),function(data){
		if(data.success) {
			alert("物料发放成功：共计发放"+data.updated_count+"人");
			window.location.reload();
		} else {
			alert("出错："+data.errmsg);
		}
	},"json");
}
</script>

<form id="materialForm">

<div class="blockDiv">
<table>

<tr>
	<td>发放商户</td>
	<td><input type="text" name="material_shop" value="*"/></td>
	<td><font style="color:#f00;">*代表发放所有商户；发放指定商户，请输入商户手机号，以半角逗号分隔</font></td>
</tr>

<tr>
	<td>物料名称</td>
	<td><input type="text" name="material_name"/></td>
	<td></td>
</tr>

<tr>
	<td>物料数量</td>
	<td><input type="text" name="material_number"/></td>
	<td></td>
</tr>

<tr>
	<td></td>
	<td><input type="button" value="发放" onclick="createMaterial();"></td>
	<td></td>
</tr>

</table>

</div>
</form>

<?php 
show_admin_footer();
?>