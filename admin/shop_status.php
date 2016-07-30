<?php
require '../inc/inc.php';
require 'funcs.php';
check_login();

$shop_id = p("shop_id");


$today_date = (new DateTime())->format("Y-m-d");
$leave_date_data = db_query("select * from ejew_shop_status where shop_id=? and leave_date>=?",[$shop_id,$today_date]);
$leave_dates = array();
foreach ($leave_date_data as $item) {
	$leave_dates[]=$item['leave_date'];
}

function show_month($date,$id) {
	global $leave_dates;
	$week_names = ['日','一','二','三','四','五','六'];
	$month = $date->format("n");
	$month_days = $date->format("t");
	$first_date = $date->format("Y-m-01");
	$week_first_date = (new DateTime($first_date))->format("w");
	$today_string = (new DateTime())->format("Y-m-d");
	$month_prefix = $date->format("Y-m-");
	$index = 1;
	echo "<table id='{$id}' class='admin_calendar'>";
	
	echo "<tr class='admin_calendar_title'><td colspan=7>{$month}月份</td></tr>";
	
	echo "<tr>";
	for($i=0;$i<7;$i++) {
		echo "<td>".$week_names[$i]."</td>";
	}
	echo "</tr>";
	
	echo "<tr>";
	for($i=0;$i<7;$i++) {
		if($i<$week_first_date) {
			echo "<td>&nbsp;</td>";
		} else {
			$theDate = $month_prefix.getXX($index);
			if($theDate<$today_string) {
				echo "<td class='admin_calendar_cell admin_calendar_cell_disabled'>{$index}</td>";
			} else {
				if(array_search($theDate, $leave_dates)!==FALSE) {
					echo "<td class='admin_calendar_cell admin_calendar_cell_selected' onclick='clickCalendarCell(this);'>{$index}</td>";
				} else {
					echo "<td class='admin_calendar_cell' onclick='clickCalendarCell(this);'>{$index}</td>";
				}
			}
			$index++;
		}
		
	}
	echo "</tr>";
	
	$continue = true;
	
	while($continue) {
		echo "<tr>";
		for($i=0;$i<7;$i++) {
			if($index>$month_days) {
				echo "<td>&nbsp;</td>";
				$continue = false;
			} else {
				$theDate = $month_prefix.getXX($index);
				if($theDate<$today_string) {
					echo "<td class='admin_calendar_cell admin_calendar_cell_disabled'>{$index}</td>";
				} else {
					if(array_search($theDate, $leave_dates)!==FALSE) {
						echo "<td class='admin_calendar_cell admin_calendar_cell_selected' onclick='clickCalendarCell(this);'>{$index}</td>";
					} else {
						echo "<td class='admin_calendar_cell' onclick='clickCalendarCell(this);'>{$index}</td>";
					}
				}
				
				$index++;
			}
			if($index>=$month_days) {
				$continue = false;
			}
		}
		echo "</tr>";
	}
	
	echo "</table>";
}


?>

<script type="text/javascript">
this_month_prefix = "<?php echo (new DateTime())->format("Y-m-") ?>";
next_month_prefix = "<?php

$today_date = (new DateTime());
$this_year = $today_date->format("Y");
$this_month = $today_date->format("m");
$this_month++;
if($this_month==13) {
	$this_year++;
	$this_month = 1;
}

$nextMonthPrefix = $this_year."-".getXX($this_month)."-";
$nextMonth = new DateTime($nextMonthPrefix."01");

echo $nextMonthPrefix ;

?>";

</script>

<div style="color:#f00;">点击日期，红色选中的日期，代表那天休息</div>
<table>
	<tr>
		<td style="vertical-align: top;"><?php show_month(new DateTime(),'thisMonth')?></td>
		<td style="vertical-align: top;"><?php show_month($nextMonth,'nextMonth')?></td>
	</tr>
</table>
