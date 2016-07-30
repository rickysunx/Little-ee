<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$shop_id = p("shop_id");
if(!$shop_id) {
	echo "商户id不能为空";
	exit();
}

$shop = db_query_row("select * from ejew_shop where shop_id=?",[$shop_id]);

if(!$shop) {
	echo "商户不存在";
	exit();
}

$products = db_query("select * from ejew_product where shop_id=?",[$shop_id]);
$product_header = array (
	"product_name"=>"菜品名称",
	"product_image"=>"菜品图片",
	"product_price"=>"菜品价格",
	"product_count"=>"预估库存",
	"is_main"=>"主食",
	"is_sign"=>"招牌菜",
	"is_half"=>"半份"
);

$product_action = array (
	"Edit"=>"编辑",
	"Delete"=>"删除"
);

$product_lovCols = array (
	"is_main"=>"boolean_value",
	"is_sign"=>"boolean_value",
	"is_half"=>"boolean_value"
);

foreach($products as &$product) {
	$product['product_image'] = "<img style='width:100px;' src='".$image_full_url.$product['product_image']."'/>";
}


show_admin_header("菜品管理");

?>

<script type="text/javascript">

function onDelete(id) {
	if(!confirm("确定要删除该菜品吗？")) {
		return;
	}
	$.post("actions.php?action=delete_product",{"product_id":id},function(data) {
		if(data.success) {
			window.location.reload();
		} else {

		}
	},"json");
}

function onEdit(id) {
	$.post("actions.php?action=get_product",{"product_id":id},function(data){

		if(!data.success) {
			alert("获取数据失败"+data.errmsg);
			return;
		}

		$("#product_name").val(data.product_name);
		$("#product_price").val(data.product_price);
		$("#product_count").val(data.product_count);
		$("#today_stock").val(data.today_stock);
		$("#tomorrow_stock").val(data.tomorrow_stock);
		
		if(data.is_main) {
			$("#is_main_true").attr("checked","checked");
			$("#is_main_false").removeAttr("checked");
		} else {
			$("#is_main_true").removeAttr("checked");
			$("#is_main_false").attr("checked","checked");
		}

		if(data.is_sign) {
			$("#is_sign_true").attr("checked","checked");
			$("#is_sign_false").removeAttr("checked");
		} else {
			$("#is_sign_true").removeAttr("checked");
			$("#is_sign_false").attr("checked","checked");
		}

		if(data.is_half) {
			$("#is_half_true").attr("checked","checked");
			$("#is_half_false").removeAttr("checked");
		} else {
			$("#is_half_true").removeAttr("checked");
			$("#is_half_false").attr("checked","checked");
		}

		addProductImage(data.product_image,data.product_image_url);
		
		$("#ProductEditor").dialog({
			title:"编辑菜品",modal:true,width:500,
			buttons:{
				"确定":function(){
					var params = $("#ProductForm").serialize();
					params += '&product_id='+data.product_id;
					$.post("actions.php?action=update_product",params,function(data){
						if(data.success) {
							alert("保存成功");
							$("#ProductEditor").dialog("close");
						} else {
							alert("保存失败："+data.errmsg);
						}
					},"json");
				},
				"取消":function(){
					$(this).dialog("close");
				}
			}
		});
	},"json");
}

function onCreate() {
	$("#product_name").val("");
	$("#product_price").val("");
	$("#product_count").val("");
	$("#today_stock").val("");
	$("#tomorrow_stock").val("");

	if(false) {
		$("#is_main_true").attr("checked","checked");
		$("#is_main_false").removeAttr("checked");
	} else {
		$("#is_main_true").removeAttr("checked");
		$("#is_main_false").attr("checked","checked");
	}

	if(false) {
		$("#is_sign_true").attr("checked","checked");
		$("#is_sign_false").removeAttr("checked");
	} else {
		$("#is_sign_true").removeAttr("checked");
		$("#is_sign_false").attr("checked","checked");
	}

	if(false) {
		$("#is_half_true").attr("checked","checked");
		$("#is_half_false").removeAttr("checked");
	} else {
		$("#is_half_true").removeAttr("checked");
		$("#is_half_false").attr("checked","checked");
	}
	
	$("#ProductEditor").dialog({
		title:"添加菜品",modal:true,width:500,
		buttons:{
			"确定":function(){
				$.post("actions.php?action=add_product",$("#ProductForm").serialize(),function(data){
					if(data.success) {
						alert("保存成功");
						$("#ProductEditor").dialog("close");
						window.location.reload();
					} else {
						alert("保存失败："+data.errmsg);
					}
				},"json");
			},
			"取消":function(){
				$(this).dialog("close");
			}
		}
	});
}


function addProductImage(image_name,image_name_url) {
	$("#productImageDiv").html(
		"<div id='productImageDiv' class='imageUpload'>"+
		"<input name='product_image' type='hidden' value='"+image_name+"'/>"+
		"<img src='"+ image_name_url+"'/>"+
		"<button onclick='deleteImage(\"productImageDiv\");'>删除</button></div>"
	);
}

function deleteImage(image_id) {
	$("#"+image_id).remove();
}
</script>

<div class="sectionTitle"><div>
编辑厨房信息
<input type="button" value="添加菜品" onclick="onCreate();"/>
</div></div>

<div id="ProductEditor" style="display:none;">
<form id="ProductForm">

<input type="hidden" name="shop_id" value="<?php echo $shop_id?>"/>

<table>

<tr>
	<td>菜品名称：</td>
	<td><input id="product_name" name="product_name" type="text"/></td>
</tr>

<tr>
	<td>菜品价格：</td>
	<td><input id="product_price" name="product_price" type="text"/></td>
</tr>

<tr>
	<td>预估库存：</td>
	<td><input id="product_count" name="product_count" type="text"/></td>
</tr>

<tr>
	<td>是否主食：</td>
	<td>
		<input id="is_main_true" name="is_main" type="radio" value="1"/>是
		<input id="is_main_false" name="is_main" type="radio" value="0"/>否
	</td>
</tr>

<tr>
	<td>是否招牌菜：</td>
	<td>
		<input id="is_sign_true" name="is_sign" type="radio" value="1"/>是
		<input id="is_sign_false" name="is_sign" type="radio" value="0"/>否
	</td>
</tr>

<tr>
	<td>是否支持半份：</td>
	<td>
		<input id="is_half_true" name="is_half" type="radio" value="1"/>是
		<input id="is_half_false" name="is_half" type="radio" value="0"/>否
	</td>
</tr>

<tr>
	<td>今日库存：</td>
	<td>
		<input id="today_stock" name="today_stock" type="text"/>
	</td>
</tr>

<tr>
	<td>明日库存：</td>
	<td>
		<input id="tomorrow_stock" name="tomorrow_stock" type="text"/>
	</td>
</tr>

<tr>
	<td>菜品图片：</td>
	<td>
		<div id="productImageDiv"></div>
		<iframe src="upload_image.php?callback=addProductImage" frameBorder=0 scrolling="no" 
			style="margin-top:10px;border:1px solid #999;width:300px;height:25px;overflow:hidden;"></iframe>
	</td>
</tr>

</table>

</form>
</div>

<div class="blockDiv">
<?php show_data_table($product_header, $products, NULL,NULL,"product_id",$product_action,$product_lovCols)?>
</div>

<?php 
show_admin_footer();
?>