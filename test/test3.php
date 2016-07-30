<?php

$now = new DateTime();

echo $now->format("Y-m-d H:i:s");
$ts = $now->getTimestamp();
echo $ts;

$now->setTimestamp($ts-600);
echo $now->format("Y-m-d H:i:s");



