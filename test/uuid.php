<?php
//echo uniqid(md5(srand()),true)."<br/>";
//echo uniqid(md5(srand()),true);

//echo md5(mt_rand(0,100000000000));
for($i=0;$i<100;$i++) {
	usleep(10);
	echo uniqid()."<br/>";
}