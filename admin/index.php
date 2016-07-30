<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

show_admin_header();
?>


<div class="centerDiv">
欢迎使用后台管理系统，请选择对应功能
</div>

<?php 
show_admin_footer();
?>