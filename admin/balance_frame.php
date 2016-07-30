<?php 


/**************************************************************************
 * 公共头部
 **************************************************************************/
function show_admin_header ($title="") { ?>
<!DOCTYPE html>

<html>

<head>
<title>后台管理系统<?php echo strlen($title)>0?" - ".$title:""?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="css/admin.css" />
<link rel="stylesheet" href="css/jquery-ui.css">
<link rel="stylesheet" href="css/jquery-ui.theme.css">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>
<script type="text/javascript" src="js/admin_calendar.js"></script>
<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=R1tII8wkR3VBSXP4PaHbFUTy"></script>
</head>

<body>

<div class="centerDiv">
<h1>后台管理系统</h1>
</div>

<div class="centerDiv adminNav">
<a href="order.php">订单管理</a> |
<a href="user.php"> 用户管理</a> | 
<a href="shop.php">商户管理</a> | 
<a href="coupon_manage.php">优惠券管理</a> | 
<a href="product_sale.php">菜品销量</a> | 
<a href="product.php">菜品审核</a> | 
<a href="coupon.php">发放优惠券</a> | 
<a href="vcode.php">查询验证码</a> | 
<a href="comment.php">评论审核</a> |
<a href="combine.php">每日统计</a> |
<a href="balance.php">商户结算</a> |
<a href="dada_connect.php">达达对接</a> |
<a href="material.php">发放物料</a> | 
<a href="admin.php">管理员维护</a> | 
<a href="pass.php"> 密码修改</a> | 
<a href="exit.php">退出登陆</a> |
当前用户:<?php echo get_login_admin()['admin_name']?>
</div>
<?php }

/**************************************************************************
 * 数据表显示
 **************************************************************************/
function balance_show_data_table($headers,$data, $start_date, $end_date, $currentPage=NULL,$pageCount=NULL,$idField=NULL,$actions=NULL,$lovCols=NULL) {
	global $listOfValue;
?>

<?php if($currentPage) {?>
<div class="dataPageSelector">
<?php if($currentPage>1) {?>
<a href="javascript:onPage(<?php echo $currentPage-1?>);">上一页</a>
<?php }?>
<?php if($currentPage<$pageCount) {?>
<a href="javascript:onPage(<?php echo $currentPage+1?>)">下一页</a>
<?php }?>
跳转到
<select id="dataPageSelect">
	<?php for($ipage=1;$ipage<=$pageCount;$ipage++){?>
	<option value="<?php echo $ipage?>"<?php output_select($ipage, $currentPage)?>><?php echo $ipage?></option>
	<?php }?>
</select>
页
</div>
<?php }?>
<table class="dataTable">
	<tr class="dataTableHeader">
		<?php foreach($headers as $key=>$value) {?>
		<td><?php echo $value ?></td>
		<?php } ?>
		<?php if($actions) {?>
		<td>操作</td>
		<?php }?>
	</tr>
	<?php foreach ($data as $row) {?>
	<tr>
		<?php foreach($headers as $key=>$value) {?>
		<td><?php
		if($lovCols && isset($lovCols[$key])) {
			$lovKey = $lovCols[$key];
			if(isset($listOfValue[$lovKey]) && isset($listOfValue[$lovKey][$row[$key]])) {
				echo $listOfValue[$lovKey][$row[$key]];
			} else {
				echo $row[$key];
			}
		} else {
			echo $row[$key];
		}
		?></td>
		<?php }?>
		<?php if($actions) {?>
		<td>
			<?php foreach($actions as $actionKey=>$actionValue) {?>
			<a href="javascript:on<?php echo $actionKey?>('<?php echo $row[$idField]?>', '<?php echo $start_date?>', '<?php echo $end_date?>');"><?php echo $actionValue?></a>
			<?php }?>
		</td>
		<?php }?>
	</tr>
	<?php }?>
</table>
<?php echo count($data)==0?"无数据":""?>
<script type="text/javascript">
$("#dataPageSelect").change(function(){
	onPage($(this).val());
});
</script>
<?php }



/**************************************************************************
 * 属性表显示
 **************************************************************************/
function show_property($data,$header=NULL,$lovCols=NULL) {
	global $listOfValue;
?>
<table class="dataTable">
<?php
if($header) {
	foreach($header as $key=>$value) {
?>
	<tr>
		<td class="dataTableHeader"><?php echo $value?></td>
		<td><?php
		if($lovCols && isset($lovCols[$key])) {
			$lovKey = $lovCols[$key];
			if(isset($listOfValue[$lovKey]) && isset($listOfValue[$lovKey][$data[$key]])) {
				echo $listOfValue[$lovKey][$data[$key]];
			} else {
				echo $data[$key];
			}
		} else {
			echo $data[$key];
		}
		?></td>
	</tr>
<?php	
	}
} else {
	foreach($data as $key=>$value) {
?>
	<tr>
		<td class="dataTableHeader"><?php echo $key?></td>
		<td><?php
		if($lovCols && isset($lovCols[$key])) {
			$lovKey = $lovCols[$key];
			if(isset($listOfValue[$lovKey]) && isset($listOfValue[$lovKey][$row[$key]])) {
				echo $listOfValue[$lovKey][$row[$key]];
			} else {
				echo $data[$key];
			}
		} else {
			echo $data[$key];
		}
		?></td>
	</tr>
<?php
	}
}
?>
</table>
<?php }

/**************************************************************************
 * 公共尾部
 **************************************************************************/
function show_admin_footer() { ?>
</body>
</html>
<?php } 










?>