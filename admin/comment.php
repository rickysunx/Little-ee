<?php
require '../inc/inc.php';
require 'funcs.php';
require 'frame.php';
check_login();

$approval_status = p("approval_status");

$page = p("page");
if(strlen($page)==0) $page=1;
if(is_string_empty($approval_status)) {
	$approval_status = 3;
}

if($approval_status==1 || $approval_status==2 ||$approval_status==3) {
	$where_sql = " comment_approval=".$approval_status." or reply_approval=".$approval_status;
}

if($approval_status==-1) $where_sql = " 1=1 ";


$sql_raw = " from ejew_comment c,ejew_order o,ejew_product p where c.order_id=o.order_id ";
$sql_count = "select count(comment_id) ".$sql_raw.
(is_string_empty($where_sql)?"":(" and ".$where_sql));

$item_count = db_query_value($sql_count);
$page_count = get_page_count($item_count);
$sql_limit = get_sql_limit($page, $item_count);
$sql = "select * ".$sql_raw.(is_string_empty($where_sql)?"":" and ".$where_sql)." order by c.update_at desc ".$sql_limit;

$data = db_query($sql);

foreach($data as &$row) {
	$row['comment_detail'] = "<pre>".$row["comment_detail"]."</pre>";
	$row['actions'] = "<a href='javascript:approveCommentPass(".$row["comment_id"].");'>评论审核通过</a><br/>".
		"<a href='javascript:approveCommentNotPass(".$row["comment_id"].");'>评论审核不通过</a><br/>".
		"<a href='javascript:approveReplyPass(".$row["comment_id"].");'>回复审核通过</a><br/>".
		"<a href='javascript:approveReplyNotPass(".$row["comment_id"].");'>回复审核不通过</a><br/>";
}

$headers = array(
	"order_id"=>"订单id",
	"comment_mark"=>"评论星级",
	"comment_detail"=>"评论内容",
	"comment_time"=>"评论时间",
	"comment_approval"=>"评论审核状态",
	"reply_time"=>"回复时间",
	"reply_detail"=>"回复内容",
	"reply_approval"=>"回复审核状态",
	"actions"=>"操作"
);

$lovCols = array (
	"comment_approval"=>"approval_status",
	"reply_approval"=>"approval_status"
);

show_admin_header("菜品审核");
?>
<script type="text/javascript">
function onDetail(id) {
	window.open("product_detail.php?product_id="+id);
}
function onPage(page) {
	$("#pageInput").val(page);
	$("#queryForm").submit();
}
$(document).ready(function(){
	$("#approvalStatusSelect").val(<?php echo $approval_status?>);
});
function approveCommentPass(id) {
	$.post("actions.php",{action:'comment_approve',comment_id:id,approval_status:1},function(data){
		if(data.success) {
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}
function approveCommentNotPass(id) {
	$.post("actions.php",{action:'comment_approve',comment_id:id,approval_status:2},function(data){
		if(data.success) {
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}
function approveReplyPass(id) {
	$.post("actions.php",{action:'reply_approve',comment_id:id,approval_status:1},function(data){
		if(data.success) {
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}
function approveReplyNotPass(id) {
	$.post("actions.php",{action:'reply_approve',comment_id:id,approval_status:2},function(data){
		if(data.success) {
			window.location.reload();
		} else {
			alert(data.errmsg);
		}
	},"json");
}
</script>

<form id="queryForm" method="get" action="comment.php">
<div class="blockDiv">
<b>评论审核</b>
<select id="approvalStatusSelect" name="approval_status">
	<option value="3">待审核</option>
	<option value="1">审核通过</option>
	<option value="2">审核不通过</option>
	<option value="0">未审核</option>
	<option value="-1">全部</option>
</select>
<input type="hidden" id="pageInput" name="page" value=""/>
<input type="submit" value="查询"/>
</div>
</form>

<div class="blockDiv">
<?php show_data_table($headers, $data, $page, $page_count,"product_id",NULL,$lovCols)?>
</div>
<?php 
show_admin_footer();
?>