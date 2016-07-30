<?php
require '../inc/inc.php';
require 'funcs.php';
?>
<!DOCTYPE html>

<html>

<head>
<title>登陆</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="js/jquery.js"></script>
<link rel="stylesheet" href="css/admin.css" />
<script type="text/javascript">
var redirectURL = "<?php echo p("redirect_url")?>";
function onSubmit() {
	if($("#userName").val()=="") {
		alert("用户名不能为空");
		return;
	}

	if($("#userPass").val()=="") {
		alert("密码不能为空");
		return;
	}
	
	$.post("login_check.php",$("#loginForm").serialize(),function(data){
		if(data.success) {
			$("#errorInfo").text("登陆成功，正在跳转");
			window.location.href = redirectURL;
		} else {
			$("#errorInfo").text(data.error);
		}
	},"json");
}


</script>
</head>

<body>
<form id="loginForm" onsubmit="onSubmit();return false;">
<div class="loginDiv">
	<table border=0 class="loginTable">
		<tr>
			<td colspan="2" style="text-align: center; background-color: #eee;"><b>管理员登陆</b></td>
		</tr>
	
		<tr>
			<td style="width:60px;text-align: right;">用户名</td>
			<td style="width:290px;"><input id="userName" type="text" name="userName" /></td>
		</tr>
		
		<tr>
			<td style="text-align: right;">密码</td>
			<td><input id="userPass" type="password" name="userPass" /></td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" value="登陆"/><span id="errorInfo" style="color:red;margin-left:5px;"></span></td>
		</tr>
	</table>
</div>
</form>
</body>

</html>