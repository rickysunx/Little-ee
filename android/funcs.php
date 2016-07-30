<?php

//common headers
header("Content-Type:text/html; charset=utf-8");

function is_string_empty($string) {
	return (!$string) || strlen($string)==0;
}

function is_string_not_empty($string) {
	return !is_string_empty($string);
}



