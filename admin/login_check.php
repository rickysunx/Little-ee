<?php
require '../inc/inc.php';
require 'funcs.php';

$result = array();


$users = db_query("select * from ejew_admin where admin_name=?",[p("userName")]);


$result['success'] = false;

if(count($users)==0) {
	$result['error'] = '用户不存在';
} else if(count($users)==1) {
	$user = $users[0];
	if($user['admin_pass']==md5(p("userPass"))) {
		$result['success'] = true;
		set_admin_user(array('admin_id'=>$user['admin_id'],'admin_name'=>$user['admin_name']));
	} else {
		$result['error'] = "密码错误";
	}
} else {
	$result['error'] = '管理员信息不唯一';
}

echo json_encode($result);

