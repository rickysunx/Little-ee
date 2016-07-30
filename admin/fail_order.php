<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$start_date = trim(p("start_date"));
$end_date = trim(p("end_date"));
if(is_string_empty($start_date)) {
	$start_date = "2015-08-01";
}
if(is_string_empty($end_date)) {
	$end_date = date("Y-m-d");
}

$field_01 = trim(p("field_01"));
$keyword_01 = trim(p("keyword_01"));
$field = trim(p("field"));
$keyword = trim(p("keyword"));
$page = p("page");
if(strlen($page)==0) $page=1;

$where_sql = "";
$where_sql_01 = "";

$acculate_amount = 0;

if(is_string_not_empty($keyword) && is_string_not_empty($field)>0) {
	if($field=="orderId") {
		$where_sql = " o.order_id=?";
	} else if($field=="userPhone") {
		$where_sql = " o.user_id in (select user_id from ejew_user where user_phone=?) ";
	} else if($field=="shopName") {
		$where_sql = " o.shop_id in (select shop_id from ejew_shop where shop_name like concat('%',?,'%'))";
	} else if($field=="shopPhone") {
		$where_sql = " o.shop_id in (select shop_id from ejew_shop where shop_phone=? )";
	} else if($field=="userId") {
		$where_sql = " o.user_id = ? ";
	} else if($field=="shopId") {
		$where_sql = " o.shop_id = ? ";
	}  else if($field=="orderStatus") {
		$where_sql = " b.order_status  = ? ";
	}  else if($field=="payStatus") {
		$where_sql = " b.pay_status  = ? ";
	}  else if($field=="orderTime") {
		$where_sql = " date_format(o.order_time,'%Y-%m-%d')  like concat('%',?,'%') ";
	}  else if($field=="predictedTime") {
		$where_sql = "  date_format(o.predicted_time,'%Y-%m-%d') like concat('%',?,'%') ";
	}
}

if(is_string_not_empty($keyword_01) && is_string_not_empty($field_01)>0) {
	if($field_01=="orderId_01") {
		$where_sql_01 = " o.order_id=?";
	} else if($field_01=="userPhone_01") {
		$where_sql_01 = " o.user_id in (select user_id from ejew_user where user_phone=?) ";
	} else if($field_01=="shopName_01") {
		$where_sql_01 = " o.shop_id in (select shop_id from ejew_shop where shop_name like concat('%',?,'%'))";
	} else if($field_01=="shopPhone_01") {
		$where_sql_01 = " o.shop_id in (select shop_id from ejew_shop where shop_phone=? )";
	} else if($field_01=="userId_01") {
		$where_sql_01 = " o.user_id = ? ";
	} else if($field_01=="shopId_01") {
		$where_sql_01 = " o.shop_id = ? ";
	}  else if($field_01=="orderStatus_01") {
		$where_sql_01 = " b.order_status  = ? ";
	}  else if($field_01=="payStatus_01") {
		$where_sql_01 = " b.pay_status  = ? ";
	}  else if($field_01=="orderTime_01") {
		$where_sql_01 = " date_format(o.order_time,'%Y-%m-%d') like concat('%',?,'%') ";
	}  else if($field_01=="predictedTime_01") {
		$where_sql_01 = "  date_format(o.predicted_time,'%Y-%m-%d') like concat('%',?,'%') ";
	}
}

$sql_raw = " from ejew_order o,ejew_order_b b,ejew_shop s,ejew_user u, ejew_bill bill "
			." where o.order_id=b.order_id and o.shop_id=s.shop_id and o.user_id=u.user_id and o.order_id=bill.order_id "
			." and DATE_FORMAT(bill.bill_time,'%Y-%m-%d') <> DATE_FORMAT(o.predicted_time,'%Y-%m-%d')";
$sql_count = "select count(o.order_id) ".$sql_raw.
	(is_string_empty($where_sql)?" and b.order_status in (4,9) ":(" and b.order_status in (4,9) and ".$where_sql));

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($keyword));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);

/* if(is_string_empty($where_sql_01)) {
	$sql = "select * ".$sql_raw
			.(is_string_empty($where_sql)?" and b.order_status in (4,9) ":" and b.order_status in (4,9) and ".$where_sql)
			." and (DATE_FORMAT(o.order_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." order by shop_name, order_time desc ".$sql_limit;
	$data = db_query($sql, is_string_empty($where_sql)?array($start_date, $end_date):array($keyword, $start_date, $end_date));
} else if (is_string_empty($where_sql)) {
	$sql = "select * ".$sql_raw
			.(is_string_empty($where_sql_01)?" and b.order_status in (4,9) ":" and b.order_status in (4,9) and ".$where_sql_01)
			." and (DATE_FORMAT(o.order_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." order by shop_name, order_time desc ".$sql_limit;
	$data = db_query($sql, is_string_empty($where_sql_01)?array($start_date, $end_date):array($keyword_01, $start_date, $end_date));
} else {
	$sql = "select * ".$sql_raw
			.(is_string_empty($where_sql)?" and b.order_status in (4,9) ":" and b.order_status in (4,9) and ".$where_sql)
			.(is_string_empty($where_sql_01)?" and b.order_status in (4,9) ":" and b.order_status in (4,9)  and ".$where_sql_01)
			." and (DATE_FORMAT(o.order_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
			." order by shop_name, order_time desc ".$sql_limit;
	$data = db_query($sql, array($keyword, $keyword_01, $start_date, $end_date));
}
 */
 
$sql = "select * ".$sql_raw
		.(is_string_empty($where_sql)?" and b.order_status in (4,9) ":" and b.order_status in (4,9) and ".$where_sql)
		." and (DATE_FORMAT(o.order_time,'%Y-%m-%d') BETWEEN ? AND ? ) "
		." order by shop_name, order_time desc ".$sql_limit;
$data = db_query($sql, is_string_empty($where_sql)?array($start_date, $end_date):array($keyword, $start_date, $end_date));

$acculate_amount = 0;
	
foreach($data as $data_item) {
	$acculate_amount += $data_item['bill_amount'];
}

$headers = array(
	"order_id"=>"订单号",
	"user_phone"=>"用户手机",
	"shop_name"=>"家厨名称",
	"shop_phone"=>"家厨手机",
	"shop_address"=>"家厨小区",
	"total_fee"=>"合计金额",
	"order_status"=>"订单状态",
	"pay_status"=>"支付状态",
	"order_time"=>"下单时间",
	"bill_time"=>"账单时间",
	"predicted_time"=>"送餐时间"
);

$actions = array (
	"Detail"=>"查看",
	"Edit"=>"修改状态"
);

$lovCols = array (
	"order_status"=>"order_status",
	"pay_status"=>"pay_status"
);

show_admin_header("订单管理");
?>

<script type="text/javascript">
function onDetail(id) {
	window.open("order_detail.php?order_id="+id);
}
function onOrder(id) {
	window.open("order.php?user_id="+id);
}
function onComplain(id) {
	window.open("order_complain.php?order_id="+id);
}
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
function onEdit(id) {

	$.post("actions.php?action=get_order_status",{order_id:id},function(data){
		if(!data.success) {
			alert("获取订单状态失败："+data.errmsg);
			return;
		}
		$("#EditOrderNotice").text("修改订单["+id+"]的状态，当前状态为["+data.order_status+"]"+data.order_status_detail);
		$("#EditOrderStatusSelect").val(data.order_status);
		$("#EditOrderStatus").dialog({
			title:"修改订单状态",modal:true,width:500,
			buttons:{
				"确定":function(){
					var newOrderStatus = $("#EditOrderStatusSelect").val();
					if(newOrderStatus==data.order_status) {
						alert("您没有调整订单状态");
					} else {
						$.post("actions.php?action=set_order_status",{order_id:id,order_status:newOrderStatus},
							function(data){
								if(data.success) {
									$("#EditOrderStatus").dialog("close");
									window.location.reload();
								} else {
									alert(data.errmsg);
								}
							},"json");
					}
				},
				"取消":function(){
					$("#EditOrderStatus").dialog("close");
				}
			}
		});
	},"json");
	

}
</script>


<div id="EditOrderStatus" style="display:none;">
<form id="EditOrderStatusForm">
<div id="EditOrderNotice">修改订单的状态：</div>
<table border="0">
	<tr>
		<td>订单状态：</td>
		<td>
		<select id="EditOrderStatusSelect">
<?php 
foreach ($listOfValue['order_status_admin'] as $key=>$value) {
	echo "<option value='${key}'>{$key}-{$value}</option>\r\n";
}
?>
		</select>
		</td>
	</tr>
</table>
<div>-----------------------------------------------</div>
<div style="color:red;">修改订单状态请务必慎重</div>
<div style="color:red;">错误的订单状态会导致系统不可预知的错误</div>
<div><a style="color:#48A5AB;" href="images/order_flowchart.png" target="_blank">点击&gt;&gt;参考订单状态转化流程图&lt;&lt;</a></div>
</form>
</div>


<form id="queryForm" method="get" action="fail_order.php">
<div class="blockDiv">
<b>订单查询条件一</b>
<select name="field">
	<option value="orderId"<?php output_select("orderId",$field);?>>订单号</option>
	<option value="userId"<?php output_select("userId",$field);?>>用户ID</option>
	<option value="userPhone"<?php output_select("userPhone",$field);?>>用户手机</option>
	<option value="shopName"<?php output_select("shopName",$field);?>>商户名称</option>
	<option value="shopPhone"<?php output_select("shopPhone",$field);?>>商户手机</option>
	<option value="orderStatus"<?php output_select("orderStatus",$field);?>>订单状态</option>
	<option value="payStatus"<?php output_select("payStatus",$field);?>>支付状态</option>
	<option value="orderTime"<?php output_select("orderTime",$field);?>>下单时间</option>
	<option value="predictedTime"<?php output_select("predictedTime",$field);?>>送餐时间</option>
</select>
<input type="text" name="keyword" style="width:150px;" value="<?php echo $keyword?>"/> <br>
<input type="hidden" id="pageInput" name="page" value=""/> 
<b>订单查询条件二</b>
<select name="field_01">
	<option value="orderId_01"<?php output_select("orderId_01",$field_01);?>>订单号</option>
	<option value="userId_01"<?php output_select("userId_01",$field_01);?>>用户ID</option>
	<option value="userPhone_01"<?php output_select("userPhone_01",$field_01);?>>用户手机</option>
	<option value="shopName_01"<?php output_select("shopName_01",$field_01);?>>商户名称</option>
	<option value="shopPhone_01"<?php output_select("shopPhone_01",$field_01);?>>商户手机</option>
	<option value="orderStatus_01"<?php output_select("orderStatus_01",$field_01);?>>订单状态</option>
	<option value="payStatus_01"<?php output_select("payStatus_01",$field_01);?>>支付状态</option>
	<option value="orderTime_01"<?php output_select("orderTime_01",$field_01);?>>下单时间</option>
	<option value="predictedTime_01"<?php output_select("predictedTime_01",$field_01);?>>送餐时间</option>
</select>
<input type="text" name="keyword_01" style="width:150px;" value="<?php echo $keyword_01?>"/> <br>
<br>

开始日期
<input type="text" name="start_date" style="width:150px;" value="<?php echo $start_date?>"/>
截止日期
<input type="text" name="end_date" style="width:150px;" value="<?php echo $end_date?>"/>

<input type="submit" value="查询"/>
<input type="button" value="查看所有订单" onclick="window.location.href='fail_order.php'"/> <br>
累计刷单
<input type="text" name="acculate_amount" style="width:150px;" value="<?php echo $acculate_amount?>"/>

</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, NULL, NULL,"order_id",$actions,$lovCols)?>
</div>


<?php 
show_admin_footer();
?>
