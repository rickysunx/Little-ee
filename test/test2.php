<?php
require "../inc/inc.php";
require "../inc/api_common.php";

$t1 = get_milliseconds();
$shops = array();

for($i=0;$i<100000;$i++) {
	$shop[] = array("lng"=>60.0,"lat"=>120.0);
}

$t2 = get_milliseconds();

echo $t2-$t1;