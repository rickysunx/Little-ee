<?php
require "inc/inc.php";
require "inc/api_common.php";

/*
$xml = "<?xml version='1.0'?>
	<xml>
	<return_code>FAIL</return_code>
	<return_msg><![CDATA[SYSERR]]></return_msg>
	</xml>";

$element = simplexml_load_string($xml);
var_dump((array)$element);
$element = (array)$element;
var_dump($element['return_code']);
var_dump((string)$element['return_msg']);
*/

//echo $_SERVER['REMOTE_ADDR'];
$mm = "1.230";
echo doubleval($mm);
