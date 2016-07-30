<?php
require '../inc/inc.php';
require 'funcs.php';


$image_name = null;
$image_name_url = null;
$errmsg = null;

$action = p("action");
$callback = p("callback");

if($action=="upload") {
	uploadImage();
}

function getImageName() {
	return gen_session_id();
}

function uploadImage() {
	global $image_url,$image_full_url,$image_name,$image_name_url,$errmsg;
	$upload_path = realpath("../upload").DIRECTORY_SEPARATOR;
	if(!isset($_FILES['image_file'])) {
		$errmsg = "文件需放在image_file域中";
		return;
	}
	$ori_file_name = $_FILES['image_file']['name'];
	if(!$ori_file_name) {
		$errmsg = "请选择文件";
		return;
	}
	$ori_file_info = pathinfo($ori_file_name);
	$ori_file_ext = strtolower($ori_file_info['extension']);
	$errcode = $_FILES['image_file']['error'];
	if($errcode!=0) {
		$errmsg = "上传出错，错误码:".$errcode;
		return;
	}
	if(!($ori_file_ext=='jpg' || $ori_file_ext=='jpeg')) {
		$errmsg = "只能上传jpeg类型的文件";
		return;
	}
	$tmp_file = $_FILES['image_file']['tmp_name'];
	$image_file_name = getImageName().".jpg";
	$upload_file = $upload_path.$image_file_name;
	move_uploaded_file($tmp_file, $upload_file);

	$image_name = $image_file_name;
	$image_name_url = $image_full_url.$image_file_name;
	
}


?>
<!DOCTYPE html>

<html>

<head>
<title>上传文件</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
body,form {
	padding:0;
	margin:0;
}
</style>
<script type="text/javascript">
<?php 
if($image_name) {
	echo "parent.".$callback."('".$image_name."','".$image_name_url."');";	
}
if($errmsg) {
	echo "alert('".$errmsg."')";
}
?>
</script>
</head>

<body>
<form action="upload_image.php?action=upload&callback=<?php echo $callback?>" enctype="multipart/form-data" method="post">
<input type="file" name="image_file"/>
<input type="submit" value="上传"/>
</form>
</body>

</html>