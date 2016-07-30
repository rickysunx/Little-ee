<?php
require '../inc/inc.php';
require 'funcs.php';



function uploadImage() {
	global $image_url,$image_full_url;
	//$shop_id = get_login_shop_id();
	$upload_path = realpath("../upload").DIRECTORY_SEPARATOR;
	if(!isset($_FILES['image_file'])) {
		return makeError(9000, "文件需放在image_file域中");
	}
	$ori_file_name = $_FILES['image_file']['name'];
	$ori_file_info = pathinfo($ori_file_name);
	$ori_file_ext = strtolower($ori_file_info['extension']);
	$errcode = $_FILES['image_file']['error'];
	if($errcode!=0) {
		return makeError(9000, "上传出错，错误码:".$errcode);
	}
	if(!($ori_file_ext=='jpg' || $ori_file_ext=='jpeg')) {
		return makeError(9000, "只能上传jpeg类型的文件");
	}
	$tmp_file = $_FILES['image_file']['tmp_name'];
	$image_file_name = getImageName().".jpg";
	$upload_file = $upload_path.$image_file_name;
	move_uploaded_file($tmp_file, $upload_file);

	return makeSuccess(array(
			"image_name"=>$image_file_name,
			"image_name_url"=>$image_full_url.$image_file_name
	));
}