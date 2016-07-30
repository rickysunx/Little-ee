<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$shop_id = p("shop_id");
$shop = null;
if($shop_id) {
	$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);
	if(!$shop) {
		echo '厨房信息不存在';
		exit();
	}
}

function get_shop($key) {
	global $shop;
	if(isset($shop[$key])) {
		return $shop[$key];
	} else {
		return "";
	}
}

function echo_shop_time($key) {
	$time = get_shop($key);
	if(is_string_not_empty($time)) {
		echo "$('#".$key."').val('".$time."');\r\n";
	}
}

show_admin_header("厨房信息");
?>

<script type="text/javascript">

var imageIndex = 0;

function deleteImage(image_id) {
	$("#"+image_id).remove();
}

function addShopImage(image_name,image_name_url) {
	imageIndex++;
	$("#shopImageDiv").append(
		"<div id='shopImage_"+imageIndex+"' class='imageUpload'>"+
		"<input name='shop_images[]' type='hidden' value='"+image_name+"'/>"+
		"<img src='"+ image_name_url+"'/>"+
		"<button onclick='deleteImage(\"shopImage_"+imageIndex+"\");'>删除</button></div>"
	);
}

function addCoverProductImage(image_name,image_name_url) {
	$("#coverProductImageDiv").html(
		"<div id='coverProductImage' class='imageUpload'>"+
		"<input name='cover_product_image' type='hidden' value='"+image_name+"'/>"+
		"<img src='"+ image_name_url+"'/>"+
		"<button onclick='deleteImage(\"coverProductImage\");'>删除</button></div>"
	);
}

function addKeeperAvatar(image_name,image_name_url) {
	$("#keeperAvatarImageDiv").html(
		"<div id='keeperAvatarImage' class='imageUpload'>"+
		"<input name='keeper_avatar' type='hidden' value='"+image_name+"'/>"+
		"<img src='"+ image_name_url+"'/>"+
		"<button onclick='deleteImage(\"keeperAvatarImage\");'>删除</button></div>"
	);
}

function submitShopForm() {
	$.post("actions.php?action=save_shop",$("#shopForm").serialize(),function(data){
		if(data.success) {
			$("#saveDialog").dialog({
				dialogClass: "no-close",
				title:"保存成功",
				buttons:[
					{text:"添加下一个厨房",click:function(){
						window.location.href="shop_edit.php";
						$(this).dialog("close");
					}},
					{text:"进入厨房列表",click:function(){
						window.location.href="shop.php";
						$(this).dialog("close");
					}}
				]
			});
		} else {
			alert(data.errmsg);
		}
	},"json");
}

var mapAutocomplete = null;
var city = "北京市";

$(document).ready(function(){
	
	mapAutocomplete = new BMap.Autocomplete({
		"input":"shop_address",
		"location":city
	});

	mapAutocomplete.addEventListener("onconfirm",function(e){
		var _value = e.item.value;
		myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
		addressEditorPosition = null;
		var local = new BMap.LocalSearch(city, {
			onSearchComplete: function(){
				var pt = local.getResults().getPoi(0).point;
				$("#shop_lng").val(pt.lng);
				$("#shop_lat").val(pt.lat);
			}
		});
		local.search(myValue);
	});

<?php

echo_shop_time("lunch_time_start");
echo_shop_time("lunch_time_end");
echo_shop_time("dinner_time_start");
echo_shop_time("dinner_time_end");
echo_shop_time("lunch_stop_time");
echo_shop_time("dinner_stop_time");
	
$shop_images = get_shop("shop_images");
if(is_string_not_empty($shop_images)) {
	$shop_image_array = json_decode($shop_images);
	if(is_array($shop_image_array)) {
		foreach ($shop_image_array as $shop_image) {
			echo "addShopImage('".$shop_image."','".$image_full_url.$shop_image."');\r\n";
		}
	}
}

$keeper_avatar = get_shop("keeper_avatar");
if(is_string_not_empty($keeper_avatar)) {
	echo "addKeeperAvatar('".$keeper_avatar."','".$image_full_url.$keeper_avatar."');\r\n";
}

$cover_product_image = get_shop("cover_product_image");
if(is_string_not_empty($cover_product_image)) {
	echo "addCoverProductImage('".$cover_product_image."','".$image_full_url.$cover_product_image."');\r\n";
}

$shop_address = get_shop("shop_address");
if(is_string_not_empty($shop_address)) {
	echo "mapAutocomplete.setInputValue('".$shop_address."');";
}

//echo "$('#shop_address').val(\"".get_shop("shop_address")."\");\r\n";
?>
	
});
</script>

<div id="saveDialog" style="display:none">
保存成功，请选择您要进行的操作
</div>

<div class="sectionTitle"><div>
编辑厨房信息
</div></div>

<form id="shopForm" action="action.php?action=save_shop" method="post">
<?php if($shop_id) {?>
<input name="shop_id" type="hidden" value="<?php echo $shop_id?>"/>
<?php } else {?>
<input name="shop_id" type="hidden"/>
<?php }?>
<input id="shop_lng" name="shop_lng" type="hidden" value="<?php echo get_shop('shop_lng')?>"/>
<input id="shop_lat" name="shop_lat" type="hidden" value="<?php echo get_shop('shop_lat')?>"/>
<input name="city" type="hidden" value="北京市"/>
<div class="blockDiv">
<table>

<tr>
	<td>注册手机：</td>
	<td><input name="shop_phone" type="text" value="<?php echo get_shop('shop_phone')?>"/></td>
</tr>

<tr>
	<td>厨房名字：</td>
	<td><input name="shop_name" type="text" value="<?php echo get_shop('shop_name')?>"/></td>
</tr>

<tr>
	<td>联系电话：</td>
	<td><input name="contact_phone" type="text" value="<?php echo get_shop('contact_phone')?>"/></td>
</tr>

<tr>
	<td>配送范围：</td>
	<td><input name="delivery_distance" type="text" value="<?php echo get_shop('delivery_distance')?>"/></td>
</tr>

<tr>
	<td>家庭住址：</td>
	<td><input id="shop_address" name="shop_address" type="text" value="<?php echo get_shop('shop_address')?>"/></td>
</tr>

<tr>
	<td>详细地址：</td>
	<td><input name="shop_address_row2" type="text" value="<?php echo get_shop('shop_address_row2')?>"/></td>
</tr>

<tr>
	<td>主打菜系：</td>
	<td><input name="cooking_style" type="text" value="<?php echo get_shop('cooking_style')?>"/></td>
</tr>

<tr>
	<td>是否支持自取：</td>
	<td>
		<input name="can_takeaway" type="radio" value="1" <?php echo get_shop('can_takeaway')===1?'checked':''?>/>是
		<input name="can_takeaway" type="radio" value="0" <?php echo get_shop('can_takeaway')===0?'checked':''?>/>否
	</td>
</tr>

<tr>
	<td>是否支持配送：</td>
	<td>
		<input name="can_delivery" type="radio" value="1" <?php echo get_shop('can_delivery')===1?'checked':''?>/>是
		<input name="can_delivery" type="radio" value="0" <?php echo get_shop('can_delivery')===0?'checked':''?>/>否
	</td>
</tr>

<tr>
	<td>可做午餐：</td>
	<td>
		<input name="has_lunch" type="radio" value="1" <?php echo get_shop('has_lunch')===1?'checked':''?>/>是
		<input name="has_lunch" type="radio" value="0" <?php echo get_shop('has_lunch')===0?'checked':''?>/>否
	</td>
</tr>

<tr>
	<td>可做晚餐：</td>
	<td>
		<input name="has_dinner" type="radio" value="1" <?php echo get_shop('has_dinner')===1?'checked':''?>/>是
		<input name="has_dinner" type="radio" value="0" <?php echo get_shop('has_dinner')===0?'checked':''?>/>否
	</td>
</tr>

<tr>
	<td>午餐营业时间：</td>
	<td>
		<select id="lunch_time_start" name="lunch_time_start">
		<?php echo show_time_options()?>
		</select>
		--
		<select id="lunch_time_end" name="lunch_time_end">
		<?php echo show_time_options()?>
		</select>
	</td>
</tr>

<tr>
	<td>晚餐营业时间：</td>
	<td>
		<select id="dinner_time_start" name="dinner_time_start">
		<?php echo show_time_options()?>
		</select>
		--
		<select id="dinner_time_end" name="dinner_time_end">
		<?php echo show_time_options()?>
		</select>
	</td>
</tr>

<tr>
	<td>午餐停止接单时间：</td>
	<td>
		<select id="lunch_stop_time" name="lunch_stop_time">
		<?php echo show_time_options()?>
		</select>
	</td>
</tr>

<tr>
	<td>晚餐停止接单时间：</td>
	<td>
		<select id="dinner_stop_time" name="dinner_stop_time">
		<?php echo show_time_options()?>
		</select>
	</td>
</tr>

<tr>
	<td>添加图片：</td>
	<td>
		<div id="shopImageDiv"></div>
		<iframe src="upload_image.php?callback=addShopImage" frameBorder=0 scrolling="no" 
			style="margin-top:10px;border:1px solid #999;width:300px;height:25px;overflow:hidden;"></iframe>
	</td>
</tr>

<tr>
	<td>厨师头像：</td>
	<td>
		<div id="keeperAvatarImageDiv"></div>
		<iframe src="upload_image.php?callback=addKeeperAvatar" frameBorder=0 scrolling="no" 
			style="margin-top:10px;border:1px solid #999;width:300px;height:25px;overflow:hidden;"></iframe></td>
</tr>

<tr>
	<td>厨房首页图：</td>
	<td>
		<div id="coverProductImageDiv"></div>
		<iframe src="upload_image.php?callback=addCoverProductImage" frameBorder=0 scrolling="no" 
			style="margin-top:10px;border:1px solid #999;width:300px;height:25px;overflow:hidden;"></iframe></td>
</tr>

<tr>
	<td>厨师名字：</td>
	<td><input name="keeper_name" type="text" value="<?php echo get_shop('keeper_name')?>"/></td>
</tr>

<tr>
	<td>厨师家乡：</td>
	<td><input name="keeper_hometown" type="text" value="<?php echo get_shop('keeper_hometown')?>"/></td>
</tr>

<tr>
	<td>厨师身份证：</td>
	<td><input name="keeper_id_number" type="text" value="<?php echo get_shop('keeper_id_number')?>"/></td>
</tr>

<tr>
	<td>厨师简介：</td>
	<td><textarea name="keeper_intro" style="width:300px;height:100px;"><?php echo get_shop('keeper_intro')?></textarea></td>
</tr>

<tr>
	<td>银行卡姓名：</td>
	<td><input name="bank_account_name" type="text" value="<?php echo get_shop('bank_account_name')?>"/></td>
</tr>

<tr>
	<td>银行卡号：</td>
	<td><input name="bank_card_number" type="text" value="<?php echo get_shop('bank_card_number')?>"/></td>
</tr>

<tr>
	<td></td>
	<td><input type="button" value="提交" onclick="submitShopForm();"/></td>
</tr>
</table>

</div>
</form>

<?php 
show_admin_footer();
?>