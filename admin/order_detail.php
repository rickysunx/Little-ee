<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$order_id = p("order_id");
$admin_id = get_login_admin_id();

$order = db_query_row("select * from v_order o,ejew_shop s where o.shop_id=s.shop_id and o.order_id=?",array($order_id));

$order_header = array (
	"order_channel"=>"订单渠道",
	"user_id"=>"用户id",
	"shop_id"=>"厨房id",
	"shop_phone"=>"厨房电话",
	"shop_name"=>"厨房名称",
	"shop_address"=>"厨房地址",
	"shop_address_row2"=>"厨房门牌号",
	"order_time"=>"下单时间",
	"complete_time"=>"送达时间",
	"predicted_time"=>"期望送餐时间",
	"order_address"=>"送餐地址",
	"order_address_row2"=>"门牌号",
	"contact_name"=>"联系人姓名",
	"phone"=>"联系电话",
	"order_memo"=>"用户留言",
	"order_status"=>"订单状态",
	"complain_status"=>"投诉状态",
	"delivery_method"=>"送餐方式",
	"delivery_fee"=>"送餐费",
	"coupon_amount"=>"优惠金额",
	"total_fee"=>"实付费用",
	"pay_channel"=>"支付渠道",
	"pay_time"=>"支付时间",
	"pay_status"=>"支付状态"
);

$order_lovCols = array (
	"order_status"=>"order_status",
	"pay_status"=>"pay_status",
	"complain_status"=>"complain_status",
	"delivery_method"=>"delivery_method"
);

$order_products = db_query("select p.product_name,op.product_count,op.product_price ".
		"from ejew_order_product op,ejew_product p ".
		"where op.product_id=p.product_id and order_id = ? ",array($order_id));

$order_product_header = array(
	"product_name"=>"名称",
	"product_price"=>"价格",
	"product_count"=>"数量"
);

$complains = db_query(
		"select *,'' as 'action' ".
		"from ejew_order_complain c,ejew_admin a ".
		"where c.admin_id=a.admin_id and c.deleted=0 and order_id=? ".
		"order by c.complain_time desc ",array($order_id));

foreach($complains as &$complain) {
	$complain['complain_detail'] = htmlspecialchars($complain['complain_detail']);
	$complain['complain_detail'] = str_replace("\r\n","<br/>",$complain['complain_detail']);
	$complain['action'] = ($admin_id==$complain['admin_id'])?("<a href='javascript:onDelete(".$complain['complain_id'].");'>删除</a>"):"";
}

$complain_header = array (
	"admin_name"=>"管理员",
	"complain_time"=>"投诉时间",
	"complain_tag"=>"投诉标签",
	"complain_detail"=>"投诉内容",
	"action" => "操作"
);

$complain_tags = db_query("select distinct(complain_tag) from ejew_order_complain");

show_admin_header("订单信息");
?>

<?php if($order) {?>

<div class="sectionTitle"><div>
订单信息
</div></div>

<div class="blockDiv">
<?php show_property($order,$order_header,$order_lovCols)?>
</div>

<div class="sectionTitle"><div>
菜品信息
</div></div>

<div class="blockDiv">
<?php show_data_table($order_product_header, $order_products)?>
</div>

<div class="sectionTitle"><div>
投诉信息
</div></div>

<script type="text/javascript">
$(document).ready(function(){
	$("#selectComplain").change(function(){
		var value = $(this).val();
		if(value!="") $("#complainTagInput").val(value);
	});
	
});

function submitOrderComplain() {
	$.post("actions.php",$("#complainForm").serialize(),function(data){
		if(data.success) {
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}

function onDelete(id) {
	if(confirm("确定要删除这条记录吗？")) {
		$.post("actions.php",{action:'order_complain_delete','complain_id':id},function(data){
			if(data.success) {
				window.location.reload();
			} else {
				alert(data.errmsg);
			}
		},"json");
	}
}
</script>

<?php 

?>
<div class="blockDiv">
	<?php show_data_table($complain_header, $complains, NULL, NULL)?>
</div>
<form id="complainForm" action="actions.php" method="post" onsubmit="submitOrderComplain();return false;">
<div class="blockDiv">
<table>
<tr>
	<td>
	投诉标签：
	</td>
	<td>
		<input type="hidden" name="action" value="order_complain_save"/>
		<input type="hidden" name="order_id" value="<?php echo $order_id?>"/>
		<input type="text" id="complainTagInput" name="complain_tag" style="width:120px;height:18px;"/>
		<select id="selectComplain" name="complain_tag_selection" style="width:120px;height:20px;">
			<option value="">-选择标签-</option>
			<?php foreach($complain_tags as $complain_tag) { ?>
			<option><?php echo $complain_tag['complain_tag']?></option>
			<?php }?>
		</select>
	</td>
</tr>

<tr>
	<td>
	投诉内容：
	</td>
	<td>
	<textarea rows="5" cols="50" name="complain_detail"></textarea>
	</td>
</tr>

<tr>
<td></td>
<td><input type="submit" value="保存" /></td>
</tr>
</table>
</div>
</form>

<div style="height:100px;">
</div>
<?php } else {?>
<div class="centerDiv">
订单信息不存在
</div>
<?php }?>

<?php 
show_admin_footer();
?>