<?php
require "../inc/inc.php";
require "../inc/api_common.php";

function get_login_shop_id() {
    global $session_timeout;
    $sid = trim(p("sid"));
    if(is_string_empty($sid)) {
        output_result(makeError(9000, "sid不能为空"));
        exit();
    }

    $now = time();

    $sql = "delete from ejew_session where update_time<?";
    db_update($sql,[$now-$session_timeout]);

    $sql = "select * from ejew_session where session_id=? and session_type=2";
    $session = db_query_row($sql,[$sid]);

    if($session) {
        db_update("update ejew_session set update_time=? where session_id=?",[$now,$sid]);
        return $session['user_id'];
    } else {
        output_result(makeError(9000, "无效的sid"));
        exit();
    }
}

$shop_id = get_login_shop_id();
if(!$shop_id){
    die('no shop id');
}

$sql = "select shop_id,shop_phone,keeper_name,shop_address,shop_address_row2 from ejew_shop where shop_id=".$shop_id;
$result = db_query_row($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width" />
    <meta name="format-detection" content="telephone=no"/>
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <title>我要餐具</title>
    <link rel="stylesheet" href="./assets/normalize.css">
    <link rel="stylesheet" href="./assets/cooktool.css">
    <script src="../js/jquery.js"></script>
</head>
<body>
<div class="cooktool">
    <div class="cooktool_con">
        <h1>请选择所需餐具数量</h1>
        <div class="tool_number">
            <span class="tool" value="50">50套餐具</span>
            <span class="tool" value="100">100套餐具</span>
            <span class="tool" value="150">150套餐具</span>
        </div>
        <p>＊注：50套餐具包括长盒50个、小圓盒100个、筷子50套、塑料袋1捆（约100个）、腰封1捆（约100条）、贴纸50张</p>
    </div>
</div>
<div class="greenbar"></div>
<div class="cook_inf">
    <form action="/api/dishwareapi.php" method="post" id="dishware-form">
        <input type="hidden" value="" name="num" class="info" id="tool-num">
        <ul class="list_con">
            <li class="list_item"><span>收件人</span><input type="text" id="name" class="info" name="name" value="<?php echo $result['keeper_name']; ?>"/></li>
            <li class="list_item"><span>收件电话</span><input type="text" id="phone" class="info" name="phone" value="<?php echo $result['shop_phone']; ?>"/></li>
            <li class="list_item"><span>收件地址</span><input type="text" id="address" class="info" name="address" value="<?php echo $result['shop_address'].$result['shop_address_row2']; ?>"/></li>
        </ul>
        <h2>* 温馨提示：</h2>
        <p>1、17:00点前申请，次日发放；17:00后申请，第三日发放</p>
        <p>2、我们会通过圆通快递发货</p>
        <input type="submit" value="提交" class="save_btn" disabled="disabled" style="background-color: #C0C0C0;"></input>
    </form>
</div>

<script>
    $(".tool").click(function(){
        var toolNum = $(this).attr("value");
        $("#tool-num").val(toolNum);
        $(this).siblings().removeClass('choosetool').css('color','#fe696d');
        $(this).addClass('choosetool').css('color','#fff');
        $('.save_btn').attr("style","");
        $('.save_btn').removeAttr("disabled");
    })

    $("#dishware-form").submit(function(e){
        var toolNum = $("#tool-num").val();
        var name = $('#name').val();
        var phone = $('#phone').val();
        var address = $('#address').val();
        if(!(toolNum && name && phone && address)){
            $('.save_btn').css("background-color","#C0C0C0");
            $('.save_btn').attr("disabled","disabled");
            e.preventDefault();
        }
    })

    $(".info").click(function () {
        $(".info").each(function(){
            if(!$(this).val()){
                $('.save_btn').css("background-color","#C0C0C0");
                $('.save_btn').attr("disabled","disabled");
                return false;
            }
            $('.save_btn').attr("style","");
            $('.save_btn').removeAttr("disabled");
        });
    })
</script>
</body>
</html>