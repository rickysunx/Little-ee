<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();



show_admin_header("密码维护");
?>

<script type="text/javascript">
function onSubmit() {
	$.post("actions.php",$("#passwordForm").serialize(),function(data){
		if(data.success) {
			alert("密码修改成功");
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}
</script>

<div class="sectionTitle"><div>
密码修改
</div></div>

<div class="blockDiv">
<form id="passwordForm"/>
<input type="hidden" name="action" value="change_pass"/>
<table>
	<tr>
		<td style="width:80px;">旧密码</td>
		<td><input type="password" name="old_password"/></td>
	</tr>
	<tr>
		<td>新密码</td>
		<td><input type="password" name="new_password"/></td>
	</tr>
	<tr>
		<td>再输一次</td>
		<td><input type="password" name="new_password_2"/></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type="button" value="确定" onclick="onSubmit();"/></td>
	</tr>
</table>
</form>
</div>

<?php 
show_admin_footer();
?>