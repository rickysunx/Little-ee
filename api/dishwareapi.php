<?php

require "../inc/inc.php";
require "../inc/api_common.php";

$data = array(
    'num' => $_POST['num'],
    'name' => $_POST['name'],
    'phone' => $_POST['phone'],
    'address' => $_POST['address']
);
app_log(json_encode($data));
db_save('ejew_disware_list',$data);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <title>支付成功</title>
    <link rel="stylesheet" href="./assets/normalize.css">
    <link rel="stylesheet" href="./assets/choosesuccess.css">
</head>
<body>
<div class="choose_success">
    <div class="choose_middle">
        <div class="choosesuccess_bg">
            <img src="http://www.chio2o.com/images/paysuccess.png" width="38" height="38" alt="">
            <p class="choose_word1">提交成功！</p>
            <p class="choose_word2">经过审核后我们会尽快发货！</p>
        </div>
    </div>
</div>

</body>
</html>