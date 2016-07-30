<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$user_id = p("user_id");

$user = db_query_row("select * from ejew_user where deleted=0 and user_id=?",array($user_id));

$user_header = array (
	"user_id"=>"用户id",
	"user_phone"=>"手机",
	"nick_name"=>"昵称",
	"reg_time"=>"注册日期",
	"user_avatar"=>"用户头像"
);

$user['user_avatar'] = "<img width='80px' src='".get_image_url($user['user_avatar'])."'/>";

show_admin_header("用户信息");
?>

<?php if($user) {?>

<div class="blockDiv">
用户信息
</div>

<div class="blockDiv">
<?php show_property($user,$user_header)?>
</div>

<?php } else {?>
<div class="centerDiv">
用户信息不存在
</div>
<?php }?>

<?php 
show_admin_footer();
?>