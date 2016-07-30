<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

show_admin_header("粮票发放");
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

function createCoupon() {
	if(!confirm("确定发放本粮票吗？")) {
		return;
	}
	$.post("actions.php?action=create_coupon",$("#couponForm").serialize(),function(data){
		if(data.success) {
			alert("粮票发放成功：共计发放"+data.updated_count+"人");
			window.location.reload();
		} else {
			alert("出错："+data.errmsg);
		}
	},"json");
}
</script>

<form id="couponForm">

<div class="blockDiv">
<table>

<tr>
	<td>发放用户</td>
	<td><input type="text" name="coupon_user" value="*"/></td>
	<td><font style="color:#f00;">*代表发放所有用户；发放指定用户，请输入用户手机号，以半角逗号分隔</font></td>
</tr>

<tr>
	<td>粮票名称</td>
	<td><input type="text" name="coupon_name"/></td>
	<td></td>
</tr>

<tr>
	<td>粮票描述</td>
	<td><input type="text" name="condition_desc"/></td>
	<td></td>
</tr>

<tr>
	<td>粮票金额</td>
	<td><input type="text" name="coupon_amount"/></td>
	<td></td>
</tr>

<tr>
	<td>粮票数量</td>
	<td><input type="text" name="coupon_number"/></td>
	<td></td>
</tr>

<tr>
	<td>有效期起始</td>
	<td><input id="validation_start" type="text" name="validation_start"/></td>
	<td></td>
</tr>

<tr>
	<td>有效期结束</td>
	<td><input id="validation_end" type="text" name="validation_end"/></td>
	<td></td>
</tr>

<tr>
	<td></td>
	<td><input type="button" value="发放" onclick="createCoupon();"></td>
	<td></td>
</tr>

</table>

</div>
</form>

<?php 
show_admin_footer();
?>