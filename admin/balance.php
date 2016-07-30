<?php
require '../inc/inc.php';
require 'funcs.php';
require 'balance_frame_02.php';
check_login();

$field = trim(p("field"));
$start_date = trim(p("start_date"));
$end_date = trim(p("end_date"));
$page = p("page");
if(strlen($page)==0) $page=1;

$where_sql = "";

$sql_count = "select count(distinct b.shop_id) shop_bill_count from ejew_bill b ";

$item_count = db_query_value($sql_count,is_string_empty($where_sql)?NULL:array($start_date));
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);

if(is_string_empty($start_date)) {
	$start_date = "2015-08-01";
}
if(is_string_empty($end_date)) {
	$end_date = date("Y-m-d");
}

$sql = "select b.shop_id shop_id, s.shop_name shop_name, s.shop_phone shop_phone, s.shop_address shop_address, sum(b.bill_amount) shop_balance, (0) all_bonus_allowance_income, (0) all_income "
		." from ejew_bill b, ejew_shop s "
		." where (DATE_FORMAT(b.create_at,'%Y-%m-%d') BETWEEN ? AND ? ) "
		. " and b.shop_id=s.shop_id "
		." group by b.shop_id order by shop_balance desc";
$data = db_query($sql, [$start_date, $end_date]);

$data_count=0;
foreach($data as $data_item) {
	$sql_bonus_allowance = "select DATE_FORMAT(predicted_time,'%Y-%m-%d') days, "
			." (CASE WHEN count(distinct order_id)<=0 THEN 0 ELSE count(distinct order_id) END) as order_count, "
			." count(distinct user_id) user_count, "
			." count(distinct shop_id) shop_count, "
			." COUNT(CASE WHEN (total_fee+coupon_amount)>=15 THEN 1 ELSE NULL END) AS more_15_count,"
			." sum(is_little_e) little_e_count , "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 00 AND 15 THEN 1 ELSE NULL END) AS lunch_count, "
			." COUNT(CASE WHEN HOUR(predicted_time) BETWEEN 15 AND 24 THEN 1 ELSE NULL END) AS dinner_count, "
			." sum(is_first_order) first_order_count, "
			." count(CASE WHEN coupon_amount>0 THEN 1 ELSE NULL END) as use_coupon_count, "
			." count(CASE WHEN DATE_FORMAT(predicted_time,'%Y-%m-%d')>DATE_FORMAT(order_time,'%Y-%m-%d') THEN 1 ELSE NULL END) as pre_order_count, "
			." (CASE WHEN count(distinct order_id)>=5 THEN 5*10 ELSE (IF(count(distinct order_id)=4, 4*10, 0)) END) AS bonus, "
			." (CASE WHEN count(distinct order_id)>=3 THEN 0 ELSE (3-count(distinct order_id))*10 END) AS allowance "
			." from v_order where shop_id = ? and (DATE_FORMAT(predicted_time,'%Y-%m-%d') BETWEEN ? AND ? ) and order_status in (4,9) group by days "
			." order by days asc ";
	$data_bonus_allowance = db_query($sql_bonus_allowance, array($data_item['shop_id'], $start_date, $end_date));
	
	$data_initial_bonus_allowance = array(array('date'=>$start_date));
	for($i=0;$i<27; $i++) {
		$data_initial_bonus_allowance[$i]['date'] =  date('Y-m-d',strtotime("$start_date + $i day"));
	}
	
	$bonus_count=0;
	$allowance_count=0;
	$other_allowance=0;
	$all_bonus_allowance_income=0;
	
	$i=0;
	foreach($data_bonus_allowance as $item) {
		$bonus_count += $item['bonus'];
		$allowance_count += $item['allowance'];
		$i++;
	}
	
	$shop_leave_dates = db_query("select count(distinct leave_date) leave_dates from ejew_shop_status where shop_id = ? and (date_format(leave_date, '%Y-%m-%d') between '2015-09-24' and '2015-10-20') ", array($data_item['shop_id']));
	
	$other_allowance = (27-$i-$shop_leave_dates[0]['leave_dates'])*30;
	$all_bonus_allowance_income=$other_allowance+$bonus_count+$allowance_count;
	
	$data[$data_count]['all_bonus_allowance_income']=$all_bonus_allowance_income;
	$data[$data_count]['all_income']=$data[$data_count]['shop_balance'] + $data[$data_count]['all_bonus_allowance_income'];
	$data_count++;
}

$headers = array(
		"shop_id"=>"ID",
		"shop_name"=>"名称",
		"shop_phone"=>"电话",
		"shop_address"=>"地址",
		"shop_balance"=>"销售收入",
		"all_bonus_allowance_income"=>"奖励补贴",
		"all_income"=>"合计"
);

$actions = array (
	"BalanceDetail"=>"订单明细",
	"BonusDetail"=>"每日奖励补贴"
);

$lovCols = array (
	"shop_balance"=>"shop_balance"
);

show_admin_header("商户结算");
?>

<script type="text/javascript">
function onBalanceDetail(shop_id, start_date, end_date) {
	 window.open("balance_detail.php?shop_id="+shop_id+"&start_date="+start_date+"&end_date="+end_date);
}

/* function onBonusDetail(shop_id, start_date, end_date) {
	 window.open("balance_bonus_detail_02.php?shop_id="+shop_id+"&start_date="+start_date+"&end_date="+end_date);
}
 */
 
function onBonusDetail(shop_id, start_date, end_date) {
	window.open("balance_bonus_detail_03.php?shop_id="+shop_id+"&start_date="+start_date+"&end_date="+end_date);
}

function onAllowanceDetail(shop_id, start_date, end_date) {
	 window.open("balance_allowance_detail.php?shop_id="+shop_id+"&start_date="+start_date+"&end_date="+end_date);
}

function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
</script>


<form id="queryForm" method="get" action="balance.php">
<div class="blockDiv">
<b>结算区间</b>
开始日期
<input type="text" name="start_date" style="width:150px;" value="<?php echo $start_date?>"/>
截止日期
<input type="text" name="end_date" style="width:150px;" value="<?php echo $end_date?>"/>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询"/>
</div>
</form>

<div class="blockDiv">
<?php balance_show_data_table($headers, $data, $start_date, $end_date, NULL, NULL,"shop_id",$actions,$lovCols)?>
</div>


<?php 
show_admin_footer();
?>
