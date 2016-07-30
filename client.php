<?php
require "inc/inc.php";

$state = p("state");
$code = p("code");
$local_test = DIRECTORY_SEPARATOR=='\\'?1:0;
$preview = p("preview");
$g_shopid = p("shopid");
if(p("prd_test_123")=="111") {
	$local_test = 1;
}

if($local_test || $preview==1) {
	$accessible = 1;
} else {
	$accessible = p("accessible");
	
	if(is_string_not_empty($state) && is_string_empty($code)) {
		echo '微信未授权';
		exit();
	}
	
	if(is_string_empty($code)) {
		if(is_string_empty($g_shopid)) {
			$url = "http://www.chio2o.com/check_access.php";
		} else {
			$url = "http://www.chio2o.com/check_access.php?shopid=$g_shopid";
		}
		
		$wx_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$wx_appid."&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_userinfo&state=ejew#wechat_redirect";
		header("Location: ".$wx_url);
		exit();
	}
}

if(is_string_empty($accessible)) {
	exit();
}

if(isset($_COOKIE['view_sid'])) {
	$session_id = $_COOKIE['view_sid'];
} else {
	$session_id = gen_session_id();
	setcookie("view_sid",$session_id,null,"/");
}

$item = array(
	"view_ip"=>get_client_ip(),
	"from_id"=>0,
	"create_time"=>get_now(),
	"session_id"=>$session_id
);
db_save("ejew_viewstat",$item);

?>
<!DOCTYPE html>

<html>

<head>
<title>小e管饭</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device_width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=no"/>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=R1tII8wkR3VBSXP4PaHbFUTy"></script>
<!-- script src='http://webapi.amap.com/maps?v=1.3&key=6bc2612f8c4254f6631a39c76850cebb'></script-->
<link rel="stylesheet" href="css/client.css?v=20151113" />
<link rel="stylesheet" href="css/normalize.css" />
<script type="text/javascript">
var first_order_discount = <?php echo $first_order_discount?>;
var pre_order_discount = <?php echo $pre_order_discount?>;
</script>
<script type="text/javascript" src="js/client.js?v=20151113"></script>
<script type="text/javascript">
	wxlogin();

	$(document).ready(function(){
<?php 
	$now_time = substr(get_now(),11,5);
	if($now_time>="21:00") {
		echo "clickDinnerNav(2);";
	} else if($now_time>="15:00") {
		echo "clickDinnerNav(1);";
	} else {
		echo "clickDinnerNav(0);";
	}
	
	if(!is_string_empty($g_shopid)) {
		echo "showShop($g_shopid);";
	}
	
?>
	});
	
</script>
</head>

<body>

<div id="Index" class="Index">
	<div class="AddressBar" onclick="showPickAddress();">
		<?php /*?><img class="Icon" src="images/location.png" /><?php */?>
		<span class="Text">
		<img class="Icon1" src="images/location.png" />
		<span id="AddressText">正在定位...</span>
		<img class="Next1" src="images/next.png" /></span>
		<?php /*?><img class="Next" src="images/next.png" /><?php */?>
	</div>
	
	<div class="BannerContainer">
		<div id="IndexNotice" class="IndexNotice" style="display:none;">
			<span>通知：下暴雨了，送餐可能会慢</span>
			<img onclick="closeIndexNotice();" src="images/notice_close.png"/>
		</div>
		
		<div class="Banner">
			<img id="BannerImage" width="100%" src="images/banner.png"/>
		</div>
	</div>
	
	<div class="DinnerNav">
		<ul id="DinnerNav">
			<li class="Selected"  onclick="clickDinnerNav('0');">今日午餐</li>
			<li onclick="clickDinnerNav('1');">今日晚餐</li>
			<li onclick="clickDinnerNav('2');">明日午餐</li>
			<li onclick="clickDinnerNav('3');">明日晚餐</li>
		</ul>
	</div>
	
	<div id="ProductList" class="ProductList">
	
	
	</div>
	
	<div style="height:50px;"></div>
	
	<div class="FooterNav">
		<div onclick="showIndex();"><img src="images/tab_index.png"/></div>
		<div onclick="showOrder();"><img src="images/tab_order_grey.png"/></div>
		<div onclick="showMine();"><img src="images/tab_my_grey.png"/></div>
	</div>
</div>

<!-- 1024活动开始 -->
<div id="activity_1024" class="activity_1024" style="display:none;">
	<img src="images/active.png"  width="100%" alt="">
	<div class="active_con">
		<span onclick="buyTicket()">支付1元，购买门票</span>
		<h1>活动名称</h1>
		<p>“小e”时代嘉年华——家厨大比拼</p>
		<h1>活动时间</h1>
		<p>2015年10月24</p>
		<p>11：00—15：00</p>
		<h1>活动地点</h1>
		<p>望京国际商业中心中庭广场</p>
		<h1>参与规则</h1>
		<p>1.支付1元即可获得入场券</p>
		<p>2.保存支付报名成功截图</p>
		<p>3.凭截图或短信入场</p>
		<h1>活动介绍</h1>
		<p class="active_detail">邀请八位家庭厨师进行现场厨艺比拼，每个厨师带来自己的拿手好菜。活动由试吃参与者作为评委，选出家庭厨师特等奖1名，7名优秀奖，最后由活动主办方小e管饭发放优胜者奖品。凡活动的参与观众，只需扫码关注小e管饭微信公众号，并支付一元，就可以随便选择和试吃现场所有美食。同时活动还设有游戏和表演以及抽奖的环节。本次活动以宣传为目的，除一元任意吃环节应支付的一元人民币以外，所有活动参与者均不用支付任何其他费用。活动现场提供儿童和成人免费游戏区域，3D、人偶拍拍乐。
		</p>
	</div>
</div>
<!-- 1024活动结束 -->

<!--1024活动支付页  -->
<div id="pay_success" class="pay_success" style="display:none;">
	<div class="paysuccess_bg"><img src="images/paysuccess.png" width="38" height="38" alt=""></div>
	<p class="pay_word1">支付成功！</p>
	<p class="pay_word2">恭喜你，获得入场券！</p>
</div>
<!--1024活动支付页结束  -->
<div id="Order" class="Order" style="display:none;">
	
	<div id="MyOrderList" class="OrderList">

	</div>
	
	<div style="height:50px;"></div>
	
	<div class="FooterNav">
		<div onclick="showIndex();"><img src="images/tab_index_grey.png"/></div>
		<div onclick="showOrder();"><img src="images/tab_order.png"/></div>
		<div onclick="showMine();"><img src="images/tab_my_grey.png"/></div>
	</div>
</div>

<div id="Mine" class="Mine" style="display:none;">
	<div id="LoginDiv" class="Login1">
		<b onclick="showLogin();">马上登录</b>
		<i>新用户登录后下单立减<?php echo $first_order_discount?>元</i>
	</div>
		
	<div id="LoginDivDone" class="Login1" style="display:none;">
		
	</div>
	
	<div class="Section">
		<div class="Item" onclick="showCoupon('Mine');">
			<img class="Icon" src="images/mine_icon_1.png"/>
			<span class="Text1">粮票</span>
			<img class="Next" src="images/next.png"/>
		</div>
		<div class="Item" onclick="showAddressChooser('Mine');">
			<img class="Icon" src="images/mine_icon_2.png"/>
			<span class="Text2">送餐地址</span>
			<img class="Next" src="images/next.png"/>
		</div>
	</div>
	
	<div class="Section">
		<div class="Item" onclick="showFeedback();">
			<img class="Icon" src="images/mine_icon_3.png"/>
			<span class="Text2">用户反馈</span>
			<img class="Next" src="images/next.png"/>
		</div>
		<?php /*?>
		<div class="Item">
			<img class="Icon" src="images/mine_icon_4.png"/>
			<span class="Text2">成为私厨</span>
			<img class="Next" src="images/next.png"/>
		</div>
		<?php */?>
	</div>
	
	<div class="Hotline" onclick="window.location.href='tel:4000682028'">联系客服：400-068-2028</div>

	<div style="height:100px;background-color: #f0f0f0;"></div>
	
	<div class="FooterNav">
		<div onclick="showIndex();"><img src="images/tab_index_grey.png"/></div>
		<div onclick="showOrder();"><img src="images/tab_order_grey.png"/></div>
		<div onclick="showMine();"><img src="images/tab_my.png"/></div>
	</div>
</div>

<div id="PickAddress" class="PickAddress" style="display:none;">
	<div class="Input">
		<div class="InputBorder" onclick="showAddressInput();">
			<img src="images/search.png"/>
			<span>请输入要切换的地址</span>
		</div>
	</div>
	
	<div class="Locator">
		<div onclick="locateCurrentPosition();">
			<img src="images/location2.png" />
			<span>点击定位当前位置</span>
		</div>
	</div>
	
	<div class="AddressList">
		<ul id="AddressHistoryList">
		</ul>
		<div onclick="clearAddressHistory();">清空搜索历史</div>
	</div>
</div>

<div id="AddressInput" class="AddressInput" style="display:none;">
	<div class="Input">
		<div>
			<input id="AddressInputBox" value=""/>
			<img src="images/close.png" onclick="closeAddressInput();"/>
		</div>
	</div>
	
	<div class="AddressList">
		<ul id="AddressSuggestionList">
			
		</ul>
	</div>
</div>

<div id="Shop" class="Shop" style="display:none;">

</div>

<div id="CreateOrder" class="CreateOrder NormalFont" style="display:none;">
	<div id="OrderAddress" class="Address" onclick="showAddressChooser();" style="display:none;">
		<div class="Text">
			<div class="Contact">饭友 135000000</div>
			<div class="Addr">北京市朝阳区驼房营南路梵谷水郡小区21号楼2209室</div>
		</div>
		<div class="Next"><img class="BigNextIcon" src="images/big_next.png"/></div>
	</div>
	
	<div id="NoOrderAddress" class="Address" onclick="showAddressChooser();">
		<div class="Text" style="height:40px;margin-top:20px;">
			<img style="width:11px;" src="images/location2.png"/>
			<span>请添加一个地址，不然阿姨会哭的~</span>
		</div>
		<div class="Next"><img class="BigNextIcon" src="images/big_next.png"/></div>
	</div>
	
	<div class="Section">
		<div class="Item SepBorder">
			<span class="Key">就餐时间</span>
			<img class="BigNextIcon Next" src="images/big_next.png"/>
			<select class="Value" id="OrderTimeSelector">
			</select>
		</div>
		<div class="Item SepBorder" onclick="showOrderMemo();">
			<span class="Key">送餐备注</span>
			<img class="BigNextIcon Next" src="images/big_next.png"/>
			<b id="OrderMemoLabel" class="Value"></b>
		</div>
		<div class="Item">
			<span class="Key">是否自取</span>
			<img id="isTakeawayChecker" onclick="toggleTakeawayChecker();" class="Check" src="images/check_off.png"/>
		</div>
	</div>
	
	<div class="Section">
		<div class="PayItem">
			<img class="WeixinIcon" src="images/weixin.png"/>
			<span class="Key">微信支付</span>
			<span style="color:#5c5959;font-size:14px;margin:-21px 0 0 92px;"></span>
			<img class="YesIcon" src="images/yes.png"/>
		</div>
	</div>
	
	<div class="Section" onclick="showCoupon();">
		<div class="Coupon">
			<span class="Key">粮票</span>
			<span id="CouponTip" class="Tip">1张可用</span>
			<img class="BigNextIcon Next" src="images/big_next.png"/>
			<b id="CouponValue">未使用</b>
		</div>
	</div>
	
	<div id="OrderPaySection" class="PaySection">
	<?php /*
		<div class="Item">
			<span>配送费</span>
			<b>￥0</b>
		</div>
		<div class="Item">
			<span>首单立减</span>
			<b>-￥10</b>
		</div>
		<div class="Seperator"></div>
		<?php for($i=0;$i<3;$i++) {?>
		<div class="Item">
			<span>牛肉拉面 x 0.5</span>
			<b>￥9</b>
		</div>
		<div class="Item">
			<span>白吉馍 x 1</span>
			<b>￥8</b>
		</div>
		<div class="Item">
			<span>土豆牛肉盖饭 x 1</span>
			<b>￥27</b>
		</div>
		<?php }?>
	*/?>
	</div>
	
	<div style="height:80px;"></div>
	
	<div id="OrderConfirmation" class="Confirmation">
		<div class="Button" onclick="createAndPayOrder();">确认支付</div>
		<div class="Amount" id="OrderConfirmationAmount">还需付￥</div>
		<div class="Discount" id="OrderConfirmationDiscount">已优惠0元</div>
	</div>
	
</div>

<div id="AddressEditor" class="AddressEditor" style="display:none;">
<form id="AddressEditorForm">
	<input id="AddressEditorId" name="address_id" value="" type="hidden"/>
	<div class="AddressEditorList">
		<div class="Item">
			<span>联系姓名</span>
			<input id="AddressEditorName" name="contact_name" type="text" value="" placeholder="您的姓名"/>
		</div>
		<div class="Item">
			<span>联系电话</span>
			<input id="AddressEditorPhone" name="phone" type="text" value="" placeholder="配送人员联系您的电话"/>
		</div>
		<div class="Item">
			<span>送餐地址</span>
			<input id="AddressEditorAddress" name="user_address" type="text" value="" placeholder="输入小区、大厦等"/>
		</div>
		<div class="Item">
			<span>门牌号　</span>
			<input id="AddressEditorAddressRow2" name="user_address_row2" type="text" value="" placeholder="输入详细地址"/>
		</div>
	</div>
	
	<div class="OkButton" id="AddressEditorOkButton" onclick="saveAddressEditor();">
		<span>确定</span>
	</div>
	<!--  
	<input id="AddressEditorId" name="address_id" value="" type="hidden"/>
	<ul class="list_con">
		<li class="list_item"><span>联系姓名</span><input type="text" id="AddressEditorName"  name="contact_name"  placeholder="您的姓名"/></li>
		<li class="list_item"><span>联系电话</span><input type="text" id="AddressEditorPhone"  name="phone"  placeholder="配送人员联系您的电话"/></li>
		<li class="list_item location_red" onclick="showLocateMap();">
			<span>送餐地址</span><i></i>
			<input class="location_add" id="AddressEditorAddress" name="user_address" type="text" placeholder="点击进行添加（必填）"/>></li>
		<li class="list_item"><span>门牌号</span><input type="text" id="AddressEditorAddressRow2" name="user_address_row2"  placeholder="输入详细地址"/></li>
	</ul>
	<div class="save_btn" id="AddressEditorOkButton" onclick="saveAddressEditor();">保存</div>
	-->
</form>
</div>
<!-- 百度地图页面 -->
<div id="addressMap" style="display:none;">
        <div class="map_nav">
            <span class="map_nav_icon"></span>
            <input type="text" placeholder="请输入小区、大厦名称" id="suggestId">
        </div>
        <div class="map" id="allmap">
        </div>
        <div id="searchResultPanel" style="display:none;"></div>
        <div class="address_list">
            <ul id="r-result"></ul>
        </div>
</div>
<div id="BMapAutoAddress" style="display:none;">
	<input id="AddressEditorAddress_01" name="user_address_01" type="text" value="" style="width: 100%; padding:10px;"/>

	<div class="AddressList">
		<ul id="AddressSuggestionList_01">
			
		</ul>
	</div>

</div>

<div id="AddressChooser" class="AddressChooser" style="display:none;">
	<div id="AddressAddButton" class="AddButton" onclick="createAddress();">+添加地址</div>
	<div id="AddressChooserList" class="AddressChooserList">
	
	</div>
</div>

<div id="OrderMemo" class="OrderMemo" style="display:none;">
	<div id="OrderMemoTextContainer" class="Memo">
		<textarea id="OrderMemoText" placeholder="给厨房留言你的任性要求吧~" oninput="words_deal();" ></textarea>
	</div>
	
	<div id="OrderMemoTag" class="Tags">
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('不吃辣');">不吃辣</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('微辣');">微辣</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('油少点');">油少点</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('不放蒜');">不放蒜</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('加大菜量');">加大菜量</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('米饭加量');">米饭加量</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('不放葱');">不放葱</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('不放醋');">不放醋</span>
		<span class="OrderMemoSpanEnabled" onclick="insertOrderMemo('不放香菜');">不放香菜</span>
	</div>
	
	<div class="OkButton" onclick="confirmOrderMemo();">
		<span>确定</span>
	</div>
</div>

<div id="SelectCoupon" class="SelectCoupon" style="display:none;">
	<div id="CouponList" class="CouponList">
	</div>
</div>

<div id="Comment" class="Comment" style="display:none;">
	<div id="CommentList" class="CommentList">
	
	</div>
</div>

<div id="OrderStatus" class="OrderStatus" style="display:none;">
	<div class="OrderTab">
		<div id="OrderTabStatus" class="Item Selected">订单状态</div>
		<div id="OrderTabDetail" class="Item">订单详情</div>
		<div class="Seperator"></div>
	</div>
	
	<div class="StatusList">
	</div>
</div>

<div id="Login" class="Login" style="display:none;">
	<div class="Row1">
		<input id="LoginPhone" onkeyup="loginPhoneCheck();" name="phone" class="LoginPhone" type="text" placeholder="请输入手机号"/>
	</div>
	<div class="Row2">
		<input id="LoginVCode" onkeyup="loginVCodeCheck();" name="vcode" class="LoginVCode" type="text" placeholder="请输入验证码"/>
		<span id="LoginGetVCode" class="Time" onclick="getVCode();">获取验证码</span>
	</div>
	
	<div class="OkButton">
		<span id="LoginOkButton" onclick="login();" class="Disabled">登录</span>
	</div>
</div>

<div id="Feedback" class="Feedback" style="display:none;">
	<div class="Item">
		<textarea id="FeedbackAdvice" placeholder="您的反馈让我们变得更好"></textarea>
	</div>
	<div class="Item">
		<input id="FeedbackPhone" placeholder="您的手机号"/>
	</div>
	<div class="OkButton" onclick="saveFeedback();">
		<span>发送</span>
	</div>
	<div style="height:50px;"></div>
</div>

<div id="EditComment" class="EditComment" style="display:none">
	<div class="EC_Title">
		<b></b>
		<span>评价厨师</span>
		<b></b>
	</div>
	<div class="EC_Star">
		<img id="EC_Star_1" onclick="editCommentStarClick(1);" src="images/star_on_big.png"/>
		<img id="EC_Star_2" onclick="editCommentStarClick(2);" src="images/star_on_big.png"/>
		<img id="EC_Star_3" onclick="editCommentStarClick(3);" src="images/star_on_big.png"/>
		<img id="EC_Star_4" onclick="editCommentStarClick(4);" src="images/star_on_big.png"/>
		<img id="EC_Star_5" onclick="editCommentStarClick(5);" src="images/star_on_big.png"/>
	</div>
	<div class="EC_Title2">您的评价让厨师做的更好</div>
	<div class="EC_Editor">
		<textarea id="EditCommentText" placeholder="您的反馈让我们变得更好"></textarea>
	</div>
	<div class="OkButton" style="margin-top:20px;" onclick="saveComment();">
		<span>提交评价</span>
	</div>
</div>

<div id="ShopMap" style="display:none;">
	<div id="ShopMapContainer"></div>
	<div id="ShopMapText" class="ShopMapText"></div>
</div>

<div id="CancelOrder" class="CancelOrder" style="display:none;">
<div class="CancelOrderMask"></div>
<div class="CancelOrderContent">
	<div class="CancelOrderInner">
		<div class="Item Row1" onclick="TimeoutOrderCall();">做个好人，先联系一下厨师协商吧</div>
		<div class="Item Row2" onclick="TimeoutOrderCancel();">协商完毕，忍够了确定取消订单</div>
		<div class="Item Row3" onclick="TimeoutOrderWait();">再忍一会儿</div>
	</div>
</div>
</div>

</body>
</html>
