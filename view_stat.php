<?php
require "inc/inc.php";

$id = p("id");

if(isset($_COOKIE['view_sid'])) {
	$session_id = $_COOKIE['view_sid'];
} else {
	$session_id = gen_session_id();
	setcookie("view_sid",$session_id,null,"/");
}

$item = array(
	"view_ip"=>get_client_ip(),
	"from_id"=>$id,
	"create_time"=>get_now(),
	"session_id"=>$session_id
);
db_save("ejew_viewstat",$item);
?>

<!DOCTYPE html>

<html>

<head>
<title>e家e味</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device_width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=no"/>
<script type="text/javascript">
	window.location.href='weixin://addfriend/chiejiaewei';
</script>
</head>

<body>

</body>
</html>

